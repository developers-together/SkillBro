import type { SkillbroCourse } from '@/types/skillbro-api';

const now = new Date().toISOString();

export const demoCourses: SkillbroCourse[] = [
    {
        id: 9101,
        title: 'Build a Profitable Course MVP',
        slug: 'course-mvp',
        description: 'Validate your topic and launch your first paid cohort without wasting months.',
        thumbnail: null,
        price: '0.00',
        status: 'published',
        level: 'beginner',
        language: 'en',
        created_at: now,
    },
    {
        id: 9102,
        title: 'Content Production System for Instructors',
        slug: 'content-system-instructors',
        description: 'Create lessons faster with repeatable scripts, templates, and recording workflows.',
        thumbnail: null,
        price: '39.00',
        status: 'published',
        level: 'intermediate',
        language: 'en',
        created_at: now,
    },
    {
        id: 9103,
        title: 'Advanced Course Monetization',
        slug: 'advanced-course-monetization',
        description: 'Bundle strategy, upsells, and retention-based offers for sustained revenue growth.',
        thumbnail: null,
        price: '79.00',
        status: 'published',
        level: 'advanced',
        language: 'en',
        created_at: now,
    },
    {
        id: 9001,
        title: 'Launch Your First Digital Course',
        slug: 'launch-first-course',
        description: 'A practical blueprint for niche selection, curriculum, and your first sale.',
        thumbnail: null,
        price: '0.00',
        status: 'published',
        level: 'beginner',
        language: 'en',
        created_at: now,
    },
    {
        id: 9002,
        title: 'High-Converting Course Sales Pages',
        slug: 'course-sales-pages',
        description: 'Write and design product pages that turn visitors into enrolled students.',
        thumbnail: null,
        price: '29.00',
        status: 'published',
        level: 'intermediate',
        language: 'en',
        created_at: now,
    },
    {
        id: 9003,
        title: 'Scale Course Revenue With Retention Loops',
        slug: 'course-revenue-retention',
        description: 'Use onboarding, completion systems, and feedback loops to increase repeat buyers.',
        thumbnail: null,
        price: '49.00',
        status: 'published',
        level: 'advanced',
        language: 'en',
        created_at: now,
    },
];

export function findDemoCourseByIdOrSlug(key: string | number): SkillbroCourse | null {
    const value = String(key);

    return demoCourses.find((course) => String(course.id) === value || course.slug === value) ?? null;
}
