<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import { ruNumber } from '@/utils/dashboardFormat.js';
import {
    chartColors,
    chartResponsive,
    chartTickColor,
    chartTooltipPluginOptions,
} from '@/utils/chartTheme.js';

const nf = ruNumber;

function repostsCount(row) {
    const e = Number(row?.engagement ?? 0);
    const l = Number(row?.likes ?? 0);
    const c = Number(row?.comments ?? 0);
    return Math.max(0, e - l - c);
}

function postId(row, index) {
    if (row?.post_id != null) return row.post_id;
    return 47_300_000 + index * 19_237;
}

function defaultBarColors(count) {
    return Array.from({ length: count }, (_, i) => (i === 0 ? chartColors.barFirst : chartColors.barRest));
}

function hoverBarColors(count, hoverIdx) {
    return Array.from({ length: count }, (_, i) =>
        i === hoverIdx ? chartColors.barHover : chartColors.barDim,
    );
}

function createBarHoverPlugin(getPosts) {
    return {
        id: 'vkTopBarHover',
        afterEvent(chart, args) {
            const native = args.event?.native ?? args.event;
            if (!native || chart.config.type !== 'bar') return;

            const count = getPosts().length;
            if (!count) return;

            const inArea = args.inChartArea !== false;
            let nextIdx;

            if (inArea && (native.type === 'mousemove' || native.type === 'pointermove')) {
                const found = chart.getElementsAtEventForMode(native, 'nearest', { intersect: true }, false);
                nextIdx = found?.length ? found[0].index : null;
            } else if (
                !inArea
                || native.type === 'mouseout'
                || native.type === 'mouseleave'
                || native.type === 'pointerout'
                || native.type === 'pointerleave'
            ) {
                nextIdx = null;
            } else {
                return;
            }

            if (chart.$vkBarHoverIdx === nextIdx) return;
            chart.$vkBarHoverIdx = nextIdx;

            const ds = chart.data?.datasets?.[0];
            if (!ds) return;

            const colors = nextIdx === null ? defaultBarColors(count) : hoverBarColors(count, nextIdx);
            const same =
                Array.isArray(ds.backgroundColor)
                && ds.backgroundColor.length === colors.length
                && ds.backgroundColor.every((v, i) => v === colors[i]);
            if (same) return;

            ds.backgroundColor = colors;
            chart.update('none');
        },
    };
}

function createBarBandPlugin() {
    return {
        id: 'vkTopBarBand',
        beforeDatasetsDraw(chart) {
            const idx = chart.$vkBarHoverIdx;
            if (idx == null || chart.config.type !== 'bar') return;

            const meta = chart.getDatasetMeta(0);
            const el = meta?.data?.[idx];
            if (!el || typeof el.getProps !== 'function') return;

            const { ctx, chartArea } = chart;
            const { x, width } = el.getProps(['x', 'width'], true);
            const pad = 6;
            const left = x - width / 2 - pad;
            const w = width + pad * 2;

            ctx.save();
            ctx.fillStyle = chartColors.barHoverBand;
            ctx.fillRect(left, chartArea.top, w, chartArea.bottom - chartArea.top);
            ctx.restore();
        },
    };
}

const props = defineProps({
    topPosts: {
        type: Array,
        default: () => [],
    },
});

const barChartData = computed(() => {
    const top = props.topPosts ?? [];
    const n = top.length;
    return {
        labels: top.map((row) => `#${row.rank}`),
        datasets: [
            {
                label: 'Engagement',
                data: top.map((row) => row.engagement),
                backgroundColor: defaultBarColors(n),
                borderRadius: 4,
            },
        ],
    };
});

const chartPlugins = computed(() => {
    const getPosts = () => props.topPosts ?? [];
    return [createBarHoverPlugin(getPosts), createBarBandPlugin()];
});

const barChartOptions = computed(() => {
    const getPosts = () => props.topPosts ?? [];
    const top = getPosts();
    const maxEng = top.length ? Math.max(...top.map((r) => Number(r.engagement) || 0), 1) : 1;
    const padded = maxEng * 1.12;
    const step = padded <= 12_000 ? 2_500 : padded <= 40_000 ? 5_000 : 10_000;
    const yMax = Math.max(step, Math.ceil(padded / step) * step);

    return {
        ...chartResponsive,
        interaction: {
            mode: 'nearest',
            intersect: true,
        },
        hover: {
            mode: 'nearest',
            intersect: true,
        },
        layout: {
            padding: { top: 10, right: 4, bottom: 2, left: 4 },
        },
        datasets: {
            bar: {
                categoryPercentage: 0.92,
                barPercentage: 0.82,
            },
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                ...chartTooltipPluginOptions(),
                callbacks: {
                    title(items) {
                        if (!items?.length) return '';
                        const row = getPosts()[items[0].dataIndex];
                        return row?.date ?? '';
                    },
                    beforeBody(items) {
                        if (!items?.length) return '';
                        const i = items[0].dataIndex;
                        const row = getPosts()[i] ?? {};
                        return `Пост #${postId(row, i)}`;
                    },
                    label(item) {
                        const row = getPosts()[item.dataIndex] ?? {};
                        const rep = repostsCount(row);
                        return [
                            `Лайки: ${nf.format(row.likes ?? 0)}`,
                            `Комментарии: ${nf.format(row.comments ?? 0)}`,
                            `Репосты: ${nf.format(rep)}`,
                        ];
                    },
                    footer(items) {
                        if (!items?.length) return '';
                        const row = getPosts()[items[0].dataIndex] ?? {};
                        return `Engagement: ${nf.format(row.engagement ?? 0)}`;
                    },
                },
            },
        },
        scales: {
            x: {
                ticks: { ...chartTickColor(10), padding: 2 },
                grid: { display: false },
            },
            y: {
                min: 0,
                max: yMax,
                ticks: {
                    ...chartTickColor(10),
                    padding: 4,
                    stepSize: step,
                    callback: (v) => (v >= 1000 ? `${(v / 1000).toFixed(v % 1000 === 0 ? 0 : 1)}k` : v),
                },
                grid: { color: chartColors.gridBarY },
            },
        },
    };
});
</script>

<template>
    <Card class="vk-dash-panel">
        <template #title>Топ-10 постов по engagement</template>
        <template #content>
            <div class="vk-chart-wrap vk-chart-wrap--compact">
                <Chart type="bar" :data="barChartData" :options="barChartOptions" :plugins="chartPlugins" />
            </div>
        </template>
    </Card>
</template>
