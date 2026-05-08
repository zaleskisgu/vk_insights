<script setup>
import { ref, watch, defineAsyncComponent, onMounted, onUnmounted } from 'vue';
import AppHeader from '@/components/layout/AppHeader.vue';
import StartScreen from '@/screens/StartScreen.vue';
import { fetchReportDashboard } from '@/api/reportFetch.js';
import { formatPeriodRu } from '@/utils/dashboardFormat.js';
import { pushPathWithoutQuery, readReportQuery, replacePathWithoutQuery } from '@/utils/reportUrl.js';

const DashboardScreen = defineAsyncComponent(() => import('@/screens/DashboardScreen.vue'));

const report = ref(null);
const bootstrapping = ref(false);

async function syncReportFromUrl() {
    const q = readReportQuery();
    if (!q) {
        report.value = null;
        return;
    }
    try {
        report.value = await fetchReportDashboard(q);
    } catch {
        report.value = null;
        replacePathWithoutQuery();
    }
}

function onPopState() {
    syncReportFromUrl();
}

onMounted(async () => {
    if (readReportQuery()) {
        bootstrapping.value = true;
        await syncReportFromUrl();
        bootstrapping.value = false;
    }
    window.addEventListener('popstate', onPopState);
});

onUnmounted(() => {
    window.removeEventListener('popstate', onPopState);
});

function onNewSearch() {
    report.value = null;
    pushPathWithoutQuery();
}

const TITLE_START = 'VK Insights';

watch(
    report,
    (r) => {
        if (!r) {
            document.title = TITLE_START;
            return;
        }
        const m = r.meta ?? {};
        const name = typeof m.name === 'string' && m.name.trim() ? m.name.trim() : String(m.group_query ?? 'Сообщество');
        const from = m.from;
        const to = m.to;
        const period = from && to ? formatPeriodRu(String(from), String(to)) : '';
        document.title = period ? `${TITLE_START} - ${name} - ${period}` : `${TITLE_START} - ${name}`;
    },
    { immediate: true },
);
</script>

<template>
    <div class="vk-shell">
        <AppHeader :show-new-search="!!report && !bootstrapping" @new-search="onNewSearch" />
        <main id="vk-main" class="vk-main" :class="{ 'vk-main--dashboard': report }" tabindex="-1">
            <p v-if="bootstrapping" class="vk-dashboard-loading" role="status">Загрузка отчёта…</p>
            <StartScreen v-else-if="!report" @report="report = $event" />
            <Suspense v-else>
                <DashboardScreen :report="report" />
                <template #fallback>
                    <p class="vk-dashboard-loading" role="status">Загрузка отчёта…</p>
                </template>
            </Suspense>
        </main>
    </div>
</template>
