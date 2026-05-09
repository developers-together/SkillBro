<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroPayment } from '@/types/skillbro-api';

const api = useSkillbroApi();
const payments = ref<SkillbroPayment[]>([]);
const status = ref('');

async function load(): Promise<void> {
    status.value = '';

    try {
        const response = await api.getPayments();
        payments.value = response.data;
    } catch (caught) {
        payments.value = [];
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load payments.';
    }
}

async function requestRefund(paymentId: number): Promise<void> {
    try {
        await api.requestPaymentRefund(paymentId);
        status.value = 'Refund requested.';
        await load();
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Refund request failed.';
    }
}

onMounted(load);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Payments</h1>
        </section>

        <section class="space-y-3">
            <article
                v-for="payment in payments"
                :key="payment.id"
                class="rounded-xl border border-white/10 bg-black/50 p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-white">Payment #{{ payment.id }} · {{ payment.status }}</p>
                    <p class="text-[#c1fbd4]">${{ payment.amount }}</p>
                </div>
                <Button
                    v-if="payment.status === 'completed'"
                    class="mt-3 bg-[#c1fbd4] text-black hover:bg-[#97efb8]"
                    @click="requestRefund(payment.id)"
                >
                    Request refund
                </Button>
            </article>
        </section>

        <p v-if="!payments.length" class="mt-4 text-sm text-muted-links-2">
            No payments yet. Once you purchase courses, your payment history appears here.
        </p>
        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
