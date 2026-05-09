<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroEnrollment } from '@/types/skillbro-api';

const route = useRoute();
const api = useSkillbroApi();
const enrollment = ref<SkillbroEnrollment | null>(null);
const status = ref('');
const quizLectureId = ref('');
const quizQuestionId = ref('');
const quizAnswerId = ref('');
const certificateUrl = ref('');

const enrollmentId = computed(() => Number(route.params.enrollmentId));

async function load(): Promise<void> {
    try {
        enrollment.value = await api.getEnrollment(enrollmentId.value);
    } catch (caught) {
        enrollment.value = null;
        certificateUrl.value = '';

        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load enrollment details.';

        return;
    }

    try {
        const certificate = await api.getEnrollmentCertificate(enrollmentId.value);
        certificateUrl.value = certificate.download_url ?? '';
    } catch {
        certificateUrl.value = '';
    }
}

async function completeLecture(lectureId: number): Promise<void> {
    try {
        await api.completeLecture(enrollmentId.value, lectureId);
        status.value = `Lecture ${lectureId} marked complete.`;
        await load();
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Failed to complete lecture.';
    }
}

async function attemptQuiz(): Promise<void> {
    status.value = '';

    try {
        const result = await api.attemptLectureQuiz(Number(quizLectureId.value), {
            answers: [
                {
                    question_id: Number(quizQuestionId.value),
                    answer_id: Number(quizAnswerId.value),
                },
            ],
        });
        status.value = `Quiz score: ${result.score} (${result.passed ? 'passed' : 'failed'})`;
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Quiz attempt failed.';
    }
}

onMounted(load);
</script>

<template>
    <ShellLayout>
        <section v-if="!enrollment && status" class="rounded-xl border border-white/10 bg-black/50 p-6">
            <h1 class="text-2xl text-white">Learning detail unavailable</h1>
            <p class="mt-2 text-muted-links-2">{{ status }}</p>
        </section>

        <section v-if="enrollment">
            <h1 class="sb-display-section">{{ enrollment.course.title }}</h1>
            <p class="mt-3 text-muted-links-2">
                Enrollment #{{ enrollment.id }} · Completed: {{ enrollment.is_completed ? 'Yes' : 'No' }}
            </p>

            <a
                v-if="certificateUrl"
                :href="certificateUrl"
                target="_blank"
                class="mt-3 inline-block text-sm text-white underline"
            >
                Download completion certificate
            </a>

            <div class="mt-6 space-y-4">
                <article
                    v-for="section in enrollment.course.sections ?? []"
                    :key="section.id"
                    class="rounded-xl border border-white/10 bg-black/50 p-5"
                >
                    <h2 class="text-lg text-white">{{ section.title }}</h2>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="lecture in section.lectures ?? []"
                            :key="lecture.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-white/10 px-3 py-2"
                        >
                            <span class="text-sm text-muted-links-2">{{ lecture.title }} · {{ lecture.type }}</span>
                            <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="completeLecture(lecture.id)">
                                Mark complete
                            </Button>
                        </li>
                    </ul>
                </article>
            </div>

            <div class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-lg text-white">Quiz attempt helper</h2>
                <div class="mt-3 grid gap-2 md:grid-cols-3">
                    <Input v-model="quizLectureId" placeholder="Lecture ID" class="bg-black/30 text-white" />
                    <Input v-model="quizQuestionId" placeholder="Question ID" class="bg-black/30 text-white" />
                    <Input v-model="quizAnswerId" placeholder="Answer ID" class="bg-black/30 text-white" />
                </div>
                <Button class="mt-3 bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="attemptQuiz">
                    Submit attempt
                </Button>
            </div>
        </section>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
