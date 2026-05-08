<script setup>
import { computed } from 'vue';
import { vkWallPostUrl } from '@/utils/vkWallPostUrl.js';

const props = defineProps({
    ownerId: { type: [Number, String], default: undefined },
    postId: { type: [Number, String], default: undefined },
    text: { type: String, default: '' },
});

const href = computed(() => vkWallPostUrl(props.ownerId, props.postId));
</script>

<template>
    <component
        :is="href ? 'a' : 'span'"
        v-bind="
            href
                ? {
                      href,
                      target: '_blank',
                      rel: 'noopener noreferrer',
                      class: 'vk-all-posts__text-link',
                  }
                : { class: 'vk-all-posts__text-plain' }
        "
        :title="text"
    >
        <span class="vk-all-posts__text-clip">{{ text }}</span>
    </component>
</template>
