<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import type {
    SkillbroCategory,
    SkillbroCourse,
    SkillbroEnrollment,
    SkillbroLecture,
    SkillbroNotification,
    SkillbroQuizAttempt,
    SkillbroReview,
    SkillbroSection,
    SkillbroTag,
    SkillbroUser,
} from '@/types/skillbro-api';

type SectionKey =
    | 'overview'
    | 'auth'
    | 'users'
    | 'taxonomy'
    | 'courses'
    | 'enrollment'
    | 'payments'
    | 'reviews'
    | 'quizzes'
    | 'notifications'
    | 'admin';

const sections: Array<{ key: SectionKey; title: string }> = [
    { key: 'overview', title: 'Overview' },
    { key: 'auth', title: 'Auth' },
    { key: 'users', title: 'Users' },
    { key: 'taxonomy', title: 'Categories & Tags' },
    { key: 'courses', title: 'Courses / Sections / Lectures' },
    { key: 'enrollment', title: 'Enrollment' },
    { key: 'payments', title: 'Payments' },
    { key: 'reviews', title: 'Reviews' },
    { key: 'quizzes', title: 'Quizzes' },
    { key: 'notifications', title: 'Notifications' },
    { key: 'admin', title: 'Admin' },
];

const activeSection = ref<SectionKey>('overview');
const status = ref('');

const api = useSkillbroApi();
const { token, error } = api;

const me = ref<SkillbroUser | null>(null);
const instructorProfile = ref<{ instructor: SkillbroUser; courses: { data: SkillbroCourse[] } } | null>(null);
const categories = ref<SkillbroCategory[]>([]);
const tags = ref<SkillbroTag[]>([]);
const courses = ref<SkillbroCourse[]>([]);
const courseDetail = ref<SkillbroCourse | null>(null);
const courseSections = ref<SkillbroSection[]>([]);
const enrollments = ref<SkillbroEnrollment[]>([]);
const enrollmentDetail = ref<SkillbroEnrollment | null>(null);
const payments = ref<Array<{ id: number; course_id: number; amount: string; currency: string; status: string; created_at: string | null }>>([]);
const reviews = ref<SkillbroReview[]>([]);
const quizAttempts = ref<SkillbroQuizAttempt[]>([]);
const notifications = ref<SkillbroNotification[]>([]);
const adminUsers = ref<SkillbroUser[]>([]);
const adminCourses = ref<SkillbroCourse[]>([]);
const adminStats = ref<Record<string, unknown> | null>(null);

const registerForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    device_name: 'web',
});

const loginForm = reactive({
    email: '',
    password: '',
    device_name: 'web',
});

const resetForm = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const verifyForm = reactive({
    user_id: '',
    hash: '',
});

const profileForm = reactive({
    name: '',
    bio: '',
});

const instructorForm = reactive({
    user_id: '',
});

const categoryForm = reactive({
    category_id: '',
    name: '',
    parent_id: '',
});

const tagForm = reactive({
    tag_id: '',
    name: '',
});

const courseForm = reactive({
    course_id: '',
    title: '',
    description: '',
    category_id: '',
    price: '0',
    level: 'beginner',
    tags_csv: '',
});

const sectionForm = reactive({
    course_id: '',
    section_id: '',
    title: '',
    position: '',
    reorder_json: '[{"id":1,"position":0}]',
});

const lectureForm = reactive({
    section_id: '',
    lecture_id: '',
    title: '',
    type: 'text' as SkillbroLecture['type'],
    content: '',
    is_preview: false,
    position: '',
    reorder_json: '[{"id":1,"position":0}]',
});

const enrollmentForm = reactive({
    course_id: '',
    enrollment_id: '',
    lecture_id: '',
});

const paymentForm = reactive({
    course_id: '',
    payment_id: '',
});

const reviewForm = reactive({
    course_id: '',
    review_id: '',
    rating: '5',
    body: '',
    instructor_reply: '',
});

const quizForm = reactive({
    lecture_id: '',
    pass_percentage: '70',
    question: 'What is Laravel?',
    answer_a: 'A framework',
    answer_b: 'A database',
    question_id: '',
    answer_id: '',
});

const notificationForm = reactive({
    notification_id: '',
});

const adminForm = reactive({
    user_id: '',
    role: 'student',
    ban: false,
});

const isAuthenticated = computed(() => Boolean(token.value));

function setStatus(next: string): void {
    status.value = next;
}

function parseCsvNumbers(input: string): number[] {
    return input
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item.length > 0)
        .map((item) => Number(item))
        .filter((item) => Number.isFinite(item) && item > 0);
}

