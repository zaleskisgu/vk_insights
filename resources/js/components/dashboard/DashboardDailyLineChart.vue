<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import { ruNumber } from '@/utils/dashboardFormat.js';
import {
    chartColors,
    chartLegendBottomLabels,
    chartResponsive,
    chartTickColor,
} from '@/utils/chartTheme.js';

/**
 * Верхняя граница оси: не ниже реальных данных (с запасом factor), округление 1–2–5×10ⁿ.
 */
function niceAxisMax(value, factor = 1.12) {
    const v = Math.max(0, Number(value) || 0);
    if (v === 0) {
        return 1;
    }
    const target = v * factor;
    const exp = Math.floor(Math.log10(target));
    const frac = target / 10 ** exp;
    let niceFrac = 10;
    if (frac <= 1) {
        niceFrac = 1;
    } else if (frac <= 2) {
        niceFrac = 2;
    } else if (frac <= 5) {
        niceFrac = 5;
    }
    return niceFrac * 10 ** exp;
}

const props = defineProps({
    daily: {
        type: Array,
        default: () => [],
    },
});

const lineChartData = computed(() => {
    const daily = props.daily ?? [];
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
    const daily = props.daily ?? [];
    const dayCount = daily.length;
    const maxTicks = Math.min(8, Math.max(4, Math.ceil(dayCount / 12)));

    const maxEng = daily.length
        ? Math.max(...daily.map((row) => Number(row.avg_engagement) || 0))
        : 0;
    const maxPost = daily.length
        ? Math.max(...daily.map((row) => Number(row.posts_count) || 0))
        : 0;
    const yMax = niceAxisMax(maxEng, 1.12);
    const y1Max = niceAxisMax(maxPost, 1.15);

    return {
        ...chartResponsive,
        interaction: { mode: 'index', intersect: false },
        layout: {
            padding: { top: 2, right: 2, bottom: 0, left: 0 },
        },
        plugins: {
            legend: {
                position: 'bottom',
                align: 'center',
                labels: chartLegendBottomLabels({
                    padding: 4,
                    boxWidth: 8,
                    boxHeight: 8,
                }),
            },
        },
        scales: {
            x: {
                ticks: {
                    ...chartTickColor(9),
                    maxRotation: 40,
                    minRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: maxTicks,
                },
                grid: {
                    color: chartColors.gridMuted,
                    borderDash: [2, 4],
                },
                border: { color: chartColors.axisBorder },
            },
            y: {
                position: 'left',
                min: 0,
                max: yMax,
                ticks: {
                    ...chartTickColor(9),
                    maxTicksLimit: 8,
                    callback: (v) => ruNumber.format(v),
                },
                grid: {
                    color: chartColors.gridMuted,
                    borderDash: [2, 4],
                },
                border: { display: false },
            },
            y1: {
                position: 'right',
                min: 0,
                max: y1Max,
                ticks: {
                    ...chartTickColor(9),
                    maxTicksLimit: 8,
                    callback: (v) => ruNumber.format(v),
                },
                grid: { drawOnChartArea: false },
                border: { display: false },
            },
        },
    };
});
</script>

<template>
    <Card class="vk-dash-panel vk-dash-panel--line">
        <template #title>Динамика вовлечённости по дням</template>
        <template #content>
            <div class="vk-chart-wrap vk-chart-wrap--tall">
                <Chart type="line" :data="lineChartData" :options="lineChartOptions" />
            </div>
        </template>
    </Card>
</template>
