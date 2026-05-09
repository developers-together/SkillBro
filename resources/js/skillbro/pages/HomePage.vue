<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import { demoCourses } from '@/skillbro/lib/demoCourses';
import type { SkillbroCourse } from '@/types/skillbro-api';

const api = useSkillbroApi();
const featuredCourses = ref<SkillbroCourse[]>([]);
const fallbackFeatured = demoCourses.filter((course) => course.id >= 9101 && course.id <= 9103);

onMounted(async () => {
    try {
        const response = await api.getCourses();
        featuredCourses.value = response.data.slice(0, 3);
    } catch {
        featuredCourses.value = [];
    }
});
</script>

<template>
    <ShellLayout>
        <section class="grid gap-10 lg:grid-cols-2 lg:items-end">
            <div>
                <p class="mb-4 text-xs tracking-[0.72px] text-muted-links uppercase">
                    Commerce-first learning platform
                </p>
                <h1 class="sb-display-hero max-w-4xl">
                    Sell expertise with a cinematic storefront.
                </h1>
                <p class="mt-6 max-w-2xl text-lg text-muted-links-2">
                    SkillBro combines catalog, enrollment, payments, curriculum, and analytics in one API-powered system.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <RouterLink to="/courses">
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]">
                            Browse catalog
                        </Button>
                    </RouterLink>
                    <RouterLink to="/auth/register">
                        <Button variant="outline" class="border-white text-white">
                            Launch as instructor
                        </Button>
                    </RouterLink>
                </div>
            </div>
            <div class="overflow-hidden rounded-[20px] border border-white/10">
                <img
                    src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80"
                    alt="Team scaling digital commerce learning"
                    class="h-[420px] w-full object-cover"
                >
            </div>
        </section>

        <section class="mt-16 rounded-xl bg-[#fbfbf5] p-6 text-black md:p-10">
            <h2 class="sb-display-section text-black">Featured starter courses</h2>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article
                    v-for="course in (featuredCourses.length ? featuredCourses : fallbackFeatured)"
                    :key="course.id"
                    class="rounded-xl border border-[#e4e4e7] bg-white p-5"
                >
                    <h3 class="text-xl font-medium">{{ course.title }}</h3>
                    <p class="mt-2 line-clamp-3 text-sm text-[#52525b]">
                        {{ course.description }}
                    </p>
                    <RouterLink :to="`/courses/${course.id}`" class="mt-4 inline-block text-sm text-black underline">
                        View course
                    </RouterLink>
                </article>
            </div>
            <p v-if="!featuredCourses.length" class="mt-4 text-sm text-[#52525b]">
                Live featured courses are not published yet, so these starter samples are shown.
            </p>
        </section>
    </ShellLayout>
</template>
