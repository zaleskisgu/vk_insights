/** Shared Chart.js palette for dashboard charts (dark UI). */
export const chartColors = {
    tick: '#a3a3a3',
    legend: '#d4d4d4',
    gridMuted: 'rgba(255,255,255,0.07)',
    gridBarY: 'rgba(255,255,255,0.06)',
    axisBorder: 'rgba(255,255,255,0.12)',
    tooltipBg: 'rgba(42, 42, 42, 0.96)',
    tooltipBody: '#e5e5e5',
    tooltipFooterAccent: '#6c9fe8',
    tooltipBorder: 'rgba(255, 255, 255, 0.12)',
    barFirst: '#4c75a3',
    barRest: 'rgba(76, 117, 163, 0.45)',
    barHover: '#6c9fe8',
    barDim: 'rgba(76, 117, 163, 0.22)',
    barHoverBand: 'rgba(0, 0, 0, 0.28)',
};

export const chartResponsive = {
    responsive: true,
    maintainAspectRatio: false,
};

/**
 * @param {Record<string, unknown>} [extra] merged into legend.labels
 */
export function chartLegendBottomLabels(extra = {}) {
    return {
        color: chartColors.legend,
        font: { size: 10 },
        usePointStyle: true,
        ...extra,
    };
}

/**
 * @param {number} fontSize
 */
export function chartTickColor(fontSize) {
    return {
        color: chartColors.tick,
        font: { size: fontSize },
    };
}

/**
 * Базовые опции встроенного tooltip Chart.js (без callbacks — их задаёт график).
 * @param {Record<string, unknown>} [overrides] поверх базовых полей
 */
export function chartTooltipPluginOptions(overrides = {}) {
    return {
        displayColors: false,
        backgroundColor: chartColors.tooltipBg,
        titleColor: chartColors.tick,
        bodyColor: chartColors.tooltipBody,
        footerColor: chartColors.tooltipFooterAccent,
        borderColor: chartColors.tooltipBorder,
        borderWidth: 1,
        padding: 12,
        titleFont: { size: 11, weight: '400' },
        bodyFont: { size: 13 },
        footerFont: { size: 12, weight: '600' },
        ...overrides,
    };
}
