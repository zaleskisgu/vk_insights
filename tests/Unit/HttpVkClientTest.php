<?php

namespace Tests\Unit;

use App\Integration\Vk\HttpVkClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpVkClientTest extends TestCase
{
    public function test_get_group_by_id_empty_token(): void
    {
        $client = new HttpVkClient('', '5.199');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('пустой VK service token');
        $client->getGroupById(1);
    }

    public function test_get_group_by_id_maps_vk_response(): void
    {
        Http::fake([
            'api.vk.com/method/groups.getById' => Http::response([
                'response' => [
                    [
                        'id' => 42,
                        'name' => 'Test',
                        'screen_name' => 'testclub',
                        'is_closed' => 0,
                        'type' => 'group',
                        'photo_200' => 'https://example.com/p.jpg',
                        'members_count' => 999,
                    ],
                ],
            ], 200),
        ]);

        $client = new HttpVkClient('secret', '5.199');
        $r = $client->getGroupById(42);

        $this->assertSame(42, $r['groups'][0]['id']);
        $this->assertSame('Test', $r['groups'][0]['name']);
        $this->assertSame('testclub', $r['groups'][0]['screen_name']);
        $this->assertSame(999, $r['groups'][0]['members_count']);
        $this->assertSame('https://example.com/p.jpg', $r['groups'][0]['photo_200']);
    }

    public function test_call_method_surfaces_vk_error(): void
    {
        Http::fake([
            'api.vk.com/method/groups.getById' => Http::response([
                'error' => [
                    'error_code' => 5,
                    'error_msg' => 'User authorization failed',
                ],
            ], 200),
        ]);

        $client = new HttpVkClient('bad', '5.199');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VK API groups.getById [5]: User authorization failed');
        $client->getGroupById(1);
    }
}
