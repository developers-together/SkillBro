import { ref } from 'vue';
import type {
    ApiError,
    PaginatedResponse,
    SkillbroCategory,
    SkillbroCourse,
    SkillbroEnrollment,
    SkillbroLecture,
    SkillbroPayment,
    SkillbroQuiz,
    SkillbroQuizAttempt,
    SkillbroReview,
    SkillbroSection,
    SkillbroTag,
    SkillbroUser,
} from '@/types/skillbro-api';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

type RequestOptions = {
    method?: HttpMethod;
    body?: FormData | Record<string, unknown>;
    query?: Record<string, string | number | boolean | null | undefined>;
    useAuth?: boolean;
};

const SKILLBRO_TOKEN_KEY = 'skillbro_api_token';

function toQueryString(query?: RequestOptions['query']): string {
    if (!query) {
        return '';
    }

    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query)) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        params.append(key, String(value));
    }

    const built = params.toString();

    return built.length > 0 ? `?${built}` : '';
}

function normalizeApiError(status: number, payload: unknown): ApiError {
    if (typeof payload === 'object' && payload !== null) {
        const typed = payload as { message?: string; errors?: Record<string, string[]> };

        return {
            status,
            message: typed.message ?? 'Request failed.',
            details: typed.errors,
        };
    }

    return {
        status,
        message: 'Request failed.',
    };
}

