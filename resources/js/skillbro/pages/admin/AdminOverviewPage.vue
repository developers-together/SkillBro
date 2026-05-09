<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroCourse, SkillbroPayment, SkillbroUser } from '@/types/skillbro-api';

const api = useSkillbroApi();
const stats = ref<Record<string, unknown> | null>(null);
const users = ref<SkillbroUser[]>([]);
const courses = ref<SkillbroCourse[]>([]);
const payments = ref<SkillbroPayment[]>([]);
const status = ref('');

const roleForm = ref({
    userId: '',
    role: 'student' as 'student' | 'instructor' | 'admin',
});
const banForm = ref({
    userId: '',
    ban: false,
});
const refundForm = ref({
    paymentId: '',
    approve: true,
});

function getErrorMessage(caught: unknown, fallback: string): string {
    return caught && typeof caught === 'object' && 'message' in caught
        ? String((caught as { message: string }).message)
        : fallback;
}

async function loadAll(): Promise<void> {
    status.value = '';

    try {
        const [statsResponse, usersResponse, coursesResponse, paymentsResponse] = await Promise.all([
            api.getAdminStats(),
            api.getAdminUsers(),
            api.getAdminCourses(),
            api.getAdminPayments(),
        ]);

        stats.value = statsResponse as Record<string, unknown>;
        users.value = usersResponse.data;
        courses.value = coursesResponse.data;
        payments.value = paymentsResponse.data;
    } catch (caught) {
        stats.value = null;
        users.value = [];
        courses.value = [];
        payments.value = [];
        status.value = getErrorMessage(caught, 'Unable to load admin overview.');
    }
}

async function updateRole(): Promise<void> {
    try {
        await api.updateUserRole(Number(roleForm.value.userId), roleForm.value.role);
        status.value = 'Role updated.';
        await loadAll();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Role update failed.');
    }
}

async function updateBan(): Promise<void> {
    try {
        await api.updateUserBan(Number(banForm.value.userId), banForm.value.ban);
        status.value = 'Ban status updated.';
        await loadAll();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Ban update failed.');
    }
}

async function decideRefund(): Promise<void> {
    try {
        await api.decideAdminPaymentRefund(Number(refundForm.value.paymentId), refundForm.value.approve);
        status.value = 'Refund decision saved.';
        await loadAll();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Refund decision failed.');
    }
}

onMounted(loadAll);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Admin console</h1>
        </section>

        <section class="mb-6 grid gap-3 md:grid-cols-4">
            <RouterLink to="/app/admin/users">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Users</Button>
            </RouterLink>
            <RouterLink to="/app/admin/courses">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Courses</Button>
            </RouterLink>
            <RouterLink to="/app/admin/payments">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Payments</Button>
            </RouterLink>
            <RouterLink to="/app/admin/stats">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Stats</Button>
            </RouterLink>
        </section>

        <section class="rounded-xl border border-white/10 bg-black/50 p-5">
            <h2 class="text-xl text-white">Platform stats</h2>
            <pre class="mt-3 overflow-auto rounded bg-black/60 p-3 text-xs text-muted-links-2">{{ stats }}</pre>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-3">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h3 class="text-lg text-white">Change role</h3>
                <Input v-model="roleForm.userId" placeholder="User ID" class="mt-2 bg-black/30 text-white" />
                <select v-model="roleForm.role" class="mt-2 h-11 w-full rounded-md border border-white/20 bg-black/30 px-3 text-white">
                    <option value="student">student</option>
                    <option value="instructor">instructor</option>
                    <option value="admin">admin</option>
                </select>
                <Button class="mt-2 bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="updateRole">Update role</Button>
            </article>

            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h3 class="text-lg text-white">Ban / unban</h3>
                <Input v-model="banForm.userId" placeholder="User ID" class="mt-2 bg-black/30 text-white" />
                <label class="mt-2 flex items-center gap-2 text-sm text-muted-links">
                    <input v-model="banForm.ban" type="checkbox" class="size-4">
                    Ban user
                </label>
                <Button class="mt-2 bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="updateBan">Apply</Button>
            </article>

            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h3 class="text-lg text-white">Refund decision</h3>
                <Input v-model="refundForm.paymentId" placeholder="Payment ID" class="mt-2 bg-black/30 text-white" />
                <label class="mt-2 flex items-center gap-2 text-sm text-muted-links">
                    <input v-model="refundForm.approve" type="checkbox" class="size-4">
                    Approve refund
                </label>
                <Button class="mt-2 bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="decideRefund">Submit</Button>
            </article>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-3">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h3 class="text-lg text-white">Users</h3>
                <ul class="mt-2 space-y-2 text-xs text-muted-links-2">
                    <li v-for="user in users.slice(0, 10)" :key="user.id">
                        #{{ user.id }} · {{ user.name }} · {{ user.role }}
                    </li>
                </ul>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h3 class="text-lg text-white">Courses</h3>
                <ul class="mt-2 space-y-2 text-xs text-muted-links-2">
                    <li v-for="course in courses.slice(0, 10)" :key="course.id">
                        #{{ course.id }} · {{ course.title }} · {{ course.status }}
                    </li>
                </ul>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h3 class="text-lg text-white">Payments</h3>
                <ul class="mt-2 space-y-2 text-xs text-muted-links-2">
                    <li v-for="payment in payments.slice(0, 10)" :key="payment.id">
                        #{{ payment.id }} · {{ payment.status }} · ${{ payment.amount }}
                    </li>
                </ul>
            </article>
        </section>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
