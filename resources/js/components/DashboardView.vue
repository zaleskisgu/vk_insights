<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chart from 'primevue/chart';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
});

const nf = new Intl.NumberFormat('ru-RU');

function formatPeriod(from, to) {
    const opts = { day: '2-digit', month: '2-digit', year: 'numeric' };
    const a = new Date(from + 'T12:00:00');
    const b = new Date(to + 'T12:00:00');
    return `${a.toLocaleDateString('ru-RU', opts)} — ${b.toLocaleDateString('ru-RU', opts)}`;
}

const lineChartData = computed(() => {
    const daily = props.report.daily ?? [];
    return {
        labels: daily.map((row) => row.date),
        datasets: [
            {
                label: 'Ср. engagement',
                data: daily.map((row) => row.avg_engagement),
                borderColor: '#4c75a3',
                backgroundColor: 'rgba(76, 117, 163, 0.12)',
                tension: 0.35,
                fill: false,
                yAxisID: 'y',
                pointRadius: 0,
                borderWidth: 1.5,
            },
            {
                label: 'Кол-во постов',
                data: daily.map((row) => row.posts_count),
                borderColor: '#22c55e',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0.15,
                fill: false,
                yAxisID: 'y1',
                pointRadius: 0,
                borderWidth: 1.5,
            },
        ],
    };
});

const lineChartOptions = computed(() => {
    const dayCount = (props.report.daily ?? []).length;
    const maxTicks = Math.min(8, Math.max(4, Math.ceil(dayCount / 12)));

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: {
            padding: { top: 2, right: 2, bottom: 0, left: 0 },
        },
        plugins: {
            legend: {
                position: 'bottom',
                align: 'center',
                labels: {
                    color: '#d4d4d4',
                    font: { size: 10 },
                    usePointStyle: true,
                    padding: 4,
                    boxWidth: 8,
                    boxHeight: 8,
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: '#a3a3a3',
                    font: { size: 9 },
                    maxRotation: 40,
                    minRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: maxTicks,
                },
                grid: {
                    color: 'rgba(255,255,255,0.07)',
                    borderDash: [2, 4],
                },
                border: { color: 'rgba(255,255,255,0.12)' },
            },
            y: {
                position: 'left',
                min: 0,
                max: 10_000,
                ticks: {
                    color: '#a3a3a3',
                    font: { size: 9 },
                    stepSize: 2_500,
                    callback: (v) => nf.format(v),
                },
                grid: {
                    color: 'rgba(255,255,255,0.07)',
                    borderDash: [2, 4],
                },
                border: { display: false },
            },
            y1: {
                position: 'right',
                min: 0,
                max: 36,
                ticks: {
                    color: '#a3a3a3',
                    font: { size: 9 },
                    stepSize: 9,
                    callback: (v) => nf.format(v),
                },
                grid: { drawOnChartArea: false },
                border: { display: false },
            },
        },
    };
});

const barChartData = computed(() => {
    const top = props.report.top_posts ?? [];
    return {
        labels: top.map((row) => `#${row.rank}`),
        datasets: [
            {
                label: 'Engagement',
                data: top.map((row) => row.engagement),
                backgroundColor: top.map((_, i) =>
                    i === 0 ? '#4c75a3' : 'rgba(76, 117, 163, 0.45)',
                ),
                borderRadius: 4,
            },
        ],
    };
});

const barChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: { top: 2, right: 4, bottom: 2, left: 4 },
    },
    datasets: {
        bar: {
            categoryPercentage: 0.92,
            barPercentage: 0.82,
        },
    },
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            ticks: { color: '#a3a3a3', font: { size: 10 }, padding: 2 },
            grid: { display: false },
        },
        y: {
            ticks: {
                color: '#a3a3a3',
                font: { size: 10 },
                padding: 4,
                callback: (v) => (v >= 1000 ? `${(v / 1000).toFixed(v % 1000 === 0 ? 0 : 1)}k` : v),
            },
            grid: { color: 'rgba(255,255,255,0.06)' },
        },
    },
}));

