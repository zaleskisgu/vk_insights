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
    const dayCount = (props.daily ?? []).length;
    const maxTicks = Math.min(8, Math.max(4, Math.ceil(dayCount / 12)));

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
                max: 10_000,
                ticks: {
                    ...chartTickColor(9),
                    stepSize: 2_500,
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
                max: 36,
                ticks: {
                    ...chartTickColor(9),
                    stepSize: 9,
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
