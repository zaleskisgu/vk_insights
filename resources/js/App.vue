<script setup>
import { ref } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AnalysisForm from '@/components/AnalysisForm.vue';
import DashboardView from '@/components/DashboardView.vue';

const report = ref(null);
</script>

<template>
    <div class="vk-shell">
        <AppHeader :show-new-search="!!report" @new-search="report = null" />
        <main class="vk-main" :class="{ 'vk-main--dashboard': report }">
            <template v-if="!report">
                <h1 class="vk-hero__title">Аналитика сообществ ВКонтакте</h1>
                <p class="vk-hero__subtitle">
                    Введите ID или короткое имя сообщества и выберите период для анализа постов
                </p>
                <AnalysisForm @report="report = $event" />
            </template>
            <DashboardView v-else :report="report" />
        </main>
    </div>
</template>