function parseReorderPayload(input: string, key: 'sections' | 'lectures'): Array<{ id: number; position: number }> {
    try {
        const parsed = JSON.parse(input) as Array<{ id: number; position: number }>;

        if (!Array.isArray(parsed)) {
            throw new Error('Payload must be an array.');
        }

        return parsed
            .map((item) => ({
                id: Number(item.id),
                position: Number(item.position),
            }))
            .filter((item) => Number.isFinite(item.id) && Number.isFinite(item.position));
    } catch {
        throw new Error(`Invalid ${key} reorder JSON.`);
    }
}

async function guarded(action: () => Promise<void>, successMessage: string): Promise<void> {
    try {
        await action();

        if (!status.value.startsWith('Checkout URL:')) {
            setStatus(successMessage);
        }
    } catch (caught) {
        const message = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Request failed.';

        setStatus(message);
    }
}

async function register(): Promise<void> {
    await guarded(async () => {
        const response = await api.register({
            name: registerForm.name,
            email: registerForm.email,
            password: registerForm.password,
            password_confirmation: registerForm.password_confirmation,
            device_name: registerForm.device_name,
        });

        me.value = response.user;
        profileForm.name = response.user.name;
        profileForm.bio = response.user.bio ?? '';
    }, 'Registered and authenticated.');
}

async function login(): Promise<void> {
    await guarded(async () => {
        const response = await api.login({
            email: loginForm.email,
            password: loginForm.password,
            device_name: loginForm.device_name,
        });

        me.value = response.user;
        profileForm.name = response.user.name;
        profileForm.bio = response.user.bio ?? '';
    }, 'Logged in.');
}

async function logout(): Promise<void> {
    await guarded(async () => {
        await api.logout();
        me.value = null;
    }, 'Logged out.');
}

async function forgotPassword(): Promise<void> {
    await guarded(async () => {
        await api.forgotPassword({
            email: loginForm.email || registerForm.email,
        });
    }, 'Reset email request sent.');
}

async function resetPassword(): Promise<void> {
    await guarded(async () => {
        await api.resetPassword({
            token: resetForm.token,
            email: resetForm.email,
            password: resetForm.password,
            password_confirmation: resetForm.password_confirmation,
        });
    }, 'Password reset successful.');
}

async function resendVerificationEmail(): Promise<void> {
    await guarded(async () => {
        await api.resendVerificationEmail();
    }, 'Verification email resent.');
}

async function verifyEmail(): Promise<void> {
    if (!verifyForm.user_id || !verifyForm.hash) {
        setStatus('user_id and hash are required.');

        return;
    }

    await guarded(async () => {
        await api.verifyEmail(Number(verifyForm.user_id), verifyForm.hash);
    }, 'Email verification endpoint called.');
}

async function loadProfile(): Promise<void> {
    await guarded(async () => {
        const response = await api.getProfile();
        me.value = response;
        profileForm.name = response.name;
        profileForm.bio = response.bio ?? '';
    }, 'Profile loaded.');
}

async function saveProfile(): Promise<void> {
    await guarded(async () => {
        const response = await api.updateProfile({
            name: profileForm.name,
            bio: profileForm.bio,
        });

        me.value = response;
    }, 'Profile updated.');
}

async function onAvatarSelected(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    await guarded(async () => {
        const response = await api.uploadAvatar(file);
        me.value = response;
    }, 'Avatar uploaded.');
}

async function loadInstructorProfile(): Promise<void> {
    if (!instructorForm.user_id) {
        setStatus('user_id is required.');

        return;
    }

    await guarded(async () => {
        instructorProfile.value = await api.getInstructorProfile(Number(instructorForm.user_id));
    }, 'Instructor profile loaded.');
}

async function loadCategories(): Promise<void> {
    await guarded(async () => {
        categories.value = await api.getCategories();
    }, 'Categories loaded.');
}

async function createCategory(): Promise<void> {
    if (!categoryForm.name) {
        setStatus('Category name is required.');

        return;
    }

    await guarded(async () => {
        const category = await api.createCategory({
            name: categoryForm.name,
            parent_id: categoryForm.parent_id ? Number(categoryForm.parent_id) : null,
        });

        categories.value.unshift(category);
    }, 'Category created.');
}

async function updateCategory(): Promise<void> {
    if (!categoryForm.category_id) {
        setStatus('category_id is required.');

        return;
    }

    await guarded(async () => {
        await api.updateCategory(Number(categoryForm.category_id), {
            name: categoryForm.name || undefined,
            parent_id: categoryForm.parent_id ? Number(categoryForm.parent_id) : null,
        });
    }, 'Category updated.');
}

async function deleteCategory(): Promise<void> {
    if (!categoryForm.category_id) {
        setStatus('category_id is required.');

        return;
    }

    await guarded(async () => {
        await api.deleteCategory(Number(categoryForm.category_id));
        categories.value = categories.value.filter((item) => item.id !== Number(categoryForm.category_id));
    }, 'Category deleted.');
}

