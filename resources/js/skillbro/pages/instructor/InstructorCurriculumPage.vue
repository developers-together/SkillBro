<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroCourse, SkillbroSection } from '@/types/skillbro-api';

const api = useSkillbroApi();

const loading = ref(false);
const status = ref('');
const courses = ref<SkillbroCourse[]>([]);
const sections = ref<SkillbroSection[]>([]);
const selectedCourseId = ref<number | null>(null);

const sectionForm = reactive({ title: '' });
const lectureForm = reactive({
    section_id: '',
    title: '',
    type: 'text' as 'video' | 'text' | 'quiz',
    content: '',
});

const selectedCourse = computed(() => courses.value.find((course) => course.id === selectedCourseId.value) ?? null);

function getErrorMessage(caught: unknown, fallback: string): string {
    return caught && typeof caught === 'object' && 'message' in caught
        ? String((caught as { message: string }).message)
        : fallback;
}

async function loadCourses(): Promise<void> {
    status.value = '';

    try {
        const response = await api.getCourses();
        courses.value = response.data;

        if (!selectedCourseId.value && courses.value.length > 0) {
            selectedCourseId.value = courses.value[0].id;
        }
    } catch (caught) {
        courses.value = [];
        selectedCourseId.value = null;
        status.value = getErrorMessage(caught, 'Unable to load courses.');
    }
}

async function loadSections(): Promise<void> {
    if (!selectedCourseId.value) {
        sections.value = [];

        return;
    }

    loading.value = true;

    try {
        sections.value = await api.getCourseSections(selectedCourseId.value);
    } catch (caught) {
        sections.value = [];
        status.value = getErrorMessage(caught, 'Unable to load sections.');
    } finally {
        loading.value = false;
    }
}

async function createSection(): Promise<void> {
    if (!selectedCourseId.value || !sectionForm.title.trim()) {
        return;
    }

    try {
        await api.createSection(selectedCourseId.value, { title: sectionForm.title.trim() });
        sectionForm.title = '';
        status.value = 'Section created.';
        await loadSections();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Section creation failed.');
    }
}

async function moveSection(sectionId: number, direction: 'up' | 'down'): Promise<void> {
    if (!selectedCourseId.value) {
        return;
    }

    const reordered = sections.value.map((section) => ({ ...section }));
    const index = reordered.findIndex((section) => section.id === sectionId);

    if (index < 0) {
        return;
    }

    const swapIndex = direction === 'up' ? index - 1 : index + 1;

    if (swapIndex < 0 || swapIndex >= reordered.length) {
        return;
    }

    const current = reordered[index];
    reordered[index] = reordered[swapIndex];
    reordered[swapIndex] = current;

    try {
        await api.reorderSections(
            selectedCourseId.value,
            reordered.map((section, position) => ({ id: section.id, position: position + 1 })),
        );

        status.value = 'Section order updated.';
        await loadSections();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Section reorder failed.');
    }
}

async function createLecture(): Promise<void> {
    if (!lectureForm.section_id || !lectureForm.title.trim()) {
        return;
    }

    try {
        await api.createLecture(Number(lectureForm.section_id), {
            title: lectureForm.title.trim(),
            type: lectureForm.type,
            content: lectureForm.content || undefined,
        });

        lectureForm.title = '';
        lectureForm.content = '';
        status.value = 'Lecture created.';
        await loadSections();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Lecture creation failed.');
    }
}

async function moveLecture(sectionId: number, lectureId: number, direction: 'up' | 'down'): Promise<void> {
    const section = sections.value.find((item) => item.id === sectionId);

    if (!section || !section.lectures) {
        return;
    }

    const reordered = section.lectures.map((lecture) => ({ ...lecture }));
    const index = reordered.findIndex((lecture) => lecture.id === lectureId);

    if (index < 0) {
        return;
    }

    const swapIndex = direction === 'up' ? index - 1 : index + 1;

    if (swapIndex < 0 || swapIndex >= reordered.length) {
        return;
    }

    const current = reordered[index];
    reordered[index] = reordered[swapIndex];
    reordered[swapIndex] = current;

    try {
        await api.reorderLectures(
            sectionId,
            reordered.map((lecture, position) => ({ id: lecture.id, position: position + 1 })),
        );

        status.value = 'Lecture order updated.';
        await loadSections();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Lecture reorder failed.');
    }
}

