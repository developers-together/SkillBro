<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroCourse } from '@/types/skillbro-api';

const api = useSkillbroApi();
const courses = ref<SkillbroCourse[]>([]);
const status = ref('');
const revenue = ref<{ completed_total: string; refunded_total: string } | null>(null);

const courseForm = reactive({
    course_id: '',
    title: '',
    description: '',
    price: '0',
    level: 'beginner',
});

const sectionForm = reactive({
    course_id: '',
    section_id: '',
    title: '',
});

const lectureForm = reactive({
    section_id: '',
    lecture_id: '',
    title: '',
    type: 'text' as 'video' | 'text' | 'quiz',
    content: '',
});

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
    } catch (caught) {
        courses.value = [];
        status.value = getErrorMessage(caught, 'Unable to load instructor courses.');
    }
}

async function createCourse(): Promise<void> {
    try {
        await api.createCourse({
            title: courseForm.title,
            description: courseForm.description,
            price: Number(courseForm.price),
            level: courseForm.level,
        });
        status.value = 'Course created.';
        await loadCourses();
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Course creation failed.';
    }
}

async function updateCourse(): Promise<void> {
    try {
        await api.updateCourse(Number(courseForm.course_id), {
            title: courseForm.title || undefined,
            description: courseForm.description || undefined,
            price: Number(courseForm.price),
            level: courseForm.level,
        });
        status.value = 'Course updated.';
        await loadCourses();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Course update failed.');
    }
}

async function submitCourse(courseId: number): Promise<void> {
    try {
        await api.submitCourse(courseId);
        status.value = `Course ${courseId} submitted.`;
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Submit failed.');
    }
}

async function archiveCourse(courseId: number): Promise<void> {
    try {
        await api.archiveCourse(courseId);
        status.value = `Course ${courseId} archived.`;
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Archive failed.');
    }
}

async function createSection(): Promise<void> {
    try {
        await api.createSection(Number(sectionForm.course_id), {
            title: sectionForm.title,
        });
        status.value = 'Section created.';
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Section creation failed.');
    }
}

async function updateSection(): Promise<void> {
    try {
        await api.updateSection(Number(sectionForm.course_id), Number(sectionForm.section_id), {
            title: sectionForm.title,
        });
        status.value = 'Section updated.';
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Section update failed.');
    }
}

async function createLecture(): Promise<void> {
    try {
        await api.createLecture(Number(lectureForm.section_id), {
            title: lectureForm.title,
            type: lectureForm.type,
            content: lectureForm.content || undefined,
        });
        status.value = 'Lecture created.';
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Lecture creation failed.');
    }
}

async function updateLecture(): Promise<void> {
    try {
        await api.updateLecture(Number(lectureForm.section_id), Number(lectureForm.lecture_id), {
            title: lectureForm.title || undefined,
            type: lectureForm.type,
            content: lectureForm.content || undefined,
        });
        status.value = 'Lecture updated.';
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Lecture update failed.');
    }
}

async function loadRevenue(): Promise<void> {
    try {
        const response = await api.getInstructorRevenueSummary();
        revenue.value = {
            completed_total: response.summary.completed_total,
            refunded_total: response.summary.refunded_total,
        };
    } catch {
        revenue.value = null;
    }
}

onMounted(async () => {
    await loadCourses();
    await loadRevenue();
});
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Instructor studio</h1>
            <p v-if="revenue" class="mt-2 text-muted-links-2">
                Revenue completed: ${{ revenue.completed_total }} · Refunded: ${{ revenue.refunded_total }}
            </p>
        </section>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <RouterLink to="/app/instructor/curriculum">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Curriculum builder</Button>
            </RouterLink>
            <RouterLink to="/app/instructor/reviews">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Reviews inbox</Button>
            </RouterLink>
            <RouterLink to="/app/instructor/revenue">
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]">Revenue analytics</Button>
            </RouterLink>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Course manager</h2>
                <div class="mt-3 grid gap-2">
                    <Input v-model="courseForm.course_id" placeholder="Course ID (for update)" class="bg-black/30 text-white" />
                    <Input v-model="courseForm.title" placeholder="Title" class="bg-black/30 text-white" />
                    <Input v-model="courseForm.description" placeholder="Description" class="bg-black/30 text-white" />
                    <Input v-model="courseForm.price" placeholder="Price" class="bg-black/30 text-white" />
                    <div class="flex flex-wrap gap-2">
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="createCourse">Create</Button>
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="updateCourse">Update</Button>
                    </div>
                </div>

                <div class="mt-5 space-y-2">
                    <div v-for="course in courses" :key="course.id" class="rounded-md border border-white/10 p-3">
                        <p class="text-white">{{ course.title }} ({{ course.status }})</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="submitCourse(course.id)">Submit</Button>
                            <Button variant="outline" class="border-white/20 text-white" @click="archiveCourse(course.id)">Archive</Button>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Curriculum manager</h2>
                <div class="mt-3 grid gap-2">
                    <Input v-model="sectionForm.course_id" placeholder="Course ID" class="bg-black/30 text-white" />
                    <Input v-model="sectionForm.section_id" placeholder="Section ID (for update)" class="bg-black/30 text-white" />
                    <Input v-model="sectionForm.title" placeholder="Section title" class="bg-black/30 text-white" />
                    <div class="flex flex-wrap gap-2">
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="createSection">Create section</Button>
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="updateSection">Update section</Button>
                    </div>
                </div>

                <div class="mt-5 grid gap-2">
                    <Input v-model="lectureForm.section_id" placeholder="Section ID" class="bg-black/30 text-white" />
                    <Input v-model="lectureForm.lecture_id" placeholder="Lecture ID (for update)" class="bg-black/30 text-white" />
                    <Input v-model="lectureForm.title" placeholder="Lecture title" class="bg-black/30 text-white" />
                    <Input v-model="lectureForm.content" placeholder="Lecture content (text)" class="bg-black/30 text-white" />
                    <div class="flex flex-wrap gap-2">
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="createLecture">Create lecture</Button>
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="updateLecture">Update lecture</Button>
                    </div>
                </div>
            </article>
        </section>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