async function loadTags(): Promise<void> {
    await guarded(async () => {
        tags.value = await api.getTags();
    }, 'Tags loaded.');
}

async function createTag(): Promise<void> {
    if (!tagForm.name) {
        setStatus('Tag name is required.');

        return;
    }

    await guarded(async () => {
        const tag = await api.createTag({ name: tagForm.name });
        tags.value.unshift(tag);
    }, 'Tag created.');
}

async function deleteTag(): Promise<void> {
    if (!tagForm.tag_id) {
        setStatus('tag_id is required.');

        return;
    }

    await guarded(async () => {
        await api.deleteTag(Number(tagForm.tag_id));
        tags.value = tags.value.filter((item) => item.id !== Number(tagForm.tag_id));
    }, 'Tag deleted.');
}

async function loadCourses(): Promise<void> {
    await guarded(async () => {
        const response = await api.getCourses();
        courses.value = response.data;
    }, 'Courses loaded.');
}

function buildCoursePayload(): Record<string, unknown> {
    const payload: Record<string, unknown> = {
        title: courseForm.title,
        description: courseForm.description,
        price: Number(courseForm.price),
        level: courseForm.level,
    };

    if (courseForm.category_id) {
        payload.category_id = Number(courseForm.category_id);
    }

    const tagsList = parseCsvNumbers(courseForm.tags_csv);

    if (tagsList.length > 0) {
        payload.tags = tagsList;
    }

    return payload;
}

async function createCourse(): Promise<void> {
    if (!courseForm.title || !courseForm.description) {
        setStatus('title and description are required.');

        return;
    }

    await guarded(async () => {
        const course = await api.createCourse(buildCoursePayload());
        courses.value.unshift(course);
    }, 'Course created.');
}

async function updateCourse(): Promise<void> {
    if (!courseForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        await api.updateCourse(Number(courseForm.course_id), buildCoursePayload());
    }, 'Course updated.');
}

async function deleteCourse(): Promise<void> {
    if (!courseForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        await api.deleteCourse(Number(courseForm.course_id));
        courses.value = courses.value.filter((item) => item.id !== Number(courseForm.course_id));
    }, 'Course deleted.');
}

async function loadCourseDetail(): Promise<void> {
    if (!courseForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        courseDetail.value = await api.getCourse(Number(courseForm.course_id));
    }, 'Course detail loaded.');
}

async function onThumbnailSelected(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file || !courseForm.course_id) {
        setStatus('Select a file and set course_id first.');

        return;
    }

    await guarded(async () => {
        await api.uploadCourseThumbnail(Number(courseForm.course_id), file);
    }, 'Course thumbnail uploaded.');
}

async function submitCourse(): Promise<void> {
    if (!courseForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        await api.submitCourse(Number(courseForm.course_id));
    }, 'Course submitted for review.');
}

async function publishCourse(): Promise<void> {
    if (!courseForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        await api.publishCourse(Number(courseForm.course_id));
    }, 'Course published.');
}

async function archiveCourse(): Promise<void> {
    if (!courseForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        await api.archiveCourse(Number(courseForm.course_id));
    }, 'Course archived.');
}

async function loadCourseSections(): Promise<void> {
    if (!sectionForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        courseSections.value = await api.getCourseSections(Number(sectionForm.course_id));
    }, 'Sections loaded.');
}

async function createSection(): Promise<void> {
    if (!sectionForm.course_id || !sectionForm.title) {
        setStatus('course_id and title are required.');

        return;
    }

    await guarded(async () => {
        await api.createSection(Number(sectionForm.course_id), {
            title: sectionForm.title,
            position: sectionForm.position ? Number(sectionForm.position) : undefined,
        });
    }, 'Section created.');
}

async function updateSection(): Promise<void> {
    if (!sectionForm.course_id || !sectionForm.section_id) {
        setStatus('course_id and section_id are required.');

        return;
    }

    await guarded(async () => {
        await api.updateSection(Number(sectionForm.course_id), Number(sectionForm.section_id), {
            title: sectionForm.title || undefined,
            position: sectionForm.position ? Number(sectionForm.position) : undefined,
        });
    }, 'Section updated.');
}

async function deleteSection(): Promise<void> {
    if (!sectionForm.course_id || !sectionForm.section_id) {
        setStatus('course_id and section_id are required.');

        return;
    }

    await guarded(async () => {
        await api.deleteSection(Number(sectionForm.course_id), Number(sectionForm.section_id));
    }, 'Section deleted.');
}

async function reorderSections(): Promise<void> {
    if (!sectionForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        const payload = parseReorderPayload(sectionForm.reorder_json, 'sections');
        await api.reorderSections(Number(sectionForm.course_id), payload);
    }, 'Sections reordered.');
}

