<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import { findDemoCourseByIdOrSlug } from '@/skillbro/lib/demoCourses';
import type { SkillbroCourse, SkillbroReview } from '@/types/skillbro-api';

const route = useRoute();
const api = useSkillbroApi();
const { isAuthenticated } = useAuthSession();

const course = ref<SkillbroCourse | null>(null);
const reviews = ref<SkillbroReview[]>([]);
const loading = ref(false);
const reviewRating = ref(5);
const reviewBody = ref('');
const status = ref('');
const isDemoCourse = ref(false);

const courseKey = computed(() => String(route.params.slugOrId ?? ''));

async function load(): Promise<void> {
    loading.value = true;
    status.value = '';

    try {
        const demoCourse = findDemoCourseByIdOrSlug(courseKey.value);

        if (demoCourse) {
            course.value = demoCourse;
            reviews.value = [];
            isDemoCourse.value = true;

            return;
        }

        isDemoCourse.value = false;

        const numericId = Number(courseKey.value);

        if (Number.isFinite(numericId) && numericId > 0) {
            course.value = await api.getCourse(numericId);
        } else {
            const response = await api.getCourses({ search: courseKey.value });
            course.value = response.data.find((item) => item.slug === courseKey.value) ?? null;
        }

        if (!course.value) {
            status.value = 'Course not found.';
            reviews.value = [];

            return;
        }

        const reviewResponse = await api.getCourseReviews(course.value.id);
        reviews.value = reviewResponse.data;
    } catch (caught) {
        course.value = null;
        reviews.value = [];
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load this course right now.';
    } finally {
        loading.value = false;
    }
}

async function enrollOrCheckout(): Promise<void> {
    if (!course.value) {
        return;
    }

    if (isDemoCourse.value) {
        status.value = 'Preview course only. Enroll from a live published course in the catalog.';

        return;
    }

    try {
        if (Number(course.value.price) === 0) {
            await api.createEnrollment(course.value.id);
            status.value = 'Enrolled successfully.';
        } else {
            const checkout = await api.createPaymentCheckout({
                course_id: course.value.id,
                success_url: `${window.location.origin}/app/payments`,
                cancel_url: `${window.location.href}`,
            });
            status.value = checkout.url ? `Checkout URL: ${checkout.url}` : 'Checkout created.';
        }
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Action failed.';
    }
}

async function createReview(): Promise<void> {
    try {
        if (!course.value) {
            status.value = 'Course not found.';

            return;
        }

        if (isDemoCourse.value) {
            status.value = 'Preview course only. Reviews are disabled for demo catalog items.';

            return;
        }

        await api.createCourseReview(course.value.id, {
            rating: reviewRating.value,
            body: reviewBody.value || undefined,
        });
        reviewBody.value = '';
        await load();
        status.value = 'Review submitted.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Review failed.';
    }
}

onMounted(load);
</script>

<template>
    <ShellLayout>
        <section v-if="!course && status" class="rounded-xl border border-white/10 bg-black/50 p-6">
            <h1 class="text-2xl text-white">Course unavailable</h1>
            <p class="mt-2 text-muted-links-2">{{ status }}</p>
            <RouterLink to="/courses" class="mt-4 inline-block">
                <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Back to catalog</Button>
            </RouterLink>
        </section>

        <section v-if="course" class="grid gap-8 lg:grid-cols-[1.3fr_1fr]">
            <div>
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">
                    {{ course.level }} • {{ course.language }} • {{ course.status }}
                </p>
                <h1 class="mt-3 sb-display-section">{{ course.title }}</h1>
                <p class="mt-4 text-muted-links-2">{{ course.description }}</p>
                <p v-if="isDemoCourse" class="mt-3 text-sm text-muted-links-2">
                    Preview data: this course is shown from the local starter catalog and is not enrollable.
                </p>

                <div class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
                    <h2 class="text-xl text-white">Curriculum</h2>
                    <div v-for="section in course.sections ?? []" :key="section.id" class="mt-4">
                        <h3 class="text-lg text-white">{{ section.title }}</h3>
                        <ul class="mt-2 space-y-1 text-sm text-muted-links">
                            <li v-for="lecture in section.lectures ?? []" :key="lecture.id">
                                {{ lecture.title }} · {{ lecture.type }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
                    <h2 class="text-xl text-white">Reviews</h2>
                    <div class="mt-3 space-y-3">
                        <article v-for="review in reviews" :key="review.id" class="rounded-lg border border-white/10 p-3">
                            <p class="text-sm text-[#c1fbd4]">Rating: {{ review.rating }}/5</p>
                            <p class="text-sm text-muted-links-2">{{ review.body || 'No written feedback.' }}</p>
                            <p v-if="review.instructor_reply" class="mt-2 text-xs text-white">
                                Instructor reply: {{ review.instructor_reply }}
                            </p>
                        </article>
                    </div>

                    <div v-if="isAuthenticated" class="mt-4 grid gap-2 rounded-lg border border-white/10 p-4">
                        <label class="text-xs uppercase tracking-[0.72px] text-muted-links">Leave a review</label>
                        <Input v-model.number="reviewRating" type="number" min="1" max="5" class="bg-black/40 text-white" />
                        <Input v-model="reviewBody" placeholder="Share your feedback" class="bg-black/40 text-white" />
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" :disabled="isDemoCourse" @click="createReview">
                            Submit review
                        </Button>
                    </div>
                </div>
            </div>

            <aside class="rounded-xl border border-white/10 bg-black/50 p-5">
                <p class="text-sm uppercase tracking-[0.72px] text-muted-links">Enrollment</p>
                <p class="mt-2 text-3xl text-[#c1fbd4]">
                    {{ Number(course.price) === 0 ? 'Free' : `$${course.price}` }}
                </p>
                <Button
                    class="mt-5 w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]"
                    :disabled="isDemoCourse"
                    @click="enrollOrCheckout"
                >
                    {{ Number(course.price) === 0 ? 'Enroll free' : 'Buy course' }}
                </Button>
                <p v-if="status" class="mt-3 text-xs text-muted-links-2">{{ status }}</p>
            </aside>
        </section>

        <p v-if="loading" class="text-sm text-muted-links">Loading...</p>
    </ShellLayout>
</template>