export function useSkillbroApi() {
    const loading = ref(false);
    const error = ref<ApiError | null>(null);
    const token = ref<string | null>(
        typeof window !== 'undefined' ? window.localStorage.getItem(SKILLBRO_TOKEN_KEY) : null,
    );

    function setToken(nextToken: string | null): void {
        token.value = nextToken;

        if (typeof window === 'undefined') {
            return;
        }

        if (nextToken) {
            window.localStorage.setItem(SKILLBRO_TOKEN_KEY, nextToken);
        } else {
            window.localStorage.removeItem(SKILLBRO_TOKEN_KEY);
        }
    }

    async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
        const method = options.method ?? 'GET';
        const url = `${path}${toQueryString(options.query)}`;
        const useAuth = options.useAuth ?? true;

        const headers = new Headers({
            Accept: 'application/json',
        });

        if (useAuth && token.value) {
            headers.set('Authorization', `Bearer ${token.value}`);
        }

        let body: BodyInit | undefined;

        if (options.body instanceof FormData) {
            body = options.body;
        } else if (options.body !== undefined) {
            headers.set('Content-Type', 'application/json');
            body = JSON.stringify(options.body);
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(url, {
                method,
                headers,
                credentials: 'include',
                body,
            });

            const hasJsonBody = response.headers
                .get('content-type')
                ?.includes('application/json');

            const payload = hasJsonBody ? await response.json() : null;

            if (!response.ok) {
                throw normalizeApiError(response.status, payload);
            }

            return payload as T;
        } catch (caught) {
            if (typeof caught === 'object' && caught !== null && 'status' in caught) {
                error.value = caught as ApiError;

                throw caught;
            }

            const fallback: ApiError = {
                status: 0,
                message: 'Network error. Please try again.',
            };

            error.value = fallback;

            throw fallback;
        } finally {
            loading.value = false;
        }
    }

    function unwrapPaginated<T>(response: { data: T[]; meta?: PaginatedResponse<T>['meta'] }): PaginatedResponse<T> {
        return {
            data: response.data,
            meta: response.meta,
        };
    }

    function getCourses(query?: RequestOptions['query']): Promise<PaginatedResponse<SkillbroCourse>> {
        return request<{ data: SkillbroCourse[]; meta: PaginatedResponse<SkillbroCourse>['meta'] }>(
            '/api/v1/courses',
            { query },
        ).then(unwrapPaginated);
    }

    function getCategories(): Promise<SkillbroCategory[]> {
        return request<{ data: SkillbroCategory[] }>('/api/v1/categories').then(
            (response) => response.data,
        );
    }

    function getTags(): Promise<SkillbroTag[]> {
        return request<{ data: SkillbroTag[] }>('/api/v1/tags')
            .then((response) => response.data);
    }

    function createCategory(payload: {
        name: string;
        parent_id?: number | null;
    }): Promise<SkillbroCategory> {
        return request<SkillbroCategory>('/api/v1/categories', {
            method: 'POST',
            body: payload,
        });
    }

    function updateCategory(
        categoryId: number,
        payload: { name?: string; parent_id?: number | null },
    ): Promise<SkillbroCategory> {
        return request<SkillbroCategory>(`/api/v1/categories/${categoryId}`, {
            method: 'PUT',
            body: payload,
        });
    }

    function deleteCategory(categoryId: number): Promise<null> {
        return request<null>(`/api/v1/categories/${categoryId}`, {
            method: 'DELETE',
        });
    }

    function createTag(payload: { name: string }): Promise<SkillbroTag> {
        return request<SkillbroTag>('/api/v1/tags', {
            method: 'POST',
            body: payload,
        });
    }

    function deleteTag(tagId: number): Promise<null> {
        return request<null>(`/api/v1/tags/${tagId}`, {
            method: 'DELETE',
        });
    }

    function createCourse(payload: Record<string, unknown>): Promise<SkillbroCourse> {
        return request<SkillbroCourse>('/api/v1/courses', {
            method: 'POST',
            body: payload,
        });
    }

    function getCourse(courseId: number): Promise<SkillbroCourse> {
        return request<SkillbroCourse>(`/api/v1/courses/${courseId}`);
    }

    function updateCourse(courseId: number, payload: Record<string, unknown>): Promise<SkillbroCourse> {
        return request<SkillbroCourse>(`/api/v1/courses/${courseId}`, {
            method: 'PUT',
            body: payload,
        });
    }

    function deleteCourse(courseId: number): Promise<null> {
        return request<null>(`/api/v1/courses/${courseId}`, {
            method: 'DELETE',
        });
    }

    function uploadCourseThumbnail(courseId: number, file: File): Promise<{ thumbnail: string }> {
        const formData = new FormData();
        formData.set('thumbnail', file);

        return request<{ thumbnail: string }>(`/api/v1/courses/${courseId}/thumbnail`, {
            method: 'POST',
            body: formData,
        });
    }

    function submitCourse(courseId: number): Promise<{ status: string }> {
        return request<{ status: string }>(`/api/v1/courses/${courseId}/submit`, {
            method: 'POST',
        });
    }

    function publishCourse(courseId: number): Promise<{ status: string }> {
        return request<{ status: string }>(`/api/v1/courses/${courseId}/publish`, {
            method: 'POST',
        });
    }

    function archiveCourse(courseId: number): Promise<{ status: string }> {
        return request<{ status: string }>(`/api/v1/courses/${courseId}/archive`, {
            method: 'POST',
        });
    }

    function getCourseSections(courseId: number): Promise<SkillbroSection[]> {
        return request<{ data: SkillbroSection[] }>(`/api/v1/courses/${courseId}/sections`)
            .then((response) => response.data);
    }

    function createSection(courseId: number, payload: { title: string; position?: number }): Promise<SkillbroSection> {
        return request<SkillbroSection>(`/api/v1/courses/${courseId}/sections`, {
            method: 'POST',
            body: payload,
        });
    }

    function updateSection(
        courseId: number,
        sectionId: number,
        payload: { title?: string; position?: number },
    ): Promise<SkillbroSection> {
        return request<SkillbroSection>(`/api/v1/courses/${courseId}/sections/${sectionId}`, {
            method: 'PUT',
            body: payload,
        });
    }

    function deleteSection(courseId: number, sectionId: number): Promise<null> {
        return request<null>(`/api/v1/courses/${courseId}/sections/${sectionId}`, {
            method: 'DELETE',
        });
    }

    function reorderSections(
        courseId: number,
        sections: Array<{ id: number; position: number }>,
    ): Promise<{ message: string }> {
        return request<{ message: string }>(`/api/v1/courses/${courseId}/sections/reorder`, {
            method: 'POST',
            body: {
                sections,
            },
        });
    }

    function createLecture(
        sectionId: number,
        payload: {
            title: string;
            type: SkillbroLecture['type'];
            content?: string | null;
            is_preview?: boolean;
            position?: number;
        },
    ): Promise<SkillbroLecture> {
        return request<SkillbroLecture>(`/api/v1/sections/${sectionId}/lectures`, {
            method: 'POST',
            body: payload,
        });
    }

    function updateLecture(
        sectionId: number,
        lectureId: number,
        payload: {
            title?: string;
            type?: SkillbroLecture['type'];
            content?: string | null;
            is_preview?: boolean;
            position?: number;
        },
    ): Promise<SkillbroLecture> {
        return request<SkillbroLecture>(`/api/v1/sections/${sectionId}/lectures/${lectureId}`, {
            method: 'PUT',
            body: payload,
        });
    }

    function deleteLecture(sectionId: number, lectureId: number): Promise<null> {
        return request<null>(`/api/v1/sections/${sectionId}/lectures/${lectureId}`, {
            method: 'DELETE',
        });
    }

    function uploadLectureVideo(
        lectureId: number,
        file: File,
        videoDuration?: number,
    ): Promise<SkillbroLecture> {
        const formData = new FormData();
        formData.set('video', file);

        if (videoDuration !== undefined) {
            formData.set('video_duration', String(videoDuration));
        }

        return request<SkillbroLecture>(`/api/v1/lectures/${lectureId}/video`, {
            method: 'POST',
            body: formData,
        });
    }

    function reorderLectures(
        sectionId: number,
        lectures: Array<{ id: number; position: number }>,
    ): Promise<{ message: string }> {
        return request<{ message: string }>(`/api/v1/sections/${sectionId}/lectures/reorder`, {
            method: 'POST',
            body: {
                lectures,
            },
        });
    }

    function getProfile(): Promise<SkillbroUser> {
        return request<SkillbroUser>('/api/v1/user');
    }

    function updateProfile(payload: { name?: string; bio?: string | null }): Promise<SkillbroUser> {
        return request<SkillbroUser>('/api/v1/user', {
            method: 'PUT',
            body: payload,
        });
    }

    function uploadAvatar(file: File): Promise<SkillbroUser> {
        const formData = new FormData();
        formData.set('avatar', file);

        return request<SkillbroUser>('/api/v1/user/avatar', {
            method: 'POST',
            body: formData,
        });
    }

    function getEnrollments(): Promise<PaginatedResponse<SkillbroEnrollment>> {
        return request<{ data: SkillbroEnrollment[]; meta: PaginatedResponse<SkillbroEnrollment>['meta'] }>(
            '/api/v1/enrollments',
        ).then(unwrapPaginated);
    }

    function createEnrollment(courseId: number): Promise<SkillbroEnrollment> {
        return request<SkillbroEnrollment>('/api/v1/enrollments', {
            method: 'POST',
            body: {
                course_id: courseId,
            },
        });
    }

    function getEnrollment(enrollmentId: number): Promise<SkillbroEnrollment> {
        return request<SkillbroEnrollment>(`/api/v1/enrollments/${enrollmentId}`);
    }

    function completeLecture(enrollmentId: number, lectureId: number): Promise<{ completed_at: string | null }> {
        return request<{ completed_at: string | null }>(
            `/api/v1/enrollments/${enrollmentId}/lectures/${lectureId}/complete`,
            {
                method: 'POST',
            },
        );
    }

    function getAdminUsers(): Promise<PaginatedResponse<SkillbroUser>> {
        return request<{ data: SkillbroUser[]; meta: PaginatedResponse<SkillbroUser>['meta'] }>(
            '/api/v1/admin/users',
        ).then(unwrapPaginated);
    }

    function updateUserBan(userId: number, ban: boolean): Promise<SkillbroUser> {
        return request<SkillbroUser>(`/api/v1/admin/users/${userId}/ban`, {
            method: 'PUT',
            body: { ban },
        });
    }

    function updateUserRole(userId: number, role: SkillbroUser['role']): Promise<SkillbroUser> {
        return request<SkillbroUser>(`/api/v1/admin/users/${userId}/role`, {
            method: 'PUT',
            body: { role },
        });
    }

    function getAdminCourses(): Promise<PaginatedResponse<SkillbroCourse>> {
        return request<{ data: SkillbroCourse[]; meta: PaginatedResponse<SkillbroCourse>['meta'] }>(
            '/api/v1/admin/courses',
        ).then(unwrapPaginated);
    }

    function getNotifications(): Promise<PaginatedResponse<{
        id: string;
        type: string;
        data: Record<string, unknown>;
        read_at: string | null;
        created_at: string | null;
    }>> {
        return request<{
            data: Array<{
                id: string;
                type: string;
                data: Record<string, unknown>;
                read_at: string | null;
                created_at: string | null;
            }>;
            meta: PaginatedResponse<{
                id: string;
                type: string;
                data: Record<string, unknown>;
                read_at: string | null;
                created_at: string | null;
            }>['meta'];
        }>('/api/v1/notifications').then(unwrapPaginated);
    }

    function markAllNotificationsRead(): Promise<{ message: string }> {
        return request<{ message: string }>('/api/v1/notifications/read-all', {
            method: 'POST',
        });
    }

    function markNotificationRead(notificationId: string): Promise<{
        id: string;
        type: string;
        data: Record<string, unknown>;
        read_at: string | null;
        created_at: string | null;
    }> {
        return request<{
            id: string;
            type: string;
            data: Record<string, unknown>;
            read_at: string | null;
            created_at: string | null;
        }>(`/api/v1/notifications/${notificationId}`, {
            method: 'PUT',
        });
    }

    function getAdminStats(): Promise<{
        users: { total: number; students: number; instructors: number; admins: number };
        courses: { total: number; by_status: { draft: number; pending: number; published: number; archived: number } };
        enrollments: { total: number; completed: number };
    }> {
        return request('/api/v1/admin/stats');
    }

    function getInstructorProfile(userId: number): Promise<{
        instructor: SkillbroUser;
        courses: PaginatedResponse<SkillbroCourse>;
    }> {
        return request<{
            instructor: SkillbroUser;
            courses: PaginatedResponse<SkillbroCourse>;
        }>(`/api/v1/instructors/${userId}`);
    }

    function register(payload: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        device_name?: string;
    }): Promise<{ token: string; user: SkillbroUser }> {
        return request<{ token: string; user: SkillbroUser }>('/api/v1/auth/register', {
            method: 'POST',
            body: payload,
            useAuth: false,
        }).then((response) => {
            setToken(response.token);

            return response;
        });
    }

    function login(payload: { email: string; password: string; device_name?: string }): Promise<{ token: string; user: SkillbroUser }> {
        return request<{ token: string; user: SkillbroUser }>('/api/v1/auth/login', {
            method: 'POST',
            body: payload,
            useAuth: false,
        }).then((response) => {
            setToken(response.token);

            return response;
        });
    }

    function logout(): Promise<{ message: string }> {
        return request<{ message: string }>('/api/v1/auth/logout', {
            method: 'POST',
        }).then((response) => {
            setToken(null);

            return response;
        });
    }

    function forgotPassword(payload: { email: string }): Promise<{ message: string }> {
        return request<{ message: string }>('/api/v1/auth/forgot-password', {
            method: 'POST',
            body: payload,
            useAuth: false,
        });
    }

    function resetPassword(payload: {
        token: string;
        email: string;
        password: string;
        password_confirmation: string;
    }): Promise<{ message: string }> {
        return request<{ message: string }>('/api/v1/auth/reset-password', {
            method: 'POST',
            body: payload,
            useAuth: false,
        });
    }

    function resendVerificationEmail(): Promise<{ message: string }> {
        return request<{ message: string }>('/api/v1/auth/email/resend', {
            method: 'POST',
        });
    }

    function verifyEmail(userId: number, hash: string): Promise<{ message?: string }> {
        return request<{ message?: string }>(`/api/v1/auth/verify-email/${userId}/${hash}`, {
            method: 'GET',
            useAuth: false,
        });
    }

    function createPaymentCheckout(payload: {
        course_id: number;
        success_url?: string;
        cancel_url?: string;
    }): Promise<{ url?: string; session_id?: string }> {
        return request<{ url?: string; session_id?: string }>('/api/v1/payments/checkout', {
            method: 'POST',
            body: payload,
        });
    }

    function getPayments(): Promise<PaginatedResponse<SkillbroPayment>> {
        return request<{
            data: SkillbroPayment[];
            meta: PaginatedResponse<SkillbroPayment>['meta'];
        }>('/api/v1/payments').then(unwrapPaginated);
    }

    function requestPaymentRefund(paymentId: number): Promise<{ message?: string }> {
        return request<{ message?: string }>(`/api/v1/payments/${paymentId}/refund`, {
            method: 'POST',
        });
    }

    function getAdminPayments(): Promise<PaginatedResponse<SkillbroPayment>> {
        return request<{
            data: SkillbroPayment[];
            meta: PaginatedResponse<SkillbroPayment>['meta'];
        }>('/api/v1/admin/payments').then(unwrapPaginated);
    }

    function decideAdminPaymentRefund(paymentId: number, approve: boolean): Promise<SkillbroPayment> {
        return request<SkillbroPayment>(`/api/v1/admin/payments/${paymentId}/refund`, {
            method: 'PUT',
            body: {
                approve,
            },
        });
    }

    function getCourseReviews(courseId: number): Promise<PaginatedResponse<SkillbroReview>> {
        return request<{ data: SkillbroReview[]; meta: PaginatedResponse<SkillbroReview>['meta'] }>(
            `/api/v1/courses/${courseId}/reviews`,
        ).then(unwrapPaginated);
    }

    function createCourseReview(courseId: number, payload: { rating: number; body?: string | null }): Promise<SkillbroReview> {
        return request<SkillbroReview>(`/api/v1/courses/${courseId}/reviews`, {
            method: 'POST',
            body: payload,
        });
    }

    function updateCourseReview(
        courseId: number,
        reviewId: number,
        payload: { rating?: number; body?: string | null },
    ): Promise<SkillbroReview> {
        return request<SkillbroReview>(`/api/v1/courses/${courseId}/reviews/${reviewId}`, {
            method: 'PUT',
            body: payload,
        });
    }

    function deleteCourseReview(courseId: number, reviewId: number): Promise<null> {
        return request<null>(`/api/v1/courses/${courseId}/reviews/${reviewId}`, {
            method: 'DELETE',
        });
    }

    function replyToCourseReview(courseId: number, reviewId: number, instructorReply: string): Promise<SkillbroReview> {
        return request<SkillbroReview>(`/api/v1/courses/${courseId}/reviews/${reviewId}/reply`, {
            method: 'POST',
            body: {
                instructor_reply: instructorReply,
            },
        });
    }

    function createLectureQuiz(
        lectureId: number,
        payload: {
            pass_percentage?: number;
            questions: Array<{
                question: string;
                position?: number;
                answers: Array<{ answer: string; is_correct: boolean }>;
            }>;
        },
    ): Promise<SkillbroQuiz> {
        return request<SkillbroQuiz>(`/api/v1/lectures/${lectureId}/quiz`, {
            method: 'POST',
            body: payload,
        });
    }

    function updateLectureQuiz(
        lectureId: number,
        payload: {
            pass_percentage?: number;
            questions?: Array<{
                question: string;
                position?: number;
                answers: Array<{ answer: string; is_correct: boolean }>;
            }>;
        },
    ): Promise<SkillbroQuiz> {
        return request<SkillbroQuiz>(`/api/v1/lectures/${lectureId}/quiz`, {
            method: 'PUT',
            body: payload,
        });
    }

    function attemptLectureQuiz(
        lectureId: number,
        payload: { answers: Array<{ question_id: number; answer_id: number }> },
    ): Promise<SkillbroQuizAttempt> {
        return request<SkillbroQuizAttempt>(`/api/v1/lectures/${lectureId}/quiz/attempt`, {
            method: 'POST',
            body: payload,
        });
    }

    function getLectureQuizAttempts(lectureId: number): Promise<PaginatedResponse<SkillbroQuizAttempt>> {
        return request<{ data: SkillbroQuizAttempt[]; meta: PaginatedResponse<SkillbroQuizAttempt>['meta'] }>(
            `/api/v1/lectures/${lectureId}/quiz/attempts`,
        ).then(unwrapPaginated);
    }

    return {
        loading,
        error,
        token,
        setToken,
        request,
        register,
        login,
        logout,
        forgotPassword,
        resetPassword,
        resendVerificationEmail,
        verifyEmail,
        getCourses,
        getCategories,
        getTags,
        createCategory,
        updateCategory,
        deleteCategory,
        createTag,
        deleteTag,
        createCourse,
        getCourse,
        updateCourse,
        deleteCourse,
        uploadCourseThumbnail,
        submitCourse,
        publishCourse,
        archiveCourse,
        getCourseSections,
        createSection,
        updateSection,
        deleteSection,
        reorderSections,
        createLecture,
        updateLecture,
        deleteLecture,
        reorderLectures,
        uploadLectureVideo,
        getProfile,
        updateProfile,
        uploadAvatar,
        getEnrollments,
        createEnrollment,
        getEnrollment,
        completeLecture,
        getAdminUsers,
        updateUserBan,
        updateUserRole,
        getAdminCourses,
        getNotifications,
        markAllNotificationsRead,
        markNotificationRead,
        getAdminStats,
        createPaymentCheckout,
        getPayments,
        requestPaymentRefund,
        getAdminPayments,
        decideAdminPaymentRefund,
        getCourseReviews,
        createCourseReview,
        updateCourseReview,
        deleteCourseReview,
        replyToCourseReview,
        createLectureQuiz,
        updateLectureQuiz,
        attemptLectureQuiz,
        getLectureQuizAttempts,
        getInstructorProfile,
    };
}