async function createLecture(): Promise<void> {
    if (!lectureForm.section_id || !lectureForm.title) {
        setStatus('section_id and lecture title are required.');

        return;
    }

    await guarded(async () => {
        await api.createLecture(Number(lectureForm.section_id), {
            title: lectureForm.title,
            type: lectureForm.type,
            content: lectureForm.content || null,
            is_preview: lectureForm.is_preview,
            position: lectureForm.position ? Number(lectureForm.position) : undefined,
        });
    }, 'Lecture created.');
}

async function updateLecture(): Promise<void> {
    if (!lectureForm.section_id || !lectureForm.lecture_id) {
        setStatus('section_id and lecture_id are required.');

        return;
    }

    await guarded(async () => {
        await api.updateLecture(Number(lectureForm.section_id), Number(lectureForm.lecture_id), {
            title: lectureForm.title || undefined,
            type: lectureForm.type,
            content: lectureForm.content || null,
            is_preview: lectureForm.is_preview,
            position: lectureForm.position ? Number(lectureForm.position) : undefined,
        });
    }, 'Lecture updated.');
}

async function deleteLecture(): Promise<void> {
    if (!lectureForm.section_id || !lectureForm.lecture_id) {
        setStatus('section_id and lecture_id are required.');

        return;
    }

    await guarded(async () => {
        await api.deleteLecture(Number(lectureForm.section_id), Number(lectureForm.lecture_id));
    }, 'Lecture deleted.');
}

async function reorderLectures(): Promise<void> {
    if (!lectureForm.section_id) {
        setStatus('section_id is required.');

        return;
    }

    await guarded(async () => {
        const payload = parseReorderPayload(lectureForm.reorder_json, 'lectures');
        await api.reorderLectures(Number(lectureForm.section_id), payload);
    }, 'Lectures reordered.');
}

async function enrollCourse(): Promise<void> {
    if (!enrollmentForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        await api.createEnrollment(Number(enrollmentForm.course_id));
    }, 'Enrollment created.');
}

async function loadEnrollments(): Promise<void> {
    await guarded(async () => {
        const response = await api.getEnrollments();
        enrollments.value = response.data;
    }, 'Enrollments loaded.');
}

async function loadEnrollmentDetail(): Promise<void> {
    if (!enrollmentForm.enrollment_id) {
        setStatus('enrollment_id is required.');

        return;
    }

    await guarded(async () => {
        enrollmentDetail.value = await api.getEnrollment(Number(enrollmentForm.enrollment_id));
    }, 'Enrollment detail loaded.');
}

async function completeLecture(): Promise<void> {
    if (!enrollmentForm.enrollment_id || !enrollmentForm.lecture_id) {
        setStatus('enrollment_id and lecture_id are required.');

        return;
    }

    await guarded(async () => {
        await api.completeLecture(Number(enrollmentForm.enrollment_id), Number(enrollmentForm.lecture_id));
    }, 'Lecture marked complete.');
}

async function createCheckout(): Promise<void> {
    if (!paymentForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        const response = await api.createPaymentCheckout({
            course_id: Number(paymentForm.course_id),
        });

        if (response.url) {
            setStatus(`Checkout URL: ${response.url}`);
        }
    }, 'Checkout created.');
}

async function loadPayments(): Promise<void> {
    await guarded(async () => {
        const response = await api.getPayments();
        payments.value = response.data;
    }, 'Payments loaded.');
}

async function requestRefund(): Promise<void> {
    if (!paymentForm.payment_id) {
        setStatus('payment_id is required.');

        return;
    }

    await guarded(async () => {
        await api.requestPaymentRefund(Number(paymentForm.payment_id));
    }, 'Refund requested.');
}

async function loadReviews(): Promise<void> {
    if (!reviewForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        const response = await api.getCourseReviews(Number(reviewForm.course_id));
        reviews.value = response.data;
    }, 'Reviews loaded.');
}

async function createReview(): Promise<void> {
    if (!reviewForm.course_id) {
        setStatus('course_id is required.');

        return;
    }

    await guarded(async () => {
        const created = await api.createCourseReview(Number(reviewForm.course_id), {
            rating: Number(reviewForm.rating),
            body: reviewForm.body || null,
        });

        reviews.value.unshift(created);
    }, 'Review created.');
}

async function updateReview(): Promise<void> {
    if (!reviewForm.course_id || !reviewForm.review_id) {
        setStatus('course_id and review_id are required.');

        return;
    }

    await guarded(async () => {
        await api.updateCourseReview(Number(reviewForm.course_id), Number(reviewForm.review_id), {
            rating: Number(reviewForm.rating),
            body: reviewForm.body || null,
        });
    }, 'Review updated.');
}

