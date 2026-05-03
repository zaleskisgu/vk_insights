<script setup>
import Card from 'primevue/card';
import { ruNumber } from '@/utils/dashboardFormat.js';

defineProps({
    summary: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="vk-dashboard__cards" aria-label="Ключевые показатели за период">
        <Card class="vk-dash-card">
            <template #title>Всего постов</template>
            <template #content>
                <p class="vk-dash-card__value">{{ ruNumber.format(summary.total_posts ?? 0) }}</p>
            </template>
        </Card>
        <Card class="vk-dash-card">
            <template #title>Ср. вовлечённость</template>
            <template #content>
                <p class="vk-dash-card__value">{{ ruNumber.format(summary.avg_engagement ?? 0) }}</p>
                <p class="vk-dash-card__hint">лайки + коммент. + репосты</p>
            </template>
        </Card>
        <Card class="vk-dash-card">
            <template #title>Самый активный день</template>
            <template #content>
                <p class="vk-dash-card__value">{{ summary.most_active_day?.date }}</p>
                <p class="vk-dash-card__hint">{{ summary.most_active_day?.posts }} постов</p>
            </template>
        </Card>
        <Card class="vk-dash-card">
            <template #title>Макс. engagement</template>
            <template #content>
                <p class="vk-dash-card__value">{{ ruNumber.format(summary.max_engagement?.value ?? 0) }}</p>
                <p class="vk-dash-card__hint">{{ summary.max_engagement?.date }}</p>
            </template>
        </Card>
    </section>
</template>
