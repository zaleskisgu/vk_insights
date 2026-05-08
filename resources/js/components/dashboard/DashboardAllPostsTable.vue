<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import { ruNumber } from '@/utils/dashboardFormat.js';
import { reportJsonPost, reportClientErrorMessage } from '@/api/reportFetch.js';
import DashboardPostTextLink from '@/components/dashboard/DashboardPostTextLink.vue';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
});

const meta = computed(() => props.report?.meta ?? {});

const loading = ref(false);
const errorMessage = ref('');
const posts = ref([]);
const totalRecords = ref(0);
const allTotal = ref(0);
const filteredTotal = ref(0);

const first = ref(0);
const rows = ref(25);
const sortField = ref('date');
const sortOrder = ref(-1);
const typeFilter = ref('all');

const searchInput = ref('');
const searchDebounced = ref('');

const typeOptions = [
    { label: 'Все типы', value: 'all' },
    { label: 'Фото', value: 'photo' },
    { label: 'Мульти', value: 'multi' },
    { label: 'Видео', value: 'video' },
    { label: 'Текст', value: 'text' },
    { label: 'Ссылка', value: 'link' },
];

let listAbort = null;
let searchDebounceTimer = null;

const headCountMeta = computed(() => {
    const f = filteredTotal.value;
    const t = allTotal.value;
    if (t <= 0) {
        return '';
    }
    return ` (${f.toLocaleString('ru-RU')} из ${t.toLocaleString('ru-RU')})`;
});

const canQuery = computed(() => !!(meta.value.group_query && meta.value.from && meta.value.to));

async function loadPosts() {
    if (!canQuery.value) {
        errorMessage.value = 'Нет данных запроса. Выполните анализ заново.';
        return;
    }
    if (listAbort) {
        listAbort.abort();
    }
    listAbort = new AbortController();
    const signal = listAbort.signal;

    loading.value = true;
    errorMessage.value = '';
    try {
        const body = {
            group: meta.value.group_query,
            from: meta.value.from,
            to: meta.value.to,
            page: Math.floor(first.value / rows.value) + 1,
            per_page: rows.value,
            sort: sortField.value ?? 'date',
            order: sortOrder.value === 1 ? 'asc' : 'desc',
            type: typeFilter.value,
        };
        const q = searchDebounced.value.trim();
        if (q) {
            body.q = q;
        }
        const res = await reportJsonPost('/report/posts', body, { signal });
        posts.value = Array.isArray(res.data) ? res.data : [];
        const m = res.meta ?? {};
        totalRecords.value = Number(m.filtered) || 0;
        allTotal.value = Number(m.total) || 0;
        filteredTotal.value = Number(m.filtered) || 0;
        if (m.page != null && m.per_page != null) {
            first.value = (Number(m.page) - 1) * Number(m.per_page);
        }
    } catch (e) {
        if (e?.name === 'AbortError') {
            return;
        }
        errorMessage.value = reportClientErrorMessage(
            e,
            'Не удалось загрузить посты.',
            '[VK Insights] Таблица постов',
        );
        posts.value = [];
        totalRecords.value = 0;
    } finally {
        loading.value = false;
    }
}

function onPage(ev) {
    first.value = ev.first;
    rows.value = ev.rows;
    loadPosts();
}

function onSort(ev) {
    first.value = ev.first;
    rows.value = ev.rows;
    if (ev.sortField != null) {
        sortField.value = ev.sortField;
    }
    if (ev.sortOrder != null) {
        sortOrder.value = ev.sortOrder;
    }
    loadPosts();
}

watch(searchInput, (v) => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        searchDebounced.value = v ?? '';
        first.value = 0;
        loadPosts();
    }, 300);
});

watch(typeFilter, () => {
    first.value = 0;
    loadPosts();
});

watch(
    () => [meta.value.group_query, meta.value.from, meta.value.to],
    () => {
        clearTimeout(searchDebounceTimer);
        first.value = 0;
        searchInput.value = '';
        searchDebounced.value = '';
        typeFilter.value = 'all';
        sortField.value = 'date';
        sortOrder.value = -1;
        loadPosts();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    clearTimeout(searchDebounceTimer);
    if (listAbort) {
        listAbort.abort();
    }
});
</script>

