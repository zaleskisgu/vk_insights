<script setup>
defineProps({
    showNewSearch: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['new-search']);
</script>

<template>
    <header class="vk-header">
        <div class="vk-header__inner">
            <a class="vk-header__brand" href="/" title="VK Insights — на главную">
                <span class="vk-logo" aria-hidden="true">VK</span>
                <span class="vk-header__title">VK Insights</span>
            </a>
            <nav v-if="showNewSearch" class="vk-header__nav" aria-label="Действия отчёта">
                <button
                    type="button"
                    class="vk-header__new-search"
                    @click="emit('new-search')"
                >
                    ← Новый поиск
                </button>
            </nav>
        </div>
    </header>
</template>
