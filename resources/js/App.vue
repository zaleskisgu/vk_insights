<script setup>
import AppHeader from '@/components/AppHeader.vue';
import AnalysisForm from '@/components/AnalysisForm.vue';
</script>

<template>
    <div class="vk-shell">
        <AppHeader />
        <main class="vk-main">
            <h1 class="vk-hero__title">Аналитика сообществ ВКонтакте</h1>
            <p class="vk-hero__subtitle">
                Введите ID или короткое имя сообщества и выберите период для анализа постов
            </p>
            <AnalysisForm />
        </main>
    </div>
</template>
