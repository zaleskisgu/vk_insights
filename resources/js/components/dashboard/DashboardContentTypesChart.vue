<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import { chartLegendBottomLabels, chartResponsive } from '@/utils/chartTheme.js';

const props = defineProps({
    contentTypes: {
        type: Array,
        default: () => [],
    },
});

const doughnutData = computed(() => {
    const types = props.contentTypes ?? [];
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
    ...chartResponsive,
    cutout: '56%',
    layout: {
        padding: { top: 4, right: 4, bottom: 2, left: 4 },
    },
    plugins: {
        legend: {
            position: 'bottom',
            align: 'center',
            labels: chartLegendBottomLabels({
                boxWidth: 10,
                boxHeight: 10,
                padding: 8,
            }),
        },
    },
}));
</script>

<template>
    <Card class="vk-dash-panel">
        <template #title>Распределение типов контента</template>
        <template #content>
            <div class="vk-chart-wrap vk-chart-wrap--compact">
                <Chart type="doughnut" :data="doughnutData" :options="doughnutOptions" />
            </div>
        </template>
    </Card>
</template>
