<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroEnrollment } from '@/types/skillbro-api';

const api = useSkillbroApi();
const enrollments = ref<SkillbroEnrollment[]>([]);
const loading = ref(false);
const status = ref('');

async function load(): Promise<void> {
    loading.value = true;
    status.value = '';

    try {
        const response = await api.getEnrollments();
        enrollments.value = response.data;
    } catch (caught) {
        enrollments.value = [];
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load learning enrollments.';
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">My learning</h1>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            <article
                v-for="enrollment in enrollments"
                :key="enrollment.id"
                class="rounded-xl border border-white/10 bg-black/50 p-5"
            >
                <h2 class="text-xl text-white">{{ enrollment.course.title }}</h2>
                <p class="mt-2 text-sm text-muted-links">
                    Enrolled at {{ enrollment.enrolled_at }}
                </p>
                <p class="mt-2 text-sm text-muted-links-2">
                    Completed: {{ enrollment.is_completed ? 'Yes' : 'No' }}
                </p>
                <RouterLink :to="`/app/learn/${enrollment.id}`" class="mt-3 inline-block">
                    <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]">
                        Open learning view
                    </Button>
                </RouterLink>
            </article>
        </section>

        <p v-if="loading" class="mt-4 text-sm text-muted-links">Loading enrollments...</p>
        <p v-if="!loading && !enrollments.length" class="mt-4 text-sm text-muted-links-2">
            No enrollments yet. Explore the catalog and enroll in your first course.
        </p>
        <p v-if="status" class="mt-2 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
