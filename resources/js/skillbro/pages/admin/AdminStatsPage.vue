<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const api = useSkillbroApi();
const stats = ref<{
    users: { total: number; students: number; instructors: number; admins: number };
    courses: { total: number; by_status: { draft: number; pending: number; published: number; archived: number } };
    enrollments: { total: number; completed: number };
} | null>(null);
const status = ref('');

const completionRate = computed(() => {
    if (!stats.value || stats.value.enrollments.total === 0) {
        return '0%';
    }

    const percentage = Math.round((stats.value.enrollments.completed / stats.value.enrollments.total) * 100);

    return `${percentage}%`;
});

async function loadStats(): Promise<void> {
    status.value = '';

    try {
        stats.value = await api.getAdminStats();
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load platform stats.';
    }
}

onMounted(loadStats);
</script>

<template>
    <ShellLayout>
        <section class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="sb-display-section">Admin KPI dashboard</h1>
                <p class="mt-2 text-muted-links-2">Operational overview for users, catalog health, and enrollment outcomes.</p>
            </div>
            <Button variant="outline" class="border-white/30 text-white" @click="loadStats">Refresh</Button>
        </section>

        <section v-if="stats" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Total users</p>
                <p class="mt-2 text-3xl text-white">{{ stats.users.total }}</p>
                <p class="mt-2 text-xs text-muted-links-2">
                    Students {{ stats.users.students }} · Instructors {{ stats.users.instructors }} · Admins {{ stats.users.admins }}
                </p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Total courses</p>
                <p class="mt-2 text-3xl text-white">{{ stats.courses.total }}</p>
                <p class="mt-2 text-xs text-muted-links-2">
                    Pending {{ stats.courses.by_status.pending }} · Published {{ stats.courses.by_status.published }}
                </p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Enrollments</p>
                <p class="mt-2 text-3xl text-white">{{ stats.enrollments.total }}</p>
                <p class="mt-2 text-xs text-muted-links-2">Completed {{ stats.enrollments.completed }}</p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Completion rate</p>
                <p class="mt-2 text-3xl text-[#c1fbd4]">{{ completionRate }}</p>
            </article>
        </section>

        <section v-if="stats" class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
            <h2 class="text-xl text-white">Course status split</h2>
            <div class="mt-3 grid gap-2 text-sm text-muted-links-2 md:grid-cols-4">
                <div class="rounded-md border border-white/10 p-3">Draft: {{ stats.courses.by_status.draft }}</div>
                <div class="rounded-md border border-white/10 p-3">Pending: {{ stats.courses.by_status.pending }}</div>
                <div class="rounded-md border border-white/10 p-3">Published: {{ stats.courses.by_status.published }}</div>
                <div class="rounded-md border border-white/10 p-3">Archived: {{ stats.courses.by_status.archived }}</div>
            </div>
        </section>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
