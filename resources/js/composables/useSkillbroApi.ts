import { ref } from 'vue';
import type {
    ApiError,
    PaginatedResponse,
    SkillbroCategory,
    SkillbroCourse,
    SkillbroEnrollment,
    SkillbroReview,
    SkillbroSection,
    SkillbroUser,
} from '@/types/skillbro-api';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

type RequestOptions = {
    method?: HttpMethod;
    body?: FormData | Record<string, unknown>;
    query?: Record<string, string | number | boolean | null | undefined>;
};

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

    async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
        const method = options.method ?? 'GET';
        const url = `${path}${toQueryString(options.query)}`;

        const headers = new Headers({
            Accept: 'application/json',
        });

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

    function getTags(): Promise<Array<{ id: number; name: string; slug: string }>> {
        return request<{ data: Array<{ id: number; name: string; slug: string }> }>('/api/v1/tags')
            .then((response) => response.data);
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

    function createSection(courseId: number, payload: { title: string; position?: number }): Promise<SkillbroSection> {
        return request<SkillbroSection>(`/api/v1/courses/${courseId}/sections`, {
            method: 'POST',
            body: payload,
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

    return {
        loading,
        error,
        request,
        getCourses,
        getCategories,
        getTags,
        createCourse,
        getCourse,
        createSection,
        getProfile,
        updateProfile,
        uploadAvatar,
        getEnrollments,
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
        getCourseReviews,
        createCourseReview,
        updateCourseReview,
        deleteCourseReview,
        replyToCourseReview,
    };
}