<template>
    <section
        class="vk-all-posts"
        aria-labelledby="all-posts-heading"
        :aria-busy="loading"
    >
        <Card class="vk-dash-panel vk-dash-panel--all-posts">
            <template #content>
                <div class="vk-all-posts__head">
                    <p id="all-posts-heading" class="vk-all-posts__head-title" role="heading" aria-level="2">
                        <span class="vk-all-posts__head-title-main">Все посты</span><span
                            class="vk-all-posts__head-title-meta"
                        >{{ headCountMeta }}</span>
                    </p>
                    <div class="vk-all-posts__toolbar">
                        <InputText
                            v-model="searchInput"
                            class="vk-all-posts__search"
                            input-id="posts-search"
                            placeholder="Поиск по тексту..."
                            aria-label="Поиск по тексту постов"
                            autocomplete="off"
                        />
                        <Select
                            v-model="typeFilter"
                            class="vk-all-posts__type-filter"
                            panel-class="vk-all-posts__select-panel"
                            input-id="posts-type-filter"
                            :options="typeOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Все типы"
                            aria-label="Фильтр по типу контента"
                        />
                    </div>
                </div>
                <p v-if="errorMessage" class="vk-all-posts__error" role="alert" aria-live="polite">
                    {{ errorMessage }}
                </p>
                <DataTable
                    class="vk-all-posts__table"
                    :value="posts"
                    lazy
                    paginator
                    :rows="rows"
                    :first="first"
                    :total-records="totalRecords"
                    :loading="loading"
                    data-key="post_id"
                    :rows-per-page-options="[10, 25, 50, 100]"
                    paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    current-page-report-template="{first}–{last} из {totalRecords}"
                    :sort-field="sortField"
                    :sort-order="sortOrder"
                    sort-mode="single"
                    show-gridlines
                    @page="onPage"
                    @sort="onSort"
                >
                    <Column field="row_index" header="#" style="width: 2.75rem" />
                    <Column field="date" header="Дата" sortable style="width: 6.5rem">
                        <template #body="{ data }">
                            <time v-if="data.date" :datetime="data.date">{{ data.date }}</time>
                            <span v-else>—</span>
                        </template>
                    </Column>
                    <Column field="type" header="Тип" sortable style="width: 5.5rem">
                        <template #body="{ data }">
                            <span class="vk-all-posts__type-badge">{{ data.label ?? data.type }}</span>
                        </template>
                    </Column>
                    <Column
                        field="text"
                        header="Текст"
                        sortable
                        style="width: 32%"
                        body-class="vk-all-posts__text-col"
                    >
                        <template #body="{ data }">
                            <DashboardPostTextLink
                                :owner-id="meta.owner_id ?? data.owner_id"
                                :post-id="data.post_id"
                                :text="data.text ?? ''"
                            />
                        </template>
                    </Column>
                    <Column field="likes" header="Лайки" sortable style="width: 6rem">
                        <template #body="{ data }">
                            <span class="vk-all-posts__metric">
                                <i class="pi pi-heart vk-all-posts__metric-icon" aria-hidden="true" />
                                {{ ruNumber.format(data.likes ?? 0) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="comments" header="Коммент." sortable style="width: 6.25rem">
                        <template #body="{ data }">
                            <span class="vk-all-posts__metric">
                                <i class="pi pi-comment vk-all-posts__metric-icon" aria-hidden="true" />
                                {{ ruNumber.format(data.comments ?? 0) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="reposts" header="Репосты" sortable style="width: 6.25rem">
                        <template #body="{ data }">
                            <span class="vk-all-posts__metric">
                                <i class="pi pi-share-alt vk-all-posts__metric-icon" aria-hidden="true" />
                                {{ ruNumber.format(data.reposts ?? 0) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="engagement" header="Engagement" sortable style="width: 7rem">
                        <template #body="{ data }">
                            <span class="vk-all-posts__metric vk-all-posts__metric--engagement">
                                <i class="pi pi-star-fill vk-all-posts__metric-icon" aria-hidden="true" />
                                {{ ruNumber.format(data.engagement ?? 0) }}
                            </span>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </section>
</template>
