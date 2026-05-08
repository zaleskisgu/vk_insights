<script setup>
import { computed } from 'vue';
import DashboardProfileCard from '@/components/dashboard/DashboardProfileCard.vue';
import DashboardKpiCards from '@/components/dashboard/DashboardKpiCards.vue';
import DashboardDailyLineChart from '@/components/dashboard/DashboardDailyLineChart.vue';
import DashboardTopBarChart from '@/components/dashboard/DashboardTopBarChart.vue';
import DashboardContentTypesChart from '@/components/dashboard/DashboardContentTypesChart.vue';
import DashboardTopPostsTable from '@/components/dashboard/DashboardTopPostsTable.vue';
import DashboardAllPostsTable from '@/components/dashboard/DashboardAllPostsTable.vue';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
});

const meta = computed(() => props.report.meta ?? {});
const summary = computed(() => props.report.summary ?? {});
const daily = computed(() => props.report.daily ?? []);
const topPosts = computed(() => props.report.top_posts ?? []);
const contentTypes = computed(() => props.report.content_types ?? []);

const isDashboardChartsEmpty = computed(
    () => daily.value.length === 0 && topPosts.value.length === 0,
);

const truncatedNotice = computed(() => {
    if (!meta.value.truncated) {
        return '';
    }
    const limit = Number(meta.value.posts_limit) || 0;
    const limitLabel = limit > 0 ? limit.toLocaleString('ru-RU') : '';
    return limitLabel
        ? `За выбранный период постов больше, чем мы можем загрузить за один запрос. Показаны последние ${limitLabel} постов — для полного отчёта сузьте период.`
        : 'За выбранный период постов больше, чем мы можем загрузить за один запрос. Показаны последние посты — для полного отчёта сузьте период.';
});
</script>

<template>
    <section class="vk-dashboard" aria-labelledby="dashboard-heading">
        <h1 id="dashboard-heading" class="vk-sr-only">
            Аналитика сообщества {{ meta.name }}, период с {{ meta.from }} по {{ meta.to }}
        </h1>
        <DashboardProfileCard :report="report" />
        <DashboardKpiCards :summary="summary" />
        <p
            v-if="truncatedNotice"
            class="vk-dashboard-truncated"
            role="status"
            aria-live="polite"
        >
            {{ truncatedNotice }}
        </p>
        <template v-if="!isDashboardChartsEmpty">
            <DashboardDailyLineChart :daily="daily" />
            <div class="vk-dashboard__row2">
                <DashboardTopBarChart :top-posts="topPosts" />
                <DashboardContentTypesChart :content-types="contentTypes" />
            </div>
            <DashboardTopPostsTable :top-posts="topPosts" :meta="meta" />
            <DashboardAllPostsTable :report="report" />
        </template>
        <p
            v-else
            class="vk-dashboard-empty"
            role="status"
        >
            За выбранный период нет постов для отображения в графиках и таблицах.
        </p>
        <p v-if="meta.generated_at" class="vk-report-generated" role="status">
            Отчёт сгенерирован: {{ meta.generated_at }}
        </p>
    </section>
</template>