const doughnutData = computed(() => {
    const types = props.report.content_types ?? [];
    return {
        labels: types.map((t) => t.label),
        datasets: [
            {
                data: types.map((t) => t.count),
                backgroundColor: ['#4c75a3', '#22c55e', '#eab308'],
                borderWidth: 0,
            },
        ],
    };
});

const doughnutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '56%',
    layout: {
        padding: { top: 4, right: 4, bottom: 2, left: 4 },
    },
    plugins: {
        legend: {
            position: 'bottom',
            align: 'center',
            labels: {
                color: '#d4d4d4',
                font: { size: 10 },
                boxWidth: 10,
                boxHeight: 10,
                padding: 8,
                usePointStyle: true,
            },
        },
    },
}));

const meta = computed(() => props.report.meta ?? {});
const summary = computed(() => props.report.summary ?? {});
</script>

<template>
    <div class="vk-dashboard">
        <div class="vk-dash-profile-card">
            <div class="vk-dash-profile">
                <div class="vk-dash-profile__body">
                    <div class="vk-dash-profile__identity">
                        <img
                            v-if="meta.photo_200"
                            :src="meta.photo_200"
                            alt=""
                            class="vk-dash-profile__avatar"
                            width="56"
                            height="56"
                        />
                        <div v-else class="vk-dash-profile__avatar vk-dash-profile__avatar--placeholder" aria-hidden="true" />
                        <div class="vk-dash-profile__text">
                            <h1 class="vk-dash-profile__name">{{ meta.name }}</h1>
                            <p class="vk-dash-profile__line2">
                                <span class="vk-dash-profile__at">@{{ meta.screen_name }}</span>
                                <span class="vk-dash-profile__dot" aria-hidden="true">·</span>
                                <span>{{ nf.format(meta.members_count ?? 0) }} подписчиков</span>
                            </p>
                            <p class="vk-dash-profile__period">Период: {{ formatPeriod(meta.from, meta.to) }}</p>
                        </div>
                    </div>
                    <Button severity="secondary" disabled class="vk-dash-profile__export">
                        <span class="vk-dash-profile__export-inner">
                            <i class="pi pi-download" aria-hidden="true" />
                            <span>Экспорт</span>
                            <i class="pi pi-angle-down" aria-hidden="true" />
                        </span>
                    </Button>
                </div>
            </div>
        </div>

        <div class="vk-dashboard__cards">
            <Card class="vk-dash-card">
                <template #title>Всего постов</template>
                <template #content>
                    <p class="vk-dash-card__value">{{ nf.format(summary.total_posts ?? 0) }}</p>
                </template>
            </Card>
            <Card class="vk-dash-card">
                <template #title>Ср. вовлечённость</template>
                <template #content>
                    <p class="vk-dash-card__value">{{ nf.format(summary.avg_engagement ?? 0) }}</p>
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
                    <p class="vk-dash-card__value">{{ nf.format(summary.max_engagement?.value ?? 0) }}</p>
                    <p class="vk-dash-card__hint">{{ summary.max_engagement?.date }}</p>
                </template>
            </Card>
        </div>

        <Card class="vk-dash-panel vk-dash-panel--line">
            <template #title>Динамика вовлечённости по дням</template>
            <template #content>
                <div class="vk-chart-wrap vk-chart-wrap--tall">
                    <Chart type="line" :data="lineChartData" :options="lineChartOptions" />
                </div>
            </template>
        </Card>

        <div class="vk-dashboard__row2">
            <Card class="vk-dash-panel">
                <template #title>Топ-10 постов по engagement</template>
                <template #content>
                    <div class="vk-chart-wrap vk-chart-wrap--compact">
                        <Chart type="bar" :data="barChartData" :options="barChartOptions" />
                    </div>
                </template>
            </Card>
            <Card class="vk-dash-panel">
                <template #title>Распределение типов контента</template>
                <template #content>
                    <div class="vk-chart-wrap vk-chart-wrap--compact">
                        <Chart type="doughnut" :data="doughnutData" :options="doughnutOptions" />
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
