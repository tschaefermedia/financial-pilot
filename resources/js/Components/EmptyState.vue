<script setup>
defineProps({
    message: { type: String, required: true },
    icon: { type: String, default: 'pi-inbox' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
        <i :class="['pi', icon, 'text-4xl mb-3']"></i>
        <p class="text-sm">{{ message }}</p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>
