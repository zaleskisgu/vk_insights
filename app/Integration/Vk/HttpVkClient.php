<?php

namespace App\Integration\Vk;

use App\Contracts\VkClient;
use App\Data\Vk\GroupsGetByIdResultData;
use App\Data\Vk\WallGetResultData;
use App\Integration\Vk\Exception\VkApiException;
use App\Integration\Vk\Exception\VkClosedCommunityException;
use App\Integration\Vk\Exception\VkGroupNotFoundException;
use App\Integration\Vk\Exception\VkRateLimitException;
use App\Integration\Vk\Exception\VkUnavailableException;
use App\Integration\Vk\Method\GroupsGetByIdMethod;
use App\Integration\Vk\Method\WallGetMethod;
use App\Integration\Vk\Support\VkApiCallStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Живой VK API: POST https://api.vk.com/method/{name}, разбор response / error.
 */
class HttpVkClient implements VkClient
{
    private const RATE_LIMIT_RETRY_DELAY_MICROSECONDS = 2_000_000;

    /** @var list<int> */
    private const VK_RATE_LIMIT_ERROR_CODES = [6, 9];

    /** @var list<int> */
    private const VK_GROUP_NOT_FOUND_ERROR_CODES = [125, 113];

    /** @var list<int> */
    private const VK_CLOSED_COMMUNITY_ERROR_CODES = [203, 260];

    private ?GroupsGetByIdMethod $groupsGetByIdMethod = null;

    private ?WallGetMethod $wallGetMethod = null;

    public function __construct(
        private string $accessToken,
        private string $apiVersion,
        private ?VkApiCallStats $stats = null,
    ) {}

    public function getGroupById(int|string $groupId): array
    {
        if ($this->accessToken === '') {
            throw new \RuntimeException('groups.getById: пустой VK service token.');
        }

        /** @var array<string, mixed>|list<array<string, mixed>> $response */
        $response = $this->groupsGetById()->execute([
            'group_ids' => (string) $groupId,
            'fields' => 'photo_200,photo_100,photo_50,members_count,screen_name,name,is_closed,type',
        ]);

        return GroupsGetByIdResultData::fromVkApiResponse($response)->toArray();
    }

    public function getWall(int $ownerId, int $count = 100, int $offset = 0): array
    {
        if ($this->accessToken === '') {
            throw new \RuntimeException('wall.get: пустой VK service token.');
        }

        /** @var array<string, mixed> $wall */
        $wall = $this->wallGet()->execute([
            'owner_id' => $ownerId,
            'count' => min(max(1, $count), 100),
            'offset' => max(0, $offset),
        ]);

        return WallGetResultData::fromVkApiResponse($wall)->toArray();
    }

    /**
     * Содержимое поля {@code response} из ответа VK (или пустой массив, если нет ключа).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|list<mixed>
     */
    public function callMethod(string $method, array $params = []): array
    {
        return $this->callMethodWithRetry($method, $params, true);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|list<mixed>
     */
    private function callMethodWithRetry(string $method, array $params, bool $allowRateLimitRetry): array
    {
        $form = array_merge($params, [
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
        ]);

        $startedAt = microtime(true);
        try {
            $httpResponse = Http::timeout(30)
                ->asForm()
                ->post('https://api.vk.com/method/'.$method, $form);
        } catch (ConnectionException $e) {
            $this->recordVkTiming($startedAt);
            throw new VkUnavailableException(
                'VK API временно недоступен (сеть или таймаут).',
                0,
                $e,
            );
        }
        $this->recordVkTiming($startedAt);

        $status = $httpResponse->status();
        if ($status === 429) {
            if ($allowRateLimitRetry) {
                usleep(self::RATE_LIMIT_RETRY_DELAY_MICROSECONDS);

                return $this->callMethodWithRetry($method, $params, false);
            }

            throw new VkRateLimitException(
                'Превышен лимит запросов VK.',
                $method,
                429,
                'HTTP 429',
            );
        }

        if ($status >= 500) {
            throw new VkUnavailableException(
                'VK API временно недоступен (HTTP '.$status.').',
            );
        }

        $data = $httpResponse->json();
        if (! is_array($data)) {
            throw new VkUnavailableException(
                'VK API '.$method.': не удалось разобрать ответ.',
            );
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $err = $data['error'];
            $code = (int) ($err['error_code'] ?? 0);
            $msg = (string) ($err['error_msg'] ?? '');

            if ($allowRateLimitRetry && in_array($code, self::VK_RATE_LIMIT_ERROR_CODES, true)) {
                usleep(self::RATE_LIMIT_RETRY_DELAY_MICROSECONDS);

                return $this->callMethodWithRetry($method, $params, false);
            }

            throw $this->mapVkErrorToException($method, $code, $msg);
        }

        $response = $data['response'] ?? [];

        return is_array($response) ? $response : [];
    }

    private function mapVkErrorToException(string $method, int $code, string $msg): VkApiException
    {
        if (in_array($code, self::VK_RATE_LIMIT_ERROR_CODES, true)) {
            return new VkRateLimitException(
                'Превышен лимит запросов VK.',
                $method,
                $code,
                $msg,
            );
        }

        if (in_array($code, self::VK_GROUP_NOT_FOUND_ERROR_CODES, true)) {
            return new VkGroupNotFoundException(
                'Сообщество не найдено.',
                $method,
                $code,
                $msg,
            );
        }

        if (in_array($code, self::VK_CLOSED_COMMUNITY_ERROR_CODES, true)) {
            return new VkClosedCommunityException(
                'Сообщество закрыто или недоступно для этого токена.',
                $method,
                $code,
                $msg,
            );
        }

        return new VkApiException(
            "VK API {$method} [{$code}]: {$msg}",
            $method,
            $code,
            $msg,
        );
    }

    private function recordVkTiming(float $startedAt): void
    {
        $this->stats?->recordCall((microtime(true) - $startedAt) * 1000);
    }

    private function groupsGetById(): GroupsGetByIdMethod
    {
        return $this->groupsGetByIdMethod ??= new GroupsGetByIdMethod($this);
    }

    private function wallGet(): WallGetMethod
    {
        return $this->wallGetMethod ??= new WallGetMethod($this);
    }
}
