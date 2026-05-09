import { createRouter, createWebHistory } from 'vue-router';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import AboutPage from '@/skillbro/pages/AboutPage.vue';
import AdminCoursesPage from '@/skillbro/pages/admin/AdminCoursesPage.vue';
import AdminOverviewPage from '@/skillbro/pages/admin/AdminOverviewPage.vue';
import AdminPaymentsPage from '@/skillbro/pages/admin/AdminPaymentsPage.vue';
import AdminStatsPage from '@/skillbro/pages/admin/AdminStatsPage.vue';
import AdminUsersPage from '@/skillbro/pages/admin/AdminUsersPage.vue';
import ForgotPasswordPage from '@/skillbro/pages/auth/ForgotPasswordPage.vue';
import LoginPage from '@/skillbro/pages/auth/LoginPage.vue';
import RegisterPage from '@/skillbro/pages/auth/RegisterPage.vue';
import ResetPasswordPage from '@/skillbro/pages/auth/ResetPasswordPage.vue';
import VerifyEmailPage from '@/skillbro/pages/auth/VerifyEmailPage.vue';
import CatalogPage from '@/skillbro/pages/CatalogPage.vue';
import ContactPage from '@/skillbro/pages/ContactPage.vue';
import CourseDetailPage from '@/skillbro/pages/CourseDetailPage.vue';
import FaqPage from '@/skillbro/pages/FaqPage.vue';
import HomePage from '@/skillbro/pages/HomePage.vue';
import InstructorCurriculumPage from '@/skillbro/pages/instructor/InstructorCurriculumPage.vue';
import InstructorRevenuePage from '@/skillbro/pages/instructor/InstructorRevenuePage.vue';
import InstructorReviewsPage from '@/skillbro/pages/instructor/InstructorReviewsPage.vue';
import InstructorStudioPage from '@/skillbro/pages/instructor/InstructorStudioPage.vue';
import InstructorProfilePage from '@/skillbro/pages/InstructorProfilePage.vue';
import PricingPage from '@/skillbro/pages/PricingPage.vue';
import AccountPage from '@/skillbro/pages/student/AccountPage.vue';
import EnrollmentDetailPage from '@/skillbro/pages/student/EnrollmentDetailPage.vue';
import LearningsPage from '@/skillbro/pages/student/LearningsPage.vue';
import NotificationsPage from '@/skillbro/pages/student/NotificationsPage.vue';
import PaymentsPage from '@/skillbro/pages/student/PaymentsPage.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: HomePage },
        { path: '/pricing', component: PricingPage },
        { path: '/about', component: AboutPage },
        { path: '/faq', component: FaqPage },
        { path: '/contact', component: ContactPage },
        { path: '/courses', component: CatalogPage },
        { path: '/courses/:slugOrId', component: CourseDetailPage },
        { path: '/instructors/:userId', component: InstructorProfilePage },

        { path: '/auth/login', component: LoginPage, meta: { guestOnly: true } },
        { path: '/auth/register', component: RegisterPage, meta: { guestOnly: true } },
        { path: '/auth/forgot-password', component: ForgotPasswordPage, meta: { guestOnly: true } },
        { path: '/auth/reset-password/:token', component: ResetPasswordPage, meta: { guestOnly: true } },
        { path: '/auth/verify-email', component: VerifyEmailPage },
        { path: '/auth/verify-email/:id/:hash', component: VerifyEmailPage },
        { path: '/auth/forgot', redirect: '/auth/forgot-password' },
        { path: '/auth/reset', redirect: '/auth/forgot-password' },
        { path: '/auth/verify', redirect: '/auth/login' },

        { path: '/app/learn', component: LearningsPage, meta: { requiresAuth: true, requiresVerified: true, roles: ['student'] } },
        { path: '/app/learn/:enrollmentId', component: EnrollmentDetailPage, meta: { requiresAuth: true, requiresVerified: true, roles: ['student'] } },
        { path: '/app/payments', component: PaymentsPage, meta: { requiresAuth: true, requiresVerified: true } },
        { path: '/app/notifications', component: NotificationsPage, meta: { requiresAuth: true } },
        { path: '/app/account', component: AccountPage, meta: { requiresAuth: true } },
        { path: '/app/instructor/studio', component: InstructorStudioPage, meta: { requiresAuth: true, roles: ['instructor', 'admin'] } },
        { path: '/app/instructor/curriculum', component: InstructorCurriculumPage, meta: { requiresAuth: true, roles: ['instructor', 'admin'] } },
        { path: '/app/instructor/reviews', component: InstructorReviewsPage, meta: { requiresAuth: true, roles: ['instructor', 'admin'] } },
        { path: '/app/instructor/revenue', component: InstructorRevenuePage, meta: { requiresAuth: true, roles: ['instructor', 'admin'] } },
        { path: '/app/admin/overview', component: AdminOverviewPage, meta: { requiresAuth: true, roles: ['admin'] } },
        { path: '/app/admin/users', component: AdminUsersPage, meta: { requiresAuth: true, roles: ['admin'] } },
        { path: '/app/admin/courses', component: AdminCoursesPage, meta: { requiresAuth: true, roles: ['admin'] } },
        { path: '/app/admin/payments', component: AdminPaymentsPage, meta: { requiresAuth: true, roles: ['admin'] } },
        { path: '/app/admin/stats', component: AdminStatsPage, meta: { requiresAuth: true, roles: ['admin'] } },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.beforeEach(async (to) => {
    const { isAuthenticated, currentUser, isVerified, bootstrapSession } = useAuthSession();

    if (isAuthenticated.value && !currentUser.value) {
        await bootstrapSession();
    }

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return '/auth/login';
    }

    if (to.meta.guestOnly && isAuthenticated.value) {
        return '/';
    }

    if (to.meta.requiresVerified) {
        if (!currentUser.value) {
            await bootstrapSession();
        }

        if (!isVerified.value) {
            await bootstrapSession();
        }

        if (!isVerified.value) {
            return '/auth/verify-email';
        }
    }

    const allowedRoles = to.meta.roles as string[] | undefined;

    if (allowedRoles && currentUser.value && !allowedRoles.includes(currentUser.value.role)) {
        return '/';
    }

    return true;
});

export default router;
