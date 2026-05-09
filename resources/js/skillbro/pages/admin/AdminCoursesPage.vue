<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroCourse } from '@/types/skillbro-api';

const api = useSkillbroApi();
const courses = ref<SkillbroCourse[]>([]);
const status = ref('');

function getErrorMessage(caught: unknown, fallback: string): string {
    return caught && typeof caught === 'object' && 'message' in caught
        ? String((caught as { message: string }).message)
        : fallback;
}

async function loadCourses(): Promise<void> {
    status.value = '';

    try {
        const response = await api.getAdminCourses();
        courses.value = response.data;
    } catch (caught) {
        courses.value = [];
        status.value = getErrorMessage(caught, 'Unable to load courses.');
    }
}

async function approveCourse(courseId: number): Promise<void> {
    try {
        await api.publishCourse(courseId);
        status.value = `Course #${courseId} published.`;
        await loadCourses();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Publish failed.');
    }
}

async function archiveCourse(courseId: number): Promise<void> {
    try {
        await api.archiveCourse(courseId);
        status.value = `Course #${courseId} archived.`;
        await loadCourses();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Archive failed.');
    }
}

onMounted(loadCourses);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Admin course moderation</h1>
            <p class="mt-2 text-muted-links-2">Approve pending courses and archive content that must be removed from storefront visibility.</p>
        </section>

        <section class="space-y-3">
            <article
                v-for="course in courses"
                :key="course.id"
                class="rounded-xl border border-white/10 bg-black/50 p-5"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-xs uppercase tracking-[0.72px] text-muted-links">{{ course.status }}</p>
                        <h2 class="text-xl text-white">{{ course.title }}</h2>
                        <p class="text-sm text-muted-links-2">Course #{{ course.id }} · {{ course.level }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]"
                            :disabled="course.status === 'published'"
                            @click="approveCourse(course.id)"
                        >
                            Publish
                        </Button>
                        <Button
                            variant="outline"
                            class="border-white/20 text-white"
                            :disabled="course.status === 'archived'"
                            @click="archiveCourse(course.id)"
                        >
                            Archive
                        </Button>
                    </div>
                </div>
            </article>
        </section>
        <p v-if="!courses.length" class="mt-4 text-sm text-muted-links">No courses available for moderation.</p>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
