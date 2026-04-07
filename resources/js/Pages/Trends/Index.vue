<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { useFormatters } from '@/Composables/useFormatters.js';
import { useTheme } from '@/Composables/useTheme.js';
import { computed, defineAsyncComponent } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const { formatCurrency } = useFormatters();
const { isDark } = useTheme();

const chartTextColor = computed(() => isDark.value ? '#9ca3af' : '#6b7280');
const chartGridColor = computed(() => isDark.value ? '#374151' : '#e5e7eb');

const props = defineProps({
    monthlyData: { type: Array, default: () => [] },
    categoryTrends: { type: Array, default: () => [] },
    currentMonth: { type: String, default: '' },
});

const hasData = computed(() => props.monthlyData.length > 0);
const anomalyCount = computed(() => props.categoryTrends.filter(c => c.isAnomaly).length);

const currencyFormatter = (v) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v);
const currencyFormatterFull = (v) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(v);

const monthlyChartOptions = computed(() => ({
    chart: { type: 'area', height: 350, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#22c55e', '#ef4444'],
    xaxis: { categories: props.monthlyData.map(d => d.month), labels: { style: { colors: chartTextColor.value } } },
    yaxis: { labels: { style: { colors: chartTextColor.value }, formatter: currencyFormatter } },
    grid: { borderColor: chartGridColor.value },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 } },
    legend: { position: 'top', labels: { colors: chartTextColor.value } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: currencyFormatterFull } },
}));

const monthlySeries = computed(() => [
    { name: 'Einnahmen', data: props.monthlyData.map(d => d.income) },
    { name: 'Ausgaben', data: props.monthlyData.map(d => d.expenses) },
]);
</script>

<template>
    <AppLayout>
        <PageHeader title="Trends">
            <template #actions>
                <Tag v-if="anomalyCount > 0" severity="danger" :value="`${anomalyCount} Auffälligkeit${anomalyCount > 1 ? 'en' : ''}`" />
            </template>
        </PageHeader>

        <template v-if="hasData">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Einnahmen & Ausgaben (12 Monate)</h3>
                <VueApexCharts type="area" :options="monthlyChartOptions" :series="monthlySeries" height="350" />
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Kategorien-Trends ({{ currentMonth }})</h3>
                <DataTable :value="categoryTrends" stripedRows size="small" :paginator="categoryTrends.length > 20" :rows="20">
                    <Column field="category" header="Kategorie" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span>{{ data.category }}</span>
                                <Tag v-if="data.isAnomaly" severity="danger" value="Auffällig" class="text-xs" />
                            </div>
                        </template>
                    </Column>
                    <Column field="currentMonth" header="Aktuell" sortable>
                        <template #body="{ data }">
                            {{ formatCurrency(data.currentMonth) }}
                        </template>
                    </Column>
                    <Column field="previousMonth" header="Vormonat" sortable>
                        <template #body="{ data }">
                            {{ formatCurrency(data.previousMonth) }}
                        </template>
                    </Column>
                    <Column field="changePercent" header="Veränderung" sortable>
                        <template #body="{ data }">
                            <span :class="data.changePercent > 10 ? 'text-red-600' : data.changePercent < -10 ? 'text-green-600' : 'text-gray-600 dark:text-gray-400'">
                                {{ data.changePercent > 0 ? '+' : '' }}{{ data.changePercent }}%
                            </span>
                        </template>
                    </Column>
                    <Column field="threeMonthAvg" header="3M-Durchschnitt" sortable>
                        <template #body="{ data }">
                            {{ formatCurrency(data.threeMonthAvg) }}
                        </template>
                    </Column>
                    <Column header="Abweichung" sortable field="anomalyPercent">
                        <template #body="{ data }">
                            <template v-if="data.threeMonthAvg > 0">
                                <span :class="data.anomalyPercent > 30 ? 'text-red-600 font-semibold' : data.anomalyPercent < -10 ? 'text-green-600' : 'text-gray-600 dark:text-gray-400'">
                                    {{ data.anomalyPercent > 0 ? '+' : '' }}{{ data.anomalyPercent }}%
                                </span>
                            </template>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </template>
        <template v-else>
            <EmptyState message="Noch keine Daten für Trendanalyse vorhanden." icon="pi-chart-line" />
        </template>
    </AppLayout>
</template>
