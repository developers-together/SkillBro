<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroCourse, SkillbroUser } from '@/types/skillbro-api';

const route = useRoute();
const api = useSkillbroApi();

const instructor = ref<SkillbroUser | null>(null);
const courses = ref<SkillbroCourse[]>([]);
const status = ref('');
const loading = ref(false);
const loaded = ref(false);

function getErrorMessage(caught: unknown, fallback: string): string {
    if (caught && typeof caught === 'object' && 'status' in caught && (caught as { status: number }).status === 404) {
        return 'Instructor profile not found.';
    }

    return caught && typeof caught === 'object' && 'message' in caught
        ? String((caught as { message: string }).message)
        : fallback;
}

onMounted(async () => {
    const userId = Number(route.params.userId);

    loading.value = true;
    status.value = '';
    loaded.value = false;

    try {
        const response = await api.getInstructorProfile(userId);
        instructor.value = response.instructor;
        courses.value = response.courses.data;
    } catch (caught) {
        instructor.value = null;
        courses.value = [];
        status.value = getErrorMessage(caught, 'Unable to load instructor profile.');
    } finally {
        loading.value = false;
        loaded.value = true;
    }
});
</script>

<template>
    <ShellLayout>
        <section v-if="instructor" class="mb-8">
            <h1 class="sb-display-section">{{ instructor.name }}</h1>
            <p class="mt-3 max-w-2xl text-muted-links-2">
                {{ instructor.bio || 'This instructor has not added a public bio yet.' }}
            </p>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="course in courses" :key="course.id" class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">{{ course.title }}</h2>
                <p class="mt-2 text-sm text-muted-links-2">{{ course.description }}</p>
                <RouterLink :to="`/courses/${course.id}`" class="mt-3 inline-block text-sm text-white underline">
                    Open course
                </RouterLink>
            </article>
        </section>
        <p v-if="loaded && !loading && instructor && !courses.length" class="mt-4 text-sm text-muted-links">
            This instructor has no published courses yet.
        </p>
        <p v-if="loading" class="mt-4 text-sm text-muted-links">Loading instructor profile...</p>
        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
