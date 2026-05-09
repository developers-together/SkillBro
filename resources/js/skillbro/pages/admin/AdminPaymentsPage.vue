<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroPayment } from '@/types/skillbro-api';

const api = useSkillbroApi();
const payments = ref<SkillbroPayment[]>([]);
const status = ref('');

const form = reactive({
    paymentId: '',
    approve: true,
});

function getErrorMessage(caught: unknown, fallback: string): string {
    return caught && typeof caught === 'object' && 'message' in caught
        ? String((caught as { message: string }).message)
        : fallback;
}

async function loadPayments(): Promise<void> {
    status.value = '';

    try {
        const response = await api.getAdminPayments();
        payments.value = response.data;
    } catch (caught) {
        payments.value = [];
        status.value = getErrorMessage(caught, 'Unable to load payments.');
    }
}

async function decideRefund(): Promise<void> {
    try {
        await api.decideAdminPaymentRefund(Number(form.paymentId), form.approve);
        status.value = form.approve ? 'Refund approved.' : 'Refund rejected.';
        await loadPayments();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Unable to process refund decision.');
    }
}

onMounted(loadPayments);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Admin payments</h1>
            <p class="mt-2 text-muted-links-2">Review commerce events and process refund decisions with auditable status transitions.</p>
        </section>

        <section class="rounded-xl border border-white/10 bg-black/50 p-5">
            <h2 class="text-xl text-white">Refund decision</h2>
            <div class="mt-3 grid gap-2 md:grid-cols-[220px_1fr_auto]">
                <Input v-model="form.paymentId" placeholder="Payment ID" class="bg-black/30 text-white" />
                <label class="inline-flex items-center gap-2 rounded-md border border-white/20 px-3 text-sm text-white">
                    <input v-model="form.approve" type="checkbox" class="size-4">
                    Approve refund
                </label>
                <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="decideRefund">Submit</Button>
            </div>
        </section>

        <section class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
            <h2 class="text-xl text-white">Payments ledger</h2>
            <table class="mt-4 w-full text-left text-sm text-muted-links-2">
                <thead>
                    <tr class="border-b border-white/10 text-muted-links">
                        <th class="py-2">ID</th>
                        <th class="py-2">User</th>
                        <th class="py-2">Course</th>
                        <th class="py-2">Amount</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="border-b border-white/5">
                        <td class="py-2">{{ payment.id }}</td>
                        <td class="py-2">{{ payment.user_id }}</td>
                        <td class="py-2">{{ payment.course_id }}</td>
                        <td class="py-2 text-[#c1fbd4]">${{ payment.amount }}</td>
                        <td class="py-2 uppercase">{{ payment.status }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!payments.length" class="mt-3 text-sm text-muted-links">No payments available.</p>
        </section>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
