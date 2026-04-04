<script setup>
import { useFormatters } from '@/Composables/useFormatters.js';

const { formatCurrency, formatPercent } = useFormatters();

const props = defineProps({
    loansSummary: { type: Object, required: true },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kredite</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(loansSummary.owedByMe.totalRemaining) }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Restschuld von {{ formatCurrency(loansSummary.owedByMe.totalPrincipal) }}
                </p>
            </div>
            <a href="/loans" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Alle Kredite &rarr;</a>
        </div>

        <!-- Progress bar -->
        <div v-if="loansSummary.owedByMe.count > 0">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Tilgungsfortschritt</span>
                <span>{{ formatPercent(loansSummary.owedByMe.progressPercent) }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: loansSummary.owedByMe.progressPercent + '%' }"
                />
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                {{ loansSummary.owedByMe.count }} {{ loansSummary.owedByMe.count === 1 ? 'Kredit' : 'Kredite' }}
            </p>
        </div>

        <!-- Owed to me -->
        <div v-if="loansSummary.owedToMe.count > 0" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">Ausstehende Forderungen</p>
                <p class="text-sm font-semibold text-green-600">{{ formatCurrency(loansSummary.owedToMe.totalRemaining) }}</p>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ loansSummary.owedToMe.count }} {{ loansSummary.owedToMe.count === 1 ? 'Forderung' : 'Forderungen' }} &middot; {{ formatPercent(loansSummary.owedToMe.progressPercent) }} zurückgezahlt
            </p>
        </div>
    </div>
</template>
