<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import { ruNumber } from '@/utils/dashboardFormat.js';
import { vkWallPostUrl } from '@/utils/vkWallPostUrl.js';

const props = defineProps({
    topPosts: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const rows = computed(() => {
    const top = props.topPosts ?? [];
    const meta = props.meta ?? {};
    return top.map((row) => {
        const href = vkWallPostUrl(meta.owner_id ?? row.owner_id, row.post_id);
        return {
            rank: row.rank,
            engagement: row.engagement ?? 0,
            text: row.text ?? '—',
            date: row.date ?? '',
            likes: row.likes ?? 0,
            comments: row.comments ?? 0,
            postId: row.post_id ?? null,
            href,
        };
    });
});
</script>

<template>
    <Card class="vk-dash-panel vk-dash-panel--top-table">
        <template #title>Топ-10 постов</template>
        <template #content>
            <ul class="vk-top-posts">
                <li
                    v-for="row in rows"
                    :key="row.rank"
                    class="vk-top-posts__item"
                >
                    <component
                        :is="row.href ? 'a' : 'div'"
                        v-bind="row.href
                            ? {
                                href: row.href,
                                target: '_blank',
                                rel: 'noopener noreferrer',
                                'aria-label': `Открыть пост №${row.postId ?? row.rank} от ${row.date} на vk.com в новой вкладке`,
                            }
                            : {}"
                        class="vk-top-posts__row"
                        :class="{ 'vk-top-posts__row--link': !!row.href }"
                    >
                        <span
                            class="vk-top-posts__row-inner"
                            :aria-hidden="row.href ? true : null"
                        >
                            <span class="vk-top-posts__rank">#{{ row.rank }}</span>
                            <div class="vk-top-posts__main">
                                <p class="vk-top-posts__snippet">{{ row.text }}</p>
                                <p v-if="row.date" class="vk-top-posts__date">
                                    <time :datetime="row.date">{{ row.date }}</time>
                                </p>
                            </div>
                            <div class="vk-top-posts__metrics">
                                <span class="vk-top-posts__metric">
                                    <i class="pi pi-heart vk-top-posts__metric-icon" aria-hidden="true" />
                                    <span class="vk-top-posts__metric-value">{{ ruNumber.format(row.likes) }}</span>
                                </span>
                                <span class="vk-top-posts__metric">
                                    <i class="pi pi-comment vk-top-posts__metric-icon" aria-hidden="true" />
                                    <span class="vk-top-posts__metric-value">{{ ruNumber.format(row.comments) }}</span>
                                </span>
                                <span class="vk-top-posts__metric vk-top-posts__metric--score">
                                    <i class="pi pi-star-fill vk-top-posts__metric-icon" aria-hidden="true" />
                                    <span class="vk-top-posts__metric-value">{{ ruNumber.format(row.engagement) }}</span>
                                </span>
                            </div>
                        </span>
                    </component>
                </li>
            </ul>
        </template>
    </Card>
</template>