async function deleteReview(): Promise<void> {
    if (!reviewForm.course_id || !reviewForm.review_id) {
        setStatus('course_id and review_id are required.');

        return;
    }

    await guarded(async () => {
        await api.deleteCourseReview(Number(reviewForm.course_id), Number(reviewForm.review_id));
    }, 'Review deleted.');
}

async function replyReview(): Promise<void> {
    if (!reviewForm.course_id || !reviewForm.review_id) {
        setStatus('course_id and review_id are required.');

        return;
    }

    await guarded(async () => {
        await api.replyToCourseReview(
            Number(reviewForm.course_id),
            Number(reviewForm.review_id),
            reviewForm.instructor_reply,
        );
    }, 'Instructor reply saved.');
}

function buildQuizQuestions(): Array<{
    question: string;
    answers: Array<{ answer: string; is_correct: boolean }>;
}> {
    return [
        {
            question: quizForm.question,
            answers: [
                { answer: quizForm.answer_a, is_correct: true },
                { answer: quizForm.answer_b, is_correct: false },
            ],
        },
    ];
}

async function createQuiz(): Promise<void> {
    if (!quizForm.lecture_id) {
        setStatus('lecture_id is required.');

        return;
    }

    await guarded(async () => {
        await api.createLectureQuiz(Number(quizForm.lecture_id), {
            pass_percentage: Number(quizForm.pass_percentage),
            questions: buildQuizQuestions(),
        });
    }, 'Quiz created.');
}

async function updateQuiz(): Promise<void> {
    if (!quizForm.lecture_id) {
        setStatus('lecture_id is required.');

        return;
    }

    await guarded(async () => {
        await api.updateLectureQuiz(Number(quizForm.lecture_id), {
            pass_percentage: Number(quizForm.pass_percentage),
            questions: buildQuizQuestions(),
        });
    }, 'Quiz updated.');
}

async function attemptQuiz(): Promise<void> {
    if (!quizForm.lecture_id || !quizForm.question_id || !quizForm.answer_id) {
        setStatus('lecture_id, question_id and answer_id are required.');

        return;
    }

    await guarded(async () => {
        await api.attemptLectureQuiz(Number(quizForm.lecture_id), {
            answers: [
                {
                    question_id: Number(quizForm.question_id),
                    answer_id: Number(quizForm.answer_id),
                },
            ],
        });
    }, 'Quiz attempt submitted.');
}

async function loadQuizAttempts(): Promise<void> {
    if (!quizForm.lecture_id) {
        setStatus('lecture_id is required.');

        return;
    }

    await guarded(async () => {
        const response = await api.getLectureQuizAttempts(Number(quizForm.lecture_id));
        quizAttempts.value = response.data;
    }, 'Quiz attempts loaded.');
}

async function loadNotifications(): Promise<void> {
    await guarded(async () => {
        const response = await api.getNotifications();
        notifications.value = response.data;
    }, 'Notifications loaded.');
}

async function readAllNotifications(): Promise<void> {
    await guarded(async () => {
        await api.markAllNotificationsRead();
    }, 'All notifications marked as read.');
}

async function readOneNotification(): Promise<void> {
    if (!notificationForm.notification_id) {
        setStatus('notification_id is required.');

        return;
    }

    await guarded(async () => {
        await api.markNotificationRead(notificationForm.notification_id);
    }, 'Notification marked as read.');
}

async function loadAdminUsers(): Promise<void> {
    await guarded(async () => {
        const response = await api.getAdminUsers();
        adminUsers.value = response.data;
    }, 'Admin users loaded.');
}

async function updateAdminRole(): Promise<void> {
    if (!adminForm.user_id) {
        setStatus('user_id is required.');

        return;
    }

    await guarded(async () => {
        await api.updateUserRole(Number(adminForm.user_id), adminForm.role as SkillbroUser['role']);
    }, 'User role updated.');
}

async function updateAdminBan(): Promise<void> {
    if (!adminForm.user_id) {
        setStatus('user_id is required.');

        return;
    }

    await guarded(async () => {
        await api.updateUserBan(Number(adminForm.user_id), adminForm.ban);
    }, 'User ban status updated.');
}

async function loadAdminCourses(): Promise<void> {
    await guarded(async () => {
        const response = await api.getAdminCourses();
        adminCourses.value = response.data;
    }, 'Admin courses loaded.');
}

async function loadAdminStats(): Promise<void> {
    await guarded(async () => {
        adminStats.value = await api.getAdminStats();
    }, 'Admin stats loaded.');
}

