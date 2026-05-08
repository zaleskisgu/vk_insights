<?php

namespace Tests\Integration;

use App\Integration\Vk\HttpVkClient;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Живые запросы к api.vk.com. Нужны {@code VK_SERVICE_TOKEN} и сеть.
 *
 * ID сообщества по умолчанию — публичная страница VK (можно переопределить {@code VK_INTEGRATION_TEST_GROUP_ID}).
 */
class VkHttpClientIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Сброс Http::fake() из юнит-тестов в том же процессе PHPUnit.
        Http::swap(new HttpFactory);
    }

    public function test_groups_get_by_id_and_wall_get_return_expected_shape(): void
    {
        $token = (string) config('vk.access_token', '');
        if ($token === '') {
            $this->markTestSkipped('Укажите VK_SERVICE_TOKEN в .env для интеграционного теста VK.');
        }

        $groupId = (int) (config('vk.integration_test_group_id') ?: 22822305);

        $client = new HttpVkClient($token, (string) config('vk.version', '5.199'));

        $groupPayload = $client->getGroupById($groupId);

        $this->assertArrayHasKey('groups', $groupPayload);
        $this->assertNotEmpty($groupPayload['groups']);
        $first = $groupPayload['groups'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertSame($groupId, (int) $first['id']);
        $this->assertArrayHasKey('name', $first);
        $this->assertNotSame('', (string) $first['name']);

        $wallPayload = $client->getWall(-$groupId, 5, 0);

        $this->assertArrayHasKey('count', $wallPayload);
        $this->assertArrayHasKey('items', $wallPayload);
        $this->assertIsArray($wallPayload['items']);
        $this->assertLessThanOrEqual(5, count($wallPayload['items']));
        if (count($wallPayload['items']) > 0) {
            $post = $wallPayload['items'][0];
            $this->assertArrayHasKey('id', $post);
            $this->assertArrayHasKey('owner_id', $post);
        }
    }
}