onMounted(async () => {
    await loadCourses();
    await loadSections();
});
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Curriculum builder</h1>
            <p class="mt-2 text-muted-links-2">Compose sections, add lectures, and reorder everything for your course journey.</p>
        </section>

        <section class="mb-5 rounded-xl border border-white/10 bg-black/50 p-5">
            <label class="text-xs uppercase tracking-[0.72px] text-muted-links">Course</label>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <select
                    v-model="selectedCourseId"
                    class="h-11 min-w-[280px] rounded-md border border-white/20 bg-black/30 px-3 text-sm text-white"
                    @change="loadSections"
                >
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.title }}
                    </option>
                </select>
                <Button variant="outline" class="border-white/30 text-white" @click="loadSections">Reload</Button>
            </div>
            <p v-if="selectedCourse" class="mt-2 text-sm text-muted-links">Editing: {{ selectedCourse.title }}</p>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Create section</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Input v-model="sectionForm.title" placeholder="Section title" class="bg-black/30 text-white" />
                    <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="createSection">Add</Button>
                </div>
            </article>

            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Create lecture</h2>
                <div class="mt-3 grid gap-2 md:grid-cols-2">
                    <Input v-model="lectureForm.section_id" placeholder="Section ID" class="bg-black/30 text-white" />
                    <Input v-model="lectureForm.title" placeholder="Lecture title" class="bg-black/30 text-white" />
                    <select v-model="lectureForm.type" class="h-11 rounded-md border border-white/20 bg-black/30 px-3 text-sm text-white">
                        <option value="video">video</option>
                        <option value="text">text</option>
                        <option value="quiz">quiz</option>
                    </select>
                    <Input v-model="lectureForm.content" placeholder="Optional content" class="bg-black/30 text-white" />
                </div>
                <Button class="mt-3 bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="createLecture">Add lecture</Button>
            </article>
        </section>

        <section class="mt-6 space-y-4">
            <article v-for="(section, sectionIndex) in sections" :key="section.id" class="rounded-xl border border-white/10 bg-black/50 p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-xs text-muted-links">Section #{{ section.id }}</p>
                        <h3 class="text-lg text-white">{{ section.title }}</h3>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" class="border-white/20 text-white" :disabled="sectionIndex === 0" @click="moveSection(section.id, 'up')">Up</Button>
                        <Button variant="outline" class="border-white/20 text-white" :disabled="sectionIndex === sections.length - 1" @click="moveSection(section.id, 'down')">Down</Button>
                    </div>
                </div>

                <ul class="mt-3 space-y-2">
                    <li
                        v-for="(lecture, lectureIndex) in section.lectures ?? []"
                        :key="lecture.id"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-white/10 px-3 py-2"
                    >
                        <div class="text-sm text-muted-links-2">
                            #{{ lecture.id }} · {{ lecture.title }} · {{ lecture.type }}
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                class="border-white/20 text-white"
                                :disabled="lectureIndex === 0"
                                @click="moveLecture(section.id, lecture.id, 'up')"
                            >
                                Up
                            </Button>
                            <Button
                                variant="outline"
                                class="border-white/20 text-white"
                                :disabled="lectureIndex === (section.lectures?.length ?? 1) - 1"
                                @click="moveLecture(section.id, lecture.id, 'down')"
                            >
                                Down
                            </Button>
                        </div>
                    </li>
                </ul>
            </article>
        </section>

        <p v-if="loading" class="mt-4 text-sm text-muted-links">Loading curriculum...</p>
        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
