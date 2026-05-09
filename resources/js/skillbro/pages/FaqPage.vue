<script setup lang="ts">
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const faqs = [
    {
        q: 'How do payouts work without Stripe lock-in?',
        a: 'SkillBro uses a provider-neutral payment model so checkout and webhooks stay portable.',
    },
    {
        q: 'Can I sell free and paid courses together?',
        a: 'Yes. Free enrollments and paid checkout flows run side by side in the same catalog.',
    },
    {
        q: 'Do students get completion proof?',
        a: 'Yes. Completed courses generate certificates with downloadable artifacts.',
    },
    {
        q: 'Can teams moderate content?',
        a: 'Admin tools include course moderation, user role management, and refund decisions.',
    },
];
</script>

<template>
    <ShellLayout>
        <section class="mb-10">
            <p class="text-xs uppercase tracking-[0.72px] text-muted-links">FAQ</p>
            <h1 class="mt-3 sb-display-section">Frequently asked questions</h1>
        </section>

        <section class="space-y-3">
            <article v-for="faq in faqs" :key="faq.q" class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-lg text-white">{{ faq.q }}</h2>
                <p class="mt-2 text-sm text-muted-links-2">{{ faq.a }}</p>
            </article>
        </section>
    </ShellLayout>
</template>
