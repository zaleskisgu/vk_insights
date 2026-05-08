<?php

namespace App\Integration\Vk\Support;

/**
 * Счётчик HTTP-запросов к VK API и суммарное время ответа в рамках одного входящего запроса приложения.
 */
final class VkApiCallStats
{
    private int $calls = 0;

    private float $totalMs = 0.0;

    public function recordCall(float $elapsedMs): void
    {
        $this->calls++;
        $this->totalMs += $elapsedMs;
    }

    public function calls(): int
    {
        return $this->calls;
    }

    public function totalMs(): float
    {
        return $this->totalMs;
    }
}
