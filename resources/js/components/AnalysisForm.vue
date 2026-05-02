<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import { readXsrfToken } from '@/csrf.js';

const today = new Date();
const dateToInit = new Date(today.getFullYear(), today.getMonth(), today.getDate());
const dateFromInit = new Date(dateToInit);
dateFromInit.setMonth(dateFromInit.getMonth() - 1);

const community = ref('');
const dateFrom = ref(dateFromInit);
const dateTo = ref(dateToInit);
const errorMessage = ref('');
const loading = ref(false);

const emit = defineEmits(['report']);

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
    const xsrf = readXsrfToken();
    if (!xsrf) {
        errorMessage.value = 'Нет CSRF-куки. Обновите страницу и попробуйте снова.';
        return;
    }

    loading.value = true;
    try {
        const res = await fetch('/report', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                group,
                from: dateFrom.value?.toISOString(),
                to: dateTo.value?.toISOString(),
            }),
        });

        const body = await res.json().catch(() => ({}));

        if (!res.ok) {
            if (res.status === 419) {
                errorMessage.value = 'Сессия устарела (CSRF). Обновите страницу.';
            } else if (typeof body.message === 'string') {
                errorMessage.value = body.message;
            } else if (body.errors && typeof body.errors === 'object') {
                const first = Object.values(body.errors)[0];
                errorMessage.value = Array.isArray(first) ? first[0] : String(first);
            } else {
                errorMessage.value = `Ошибка запроса (${res.status}).`;
            }
            return;
        }

        emit('report', body);
    } catch (e) {
        console.error('[VK Insights] Сеть или парсинг ответа', e);
        errorMessage.value = 'Не удалось выполнить запрос. Проверьте сеть и консоль.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="vk-card">
        <div class="vk-field">
            <label class="vk-field__label" for="community-input"> ID или имя сообщества </label>
            <InputText
                id="community-input"
                v-model="community"
                placeholder="например: durov, vk, или ссылка vk.com/durov"
                autocomplete="off"
                fluid
                @update:model-value="errorMessage = ''"
            />
            <div class="vk-links">
                <button type="button" class="vk-link" @click="fillPreset('durov')">Durov</button>
                <button type="button" class="vk-link" @click="fillPreset('vk')">VK</button>
                <button type="button" class="vk-link" @click="fillPreset('mdk')">MDK</button>
            </div>
        </div>

        <div class="vk-dates">
            <div>
                <label class="vk-field__label" for="date-from"> Начало периода </label>
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
                <label class="vk-field__label" for="date-to"> Конец периода </label>
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
        </div>

        <p v-if="errorMessage" class="vk-form-error" role="alert">
            {{ errorMessage }}
        </p>

        <Button
            label="Анализировать"
            fluid
            :loading="loading"
            :disabled="loading"
            @click="analyze"
        />
    </div>
</template>
