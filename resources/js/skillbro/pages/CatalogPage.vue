<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import { demoCourses } from '@/skillbro/lib/demoCourses';
import type { SkillbroCourse } from '@/types/skillbro-api';

const api = useSkillbroApi();

const filters = reactive({
    search: '',
    free: false,
    level: '',
});

const courses = ref<SkillbroCourse[]>([]);
const loading = ref(false);
const status = ref('');
const fallbackCourses = demoCourses.filter((course) => course.id >= 9001 && course.id <= 9003);
const catalogItems = computed(() => (courses.value.length > 0 ? courses.value : fallbackCourses));

async function loadCourses(): Promise<void> {
    loading.value = true;
    status.value = '';

    try {
        const response = await api.getCourses({
            search: filters.search || undefined,
            free: filters.free ? 1 : undefined,
            level: filters.level || undefined,
        });
        courses.value = response.data;
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Catalog temporarily unavailable. Showing starter sample courses.';
        courses.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(loadCourses);
</script>

<template>
    <ShellLayout>
        <section class="mb-8">
            <h1 class="sb-display-section">Course catalog</h1>
            <p class="mt-3 text-muted-links-2">
                Explore published courses, compare levels, and enroll instantly.
            </p>
        </section>

        <section class="mb-6 grid gap-3 rounded-xl border border-white/10 bg-black/40 p-4 md:grid-cols-4">
            <Input v-model="filters.search" placeholder="Search title or description" class="bg-black/30 text-white" />
            <select
                v-model="filters.level"
                class="h-11 rounded-md border border-white/20 bg-black/30 px-3 text-sm text-white"
            >
                <option value="">All levels</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-muted-links">
                <input v-model="filters.free" type="checkbox" class="size-4 rounded border border-white/30 bg-black">
                Free only
            </label>
            <Button variant="outline" class="border-white/30 text-white" @click="loadCourses">
                Apply filters
            </Button>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="course in catalogItems"
                :key="course.id"
                class="rounded-xl border border-white/10 bg-black/50 p-5"
            >
                <p class="text-xs uppercase tracking-[0.72px] text-muted-links">
                    {{ course.level }} • {{ course.status }}
                </p>
                <h2 class="mt-2 text-2xl font-medium text-white">{{ course.title }}</h2>
                <p class="mt-2 line-clamp-3 text-sm text-muted-links-2">
                    {{ course.description }}
                </p>
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-lg text-[#c1fbd4]">
                        {{ Number(course.price) === 0 ? 'Free' : `$${course.price}` }}
                    </p>
                    <RouterLink :to="`/courses/${course.id}`">
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]">
                            View details
                        </Button>
                    </RouterLink>
                </div>
            </article>
        </section>

        <p v-if="loading" class="mt-6 text-sm text-muted-links">Loading courses...</p>
        <p v-if="!courses.length && !loading" class="mt-6 text-sm text-muted-links-2">
            Live catalog is empty right now. Showing starter sample courses.
        </p>
        <p v-if="status" class="mt-2 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
