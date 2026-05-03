<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import { fetchReportDashboard, reportClientErrorMessage } from '@/api/reportFetch.js';

const emit = defineEmits(['report']);

const today = new Date();
const dateToInit = new Date(today.getFullYear(), today.getMonth(), today.getDate());
const dateFromInit = new Date(dateToInit);
dateFromInit.setMonth(dateFromInit.getMonth() - 1);

const community = ref('');
const dateFrom = ref(dateFromInit);
const dateTo = ref(dateToInit);
const errorMessage = ref('');
const loading = ref(false);

function fillPreset(screenName) {
    community.value = screenName;
    errorMessage.value = '';
}

async function analyze() {
    errorMessage.value = '';
    const group = community.value.trim();
    if (!group) {
        errorMessage.value = 'Введите ID или имя сообщества.';
        return;
    }
    if (!dateFrom.value || !dateTo.value) {
        errorMessage.value = 'Выберите начало и конец периода.';
        return;
    }
    if (dateFrom.value > dateTo.value) {
        errorMessage.value = 'Начало периода не может быть позже конца.';
        return;
    }

    loading.value = true;
    try {
        const data = await fetchReportDashboard({
            group,
            from: dateFrom.value?.toISOString() ?? '',
            to: dateTo.value?.toISOString() ?? '',
        });
        emit('report', data);
    } catch (e) {
        errorMessage.value = reportClientErrorMessage(
            e,
            'Не удалось выполнить запрос. Проверьте сеть и консоль.',
            '[VK Insights] Сеть или парсинг ответа',
        );
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="vk-start-screen">
        <section class="vk-start-screen__intro" aria-labelledby="start-hero-title">
            <h1 id="start-hero-title" class="vk-hero__title">Аналитика сообществ ВКонтакте</h1>
            <p id="start-hero-desc" class="vk-hero__subtitle">
                Введите ID или короткое имя сообщества и выберите период для анализа постов
            </p>
        </section>
        <form
            class="vk-card vk-start-screen__form"
            aria-labelledby="start-hero-title"
            aria-describedby="start-hero-desc"
            novalidate
            @submit.prevent="analyze"
        >
            <div class="vk-field">
                <label class="vk-field__label" for="community-input">ID или имя сообщества</label>
                <InputText
                    id="community-input"
                    v-model="community"
                    placeholder="например: durov, vk, или ссылка vk.com/durov"
                    autocomplete="off"
                    fluid
                    @update:model-value="errorMessage = ''"
                />
                <p class="vk-links" role="group" aria-label="Быстрый выбор примера сообщества">
                    <button type="button" class="vk-link" @click="fillPreset('durov')">Durov</button>
                    <button type="button" class="vk-link" @click="fillPreset('vk')">VK</button>
                    <button type="button" class="vk-link" @click="fillPreset('mdk')">MDK</button>
                </p>
            </div>

            <fieldset class="vk-dates">
                <legend class="vk-sr-only">Период анализа</legend>
                <div>
                    <label class="vk-field__label" for="date-from">Начало периода</label>
                    <DatePicker
                        v-model="dateFrom"
                        date-format="dd.mm.yy"
                        show-icon
                        icon-display="input"
                        :manual-input="false"
                        fluid
                        input-id="date-from"
                        @update:model-value="errorMessage = ''"
                    />
                </div>
                <div>
                    <label class="vk-field__label" for="date-to">Конец периода</label>
                    <DatePicker
                        v-model="dateTo"
                        date-format="dd.mm.yy"
                        show-icon
                        icon-display="input"
                        :manual-input="false"
                        fluid
                        input-id="date-to"
                        @update:model-value="errorMessage = ''"
                    />
                </div>
            </fieldset>

            <p v-if="errorMessage" id="start-form-error" class="vk-form-error" role="alert" aria-live="polite">
                {{ errorMessage }}
            </p>

            <Button
                label="Анализировать"
                fluid
                :loading="loading"
                :disabled="loading"
                type="submit"
                :aria-describedby="errorMessage ? 'start-form-error' : undefined"
            />
        </form>
    </div>
</template>