onMounted(async () => {
    if (isAuthenticated.value) {
        await loadProfile();
    }

    await Promise.all([
        loadCourses(),
        loadCategories(),
        loadTags(),
    ]);
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-zinc-100 to-stone-100 px-4 py-6 text-slate-900 dark:from-slate-950 dark:via-zinc-950 dark:to-stone-950 dark:text-slate-100">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <header class="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/70">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">SkillBro Frontend</p>
                        <h1 class="text-3xl font-semibold tracking-tight">Vue API Workspace</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline">{{ isAuthenticated ? 'Authenticated' : 'Guest' }}</Badge>
                        <Badge variant="secondary" class="max-w-[300px] truncate">{{ token || 'No API token' }}</Badge>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        v-for="section in sections"
                        :key="section.key"
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800"
                        :class="activeSection === section.key ? 'border-slate-900 bg-slate-900 text-white dark:border-slate-100 dark:bg-slate-100 dark:text-slate-900' : 'border-slate-300 dark:border-slate-700'"
                        @click="activeSection = section.key"
                    >
                        {{ section.title }}
                    </button>
                </div>
            </header>

            <Card v-if="status || error" class="border-slate-200/70 dark:border-slate-800">
                <CardHeader>
                    <CardTitle>Request Status</CardTitle>
                    <CardDescription>Latest API feedback</CardDescription>
                </CardHeader>
                <CardContent class="text-sm">
                    <p class="font-medium">{{ status || 'Ready' }}</p>
                    <p v-if="error" class="mt-2 text-rose-600 dark:text-rose-400">{{ error.message }}</p>
                </CardContent>
            </Card>

            <section v-if="activeSection === 'overview'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card v-for="section in sections.filter((item) => item.key !== 'overview')" :key="section.key" class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader>
                        <CardTitle>{{ section.title }}</CardTitle>
                        <CardDescription>Plan endpoint coverage via <code>/api/v1</code></CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button variant="outline" class="cursor-pointer" @click="activeSection = section.key">Open</Button>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'auth'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Register / Login / Logout</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="registerForm.name" placeholder="name" />
                        <Input v-model="registerForm.email" placeholder="email" />
                        <Input v-model="registerForm.password" type="password" placeholder="password" />
                        <Input v-model="registerForm.password_confirmation" type="password" placeholder="password_confirmation" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="register">Register</Button>
                            <Button variant="outline" class="cursor-pointer" @click="login">Login</Button>
                            <Button variant="outline" class="cursor-pointer" @click="logout">Logout</Button>
                        </div>
                        <Input v-model="loginForm.email" placeholder="login email" />
                        <Input v-model="loginForm.password" type="password" placeholder="login password" />
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Recovery / Verification</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="forgotPassword">Forgot password</Button>
                            <Button variant="outline" class="cursor-pointer" @click="resendVerificationEmail">Resend verification</Button>
                        </div>
                        <Input v-model="resetForm.token" placeholder="reset token" />
                        <Input v-model="resetForm.email" placeholder="reset email" />
                        <Input v-model="resetForm.password" type="password" placeholder="new password" />
                        <Input v-model="resetForm.password_confirmation" type="password" placeholder="confirm password" />
                        <Button variant="outline" class="cursor-pointer" @click="resetPassword">Reset password</Button>
                        <Input v-model="verifyForm.user_id" placeholder="verify user_id" />
                        <Input v-model="verifyForm.hash" placeholder="verify hash" />
                        <Button variant="secondary" class="cursor-pointer" @click="verifyEmail">Verify email endpoint</Button>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'users'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Own Profile</CardTitle><CardDescription>GET/PUT /user, POST /user/avatar</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="profileForm.name" placeholder="name" />
                        <textarea v-model="profileForm.bio" class="min-h-24 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" placeholder="bio" />
                        <input type="file" accept="image/*" class="text-sm" @change="onAvatarSelected">
                        <div class="flex gap-2">
                            <Button class="cursor-pointer" @click="loadProfile">Load</Button>
                            <Button variant="outline" class="cursor-pointer" @click="saveProfile">Save</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ me }}</pre>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Instructor Public Profile</CardTitle><CardDescription>GET /instructors/{user}</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="instructorForm.user_id" placeholder="instructor user_id" />
                        <Button class="cursor-pointer" @click="loadInstructorProfile">Load instructor profile</Button>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ instructorProfile }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'taxonomy'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Categories</CardTitle><CardDescription>GET/POST/PUT/DELETE /categories</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="categoryForm.category_id" placeholder="category_id for update/delete" />
                        <Input v-model="categoryForm.name" placeholder="name" />
                        <Input v-model="categoryForm.parent_id" placeholder="parent_id (optional)" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadCategories">Load</Button>
                            <Button variant="outline" class="cursor-pointer" @click="createCategory">Create</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateCategory">Update</Button>
                            <Button variant="outline" class="cursor-pointer" @click="deleteCategory">Delete</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ categories }}</pre>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Tags</CardTitle><CardDescription>GET/POST/DELETE /tags</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="tagForm.tag_id" placeholder="tag_id for delete" />
                        <Input v-model="tagForm.name" placeholder="tag name" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadTags">Load</Button>
                            <Button variant="outline" class="cursor-pointer" @click="createTag">Create</Button>
                            <Button variant="outline" class="cursor-pointer" @click="deleteTag">Delete</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ tags }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'courses'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Course Lifecycle</CardTitle><CardDescription>CRUD + submit/publish/archive + thumbnail</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="courseForm.course_id" placeholder="course_id for detail/update/delete/status" />
                        <Input v-model="courseForm.title" placeholder="title" />
                        <textarea v-model="courseForm.description" class="min-h-24 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" placeholder="description" />
                        <div class="grid grid-cols-2 gap-2">
                            <Input v-model="courseForm.category_id" placeholder="category_id" />
                            <Input v-model="courseForm.price" type="number" placeholder="price" />
                        </div>
                        <Input v-model="courseForm.tags_csv" placeholder="tags csv (e.g. 1,2,3)" />
                        <input type="file" accept="image/*" class="text-sm" @change="onThumbnailSelected">
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadCourses">Load list</Button>
                            <Button variant="outline" class="cursor-pointer" @click="loadCourseDetail">Load detail</Button>
                            <Button variant="outline" class="cursor-pointer" @click="createCourse">Create</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateCourse">Update</Button>
                            <Button variant="outline" class="cursor-pointer" @click="deleteCourse">Delete</Button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button variant="secondary" class="cursor-pointer" @click="submitCourse">Submit</Button>
                            <Button variant="secondary" class="cursor-pointer" @click="publishCourse">Publish</Button>
                            <Button variant="secondary" class="cursor-pointer" @click="archiveCourse">Archive</Button>
                        </div>
                        <pre class="max-h-56 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ courseDetail }}</pre>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Sections & Lectures</CardTitle><CardDescription>Course content management endpoints</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="sectionForm.course_id" placeholder="course_id" />
                        <Input v-model="sectionForm.section_id" placeholder="section_id" />
                        <Input v-model="sectionForm.title" placeholder="section title" />
                        <Input v-model="sectionForm.position" placeholder="section position" />
                        <textarea v-model="sectionForm.reorder_json" class="min-h-20 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadCourseSections">Load sections</Button>
                            <Button variant="outline" class="cursor-pointer" @click="createSection">Create section</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateSection">Update section</Button>
                            <Button variant="outline" class="cursor-pointer" @click="deleteSection">Delete section</Button>
                            <Button variant="outline" class="cursor-pointer" @click="reorderSections">Reorder sections</Button>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-2 border-t border-slate-200 pt-2 dark:border-slate-700">
                            <Input v-model="lectureForm.section_id" placeholder="lecture section_id" />
                            <Input v-model="lectureForm.lecture_id" placeholder="lecture_id" />
                            <Input v-model="lectureForm.title" placeholder="lecture title" />
                            <select v-model="lectureForm.type" class="h-9 w-full rounded-md border border-slate-300 bg-transparent px-3 text-sm dark:border-slate-700">
                                <option value="video">video</option>
                                <option value="text">text</option>
                                <option value="quiz">quiz</option>
                            </select>
                            <textarea v-model="lectureForm.content" class="min-h-20 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" placeholder="lecture content" />
                            <label class="flex items-center gap-2 text-sm"><input v-model="lectureForm.is_preview" type="checkbox"> preview lecture</label>
                            <Input v-model="lectureForm.position" placeholder="lecture position" />
                            <textarea v-model="lectureForm.reorder_json" class="min-h-20 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" />
                            <div class="flex flex-wrap gap-2">
                                <Button variant="outline" class="cursor-pointer" @click="createLecture">Create lecture</Button>
                                <Button variant="outline" class="cursor-pointer" @click="updateLecture">Update lecture</Button>
                                <Button variant="outline" class="cursor-pointer" @click="deleteLecture">Delete lecture</Button>
                                <Button variant="outline" class="cursor-pointer" @click="reorderLectures">Reorder lectures</Button>
                            </div>
                        </div>

                        <pre class="max-h-56 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ courseSections }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'enrollment'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Enrollment</CardTitle><CardDescription>POST /enrollments + GET list/detail</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="enrollmentForm.course_id" placeholder="course_id" />
                        <Input v-model="enrollmentForm.enrollment_id" placeholder="enrollment_id" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="enrollCourse">Enroll</Button>
                            <Button variant="outline" class="cursor-pointer" @click="loadEnrollments">Load list</Button>
                            <Button variant="outline" class="cursor-pointer" @click="loadEnrollmentDetail">Load detail</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ enrollments }}</pre>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Progress</CardTitle><CardDescription>POST /enrollments/{id}/lectures/{lecture}/complete</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="enrollmentForm.enrollment_id" placeholder="enrollment_id" />
                        <Input v-model="enrollmentForm.lecture_id" placeholder="lecture_id" />
                        <Button class="cursor-pointer" @click="completeLecture">Complete lecture</Button>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ enrollmentDetail }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'payments'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Checkout</CardTitle><CardDescription>POST /payments/checkout</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="paymentForm.course_id" placeholder="course_id" />
                        <Button class="cursor-pointer" @click="createCheckout">Create checkout</Button>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Payments & Refund</CardTitle><CardDescription>GET /payments, POST /payments/{id}/refund</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="paymentForm.payment_id" placeholder="payment_id" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadPayments">Load payments</Button>
                            <Button variant="outline" class="cursor-pointer" @click="requestRefund">Request refund</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ payments }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'reviews'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Student Review</CardTitle><CardDescription>POST/PUT/DELETE /courses/{id}/reviews</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="reviewForm.course_id" placeholder="course_id" />
                        <Input v-model="reviewForm.review_id" placeholder="review_id" />
                        <Input v-model="reviewForm.rating" type="number" min="1" max="5" placeholder="rating" />
                        <textarea v-model="reviewForm.body" class="min-h-24 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" placeholder="review body" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadReviews">Load</Button>
                            <Button variant="outline" class="cursor-pointer" @click="createReview">Create</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateReview">Update</Button>
                            <Button variant="outline" class="cursor-pointer" @click="deleteReview">Delete</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ reviews }}</pre>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Instructor Reply</CardTitle><CardDescription>POST /courses/{id}/reviews/{id}/reply</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <textarea v-model="reviewForm.instructor_reply" class="min-h-24 w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-700" placeholder="instructor reply" />
                        <Button class="cursor-pointer" @click="replyReview">Reply</Button>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'quizzes'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Quiz Authoring</CardTitle><CardDescription>POST/PUT /lectures/{id}/quiz</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="quizForm.lecture_id" placeholder="lecture_id" />
                        <Input v-model="quizForm.pass_percentage" type="number" min="1" max="100" placeholder="pass_percentage" />
                        <Input v-model="quizForm.question" placeholder="question" />
                        <div class="grid grid-cols-2 gap-2">
                            <Input v-model="quizForm.answer_a" placeholder="correct answer" />
                            <Input v-model="quizForm.answer_b" placeholder="wrong answer" />
                        </div>
                        <div class="flex gap-2">
                            <Button class="cursor-pointer" @click="createQuiz">Create</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateQuiz">Update</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Quiz Attempts</CardTitle><CardDescription>POST attempt, GET attempts</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="quizForm.question_id" placeholder="question_id" />
                        <Input v-model="quizForm.answer_id" placeholder="answer_id" />
                        <div class="flex gap-2">
                            <Button class="cursor-pointer" @click="attemptQuiz">Submit attempt</Button>
                            <Button variant="outline" class="cursor-pointer" @click="loadQuizAttempts">Load attempts</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ quizAttempts }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'notifications'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Notifications</CardTitle><CardDescription>GET /notifications + read actions</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="notificationForm.notification_id" placeholder="notification_id" />
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadNotifications">Load</Button>
                            <Button variant="outline" class="cursor-pointer" @click="readOneNotification">Read one</Button>
                            <Button variant="outline" class="cursor-pointer" @click="readAllNotifications">Read all</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ notifications }}</pre>
                    </CardContent>
                </Card>
            </section>

            <section v-if="activeSection === 'admin'" class="grid gap-4 lg:grid-cols-2">
                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>User Governance</CardTitle><CardDescription>GET/PUT /admin/users</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <Input v-model="adminForm.user_id" placeholder="user_id" />
                        <select v-model="adminForm.role" class="h-9 w-full rounded-md border border-slate-300 bg-transparent px-3 text-sm dark:border-slate-700">
                            <option value="student">student</option>
                            <option value="instructor">instructor</option>
                            <option value="admin">admin</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm"><input v-model="adminForm.ban" type="checkbox"> ban user</label>
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadAdminUsers">Load users</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateAdminRole">Update role</Button>
                            <Button variant="outline" class="cursor-pointer" @click="updateAdminBan">Update ban</Button>
                        </div>
                        <pre class="max-h-60 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ adminUsers }}</pre>
                    </CardContent>
                </Card>

                <Card class="border-slate-200/70 dark:border-slate-800">
                    <CardHeader><CardTitle>Platform Admin</CardTitle><CardDescription>GET /admin/courses + /admin/stats</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            <Button class="cursor-pointer" @click="loadAdminCourses">Load courses</Button>
                            <Button variant="outline" class="cursor-pointer" @click="loadAdminStats">Load stats</Button>
                        </div>
                        <pre class="max-h-56 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ adminCourses }}</pre>
                        <pre class="max-h-56 overflow-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-800">{{ adminStats }}</pre>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>
