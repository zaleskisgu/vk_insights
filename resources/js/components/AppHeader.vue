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
            <div class="vk-header__brand">
                <div class="vk-logo" aria-hidden="true">VK</div>
                <span class="vk-header__title">VK Insights</span>
            </div>
            <button
                v-if="showNewSearch"
                type="button"
                class="vk-header__new-search"
                @click="emit('new-search')"
            >
                ← Новый поиск
            </button>
        </div>
    </header>
</template>
