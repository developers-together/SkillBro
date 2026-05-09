<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroCourse, SkillbroReview } from '@/types/skillbro-api';

const api = useSkillbroApi();
const { currentUser, bootstrapSession } = useAuthSession();

const loading = ref(false);
const status = ref('');
const courses = ref<SkillbroCourse[]>([]);
const selectedCourseId = ref<number | null>(null);
const reviews = ref<SkillbroReview[]>([]);
const replyForm = reactive<Record<number, string>>({});

const selectedCourse = computed(() => courses.value.find((course) => course.id === selectedCourseId.value) ?? null);

async function loadCourses(): Promise<void> {
    try {
        await bootstrapSession();
        const response = await api.getCourses();

        courses.value = response.data.filter((course) => {
            if (currentUser.value?.role === 'admin') {
                return true;
            }

            return course.instructor?.id === currentUser.value?.id;
        });

        if (!selectedCourseId.value && courses.value.length > 0) {
            selectedCourseId.value = courses.value[0].id;
        }
    } catch (caught) {
        courses.value = [];
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load instructor courses.';
    }
}

async function loadReviews(): Promise<void> {
    if (!selectedCourseId.value) {
        reviews.value = [];

        return;
    }

    loading.value = true;
    status.value = '';

    try {
        const response = await api.getCourseReviews(selectedCourseId.value);
        reviews.value = response.data;
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load reviews.';
    } finally {
        loading.value = false;
    }
}

async function replyToReview(reviewId: number): Promise<void> {
    if (!selectedCourseId.value) {
        return;
    }

    const reply = (replyForm[reviewId] ?? '').trim();

    if (!reply) {
        status.value = 'Reply text is required.';

        return;
    }

    try {
        await api.replyToCourseReview(selectedCourseId.value, reviewId, reply);
        status.value = 'Reply sent.';
        await loadReviews();
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Failed to send reply.';
    }
}

onMounted(async () => {
    await loadCourses();
    await loadReviews();
});
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Reviews inbox</h1>
            <p class="mt-2 text-muted-links-2">Read student feedback and reply directly from your instructor workflow.</p>
        </section>

        <section class="mb-5 rounded-xl border border-white/10 bg-black/50 p-5">
            <label class="text-xs uppercase tracking-[0.72px] text-muted-links">Course</label>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <select
                    v-model="selectedCourseId"
                    class="h-11 min-w-[280px] rounded-md border border-white/20 bg-black/30 px-3 text-sm text-white"
                    @change="loadReviews"
                >
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.title }}
                    </option>
                </select>
                <Button variant="outline" class="border-white/30 text-white" @click="loadReviews">Refresh</Button>
            </div>
            <p v-if="selectedCourse" class="mt-2 text-sm text-muted-links">Selected: {{ selectedCourse.title }}</p>
        </section>

        <section class="space-y-3">
            <article
                v-for="review in reviews"
                :key="review.id"
                class="rounded-xl border border-white/10 bg-black/50 p-4"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm text-[#c1fbd4]">Rating {{ review.rating }}/5</p>
                    <p class="text-xs text-muted-links">Review #{{ review.id }}</p>
                </div>
                <p class="mt-2 text-sm text-muted-links-2">{{ review.body || 'No written text.' }}</p>
                <p v-if="review.instructor_reply" class="mt-2 text-sm text-white">Reply: {{ review.instructor_reply }}</p>

                <div class="mt-3 grid gap-2 md:grid-cols-[1fr_auto]">
                    <Input
                        v-model="replyForm[review.id]"
                        placeholder="Write a constructive reply"
                        class="bg-black/30 text-white"
                    />
                    <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="replyToReview(review.id)">
                        Send reply
                    </Button>
                </div>
            </article>
        </section>

        <p v-if="!reviews.length && !loading" class="mt-4 text-sm text-muted-links">No reviews found for this course.</p>
        <p v-if="loading" class="mt-4 text-sm text-muted-links">Loading reviews...</p>
        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
