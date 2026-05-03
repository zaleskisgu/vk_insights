<script setup>
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { formatPeriodRu, ruNumber } from '@/utils/dashboardFormat.js';
import { reportExportDownload, reportClientErrorMessage, triggerBrowserDownload } from '@/api/reportFetch.js';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
});

const meta = computed(() => props.report.meta ?? {});

const exporting = ref(null);
const exportError = ref('');
const exportMenuRef = ref();

const exportMenuItems = computed(() => {
    const busy = !!exporting.value;
    return [
        {
            label: 'Скачать CSV',
            icon: 'pi pi-table',
            disabled: busy,
            command: () => {
                void runExport('csv');
            },
        },
        {
            label: 'Скачать JSON',
            icon: 'pi pi-file',
            disabled: busy,
            command: () => {
                void runExport('json');
            },
        },
    ];
});

function toggleExportMenu(event) {
    if (exporting.value) {
        return;
    }
    exportMenuRef.value?.toggle(event);
}

async function runExport(format) {
    if (exporting.value) {
        return;
    }
    const m = meta.value;
    const group = typeof m.group_query === 'string' ? m.group_query : '';
    const from = m.from;
    const to = m.to;
    if (!group || !from || !to) {
        exportError.value = 'Недостаточно данных для экспорта.';
        return;
    }

    exportError.value = '';
    exporting.value = format;
    try {
        const { blob, filename } = await reportExportDownload(format, {
            group,
            from,
            to,
        });
        triggerBrowserDownload(blob, filename);
    } catch (e) {
        exportError.value = reportClientErrorMessage(e, 'Не удалось скачать файл.');
    } finally {
        exporting.value = null;
    }
}
</script>

<template>
    <section class="vk-dash-profile-card" aria-label="Сводка по сообществу">
        <div class="vk-dash-profile">
            <div class="vk-dash-profile__body">
                <div class="vk-dash-profile__identity">
                    <img
                        v-if="meta.photo_200"
                        :src="meta.photo_200"
                        :alt="`Аватар: ${meta.name}`"
                        class="vk-dash-profile__avatar"
                        width="56"
                        height="56"
                    />
                    <div v-else class="vk-dash-profile__avatar vk-dash-profile__avatar--placeholder" aria-hidden="true" />
                    <div class="vk-dash-profile__text">
                        <h2 class="vk-dash-profile__name">{{ meta.name }}</h2>
                        <p class="vk-dash-profile__line2">
                            <span class="vk-dash-profile__at">@{{ meta.screen_name }}</span>
                            <span class="vk-dash-profile__dot" aria-hidden="true">·</span>
                            <span>{{ ruNumber.format(meta.members_count ?? 0) }} подписчиков</span>
                        </p>
                        <p class="vk-dash-profile__period">
                            Период: {{ formatPeriodRu(meta.from, meta.to) }}
                        </p>
                    </div>
                </div>
                <div class="vk-dash-profile__export-wrap">
                    <div class="vk-dash-profile__export">
                        <Button
                            type="button"
                            severity="secondary"
                            icon="pi pi-angle-down"
                            icon-pos="right"
                            :label="exporting ? 'Скачивание…' : 'Экспорт'"
                            :disabled="!!exporting"
                            :loading="!!exporting"
                            :aria-busy="exporting ? 'true' : 'false'"
                            aria-haspopup="menu"
                            aria-controls="vk-export-menu"
                            :aria-label="exporting ? 'Идёт подготовка файла экспорта' : 'Экспорт отчёта: выберите CSV или JSON'"
                            @click="toggleExportMenu"
                        />
                        <Menu
                            id="vk-export-menu"
                            ref="exportMenuRef"
                            :model="exportMenuItems"
                            :popup="true"
                            append-to="body"
                            class="vk-dash-profile__export-menu"
                        />
                    </div>
                    <p
                        v-if="exportError"
                        class="vk-dash-profile__export-error"
                        role="alert"
                        aria-live="polite"
                    >
                        {{ exportError }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
