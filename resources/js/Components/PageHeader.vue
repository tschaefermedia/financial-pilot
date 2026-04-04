<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    backHref: { type: String, default: null },
    backLabel: { type: String, default: 'Zurück' },
});
</script>

<template>
    <div class="mb-6">
        <Link v-if="backHref" :href="backHref" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mb-2">
            <i class="pi pi-arrow-left text-xs"></i>
            {{ backLabel }}
        </Link>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
            <div class="flex items-center gap-2">
                <slot />
            </div>
        </div>
    </div>
</template>
