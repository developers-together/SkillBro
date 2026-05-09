<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const api = useSkillbroApi();
const loading = ref(false);
const status = ref('');
const summary = ref<{
    completed_total: string;
    refunded_total: string;
    completed_count: number;
    refunded_count: number;
} | null>(null);
const monthly = ref<Array<{ month: string; total: string }>>([]);

async function loadRevenue(): Promise<void> {
    loading.value = true;
    status.value = '';

    try {
        const response = await api.getInstructorRevenueSummary();
        summary.value = response.summary;
        monthly.value = response.monthly;
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load revenue analytics.';
    } finally {
        loading.value = false;
    }
}

onMounted(loadRevenue);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Instructor revenue</h1>
            <p class="mt-2 text-muted-links-2">
                Provider-neutral payout analytics for your course business.
            </p>
        </section>

        <section v-if="summary" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Completed total</p>
                <p class="mt-2 text-3xl text-[#c1fbd4]">${{ summary.completed_total }}</p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Refunded total</p>
                <p class="mt-2 text-3xl text-[#c1fbd4]">${{ summary.refunded_total }}</p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Completed payments</p>
                <p class="mt-2 text-3xl text-white">{{ summary.completed_count }}</p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Refunded payments</p>
                <p class="mt-2 text-3xl text-white">{{ summary.refunded_count }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
            <h2 class="text-xl text-white">Monthly trend</h2>
            <table class="mt-4 w-full text-left text-sm text-muted-links-2">
                <thead>
                    <tr class="border-b border-white/10 text-muted-links">
                        <th class="py-2">Month</th>
                        <th class="py-2">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in monthly" :key="row.month" class="border-b border-white/5">
                        <td class="py-2">{{ row.month }}</td>
                        <td class="py-2 text-[#c1fbd4]">${{ row.total }}</td>
                    </tr>
                </tbody>
            </table>

            <p v-if="!monthly.length && !loading" class="mt-3 text-sm text-muted-links">No monthly revenue yet.</p>
        </section>

        <p v-if="loading" class="mt-4 text-sm text-muted-links">Loading analytics...</p>
        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
