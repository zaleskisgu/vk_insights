<?php

namespace App\Integration\Vk;

use App\Contracts\VkClient;
use App\Data\Vk\GroupsGetByIdResultData;
use App\Data\Vk\WallGetResultData;
use App\Integration\Vk\Method\GroupsGetByIdMethod;
use App\Integration\Vk\Method\WallGetMethod;
use Illuminate\Support\Facades\Http;

/**
 * Живой VK API: POST https://api.vk.com/method/{name}, разбор response / error.
 */
class HttpVkClient implements VkClient
{
    private ?GroupsGetByIdMethod $groupsGetByIdMethod = null;

    private ?WallGetMethod $wallGetMethod = null;

    public function __construct(
        private string $accessToken,
        private string $apiVersion,
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
        $form = array_merge($params, [
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
        ]);

        $httpResponse = Http::timeout(30)
            ->asForm()
            ->post('https://api.vk.com/method/'.$method, $form);

        $data = $httpResponse->json();
        if (! is_array($data)) {
            throw new \RuntimeException('VK API '.$method.': не удалось разобрать JSON.');
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $err = $data['error'];
            $code = $err['error_code'] ?? '?';
            $msg = $err['error_msg'] ?? '';

            throw new \RuntimeException("VK API {$method} [{$code}]: {$msg}");
        }

        $response = $data['response'] ?? [];

        return is_array($response) ? $response : [];
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
