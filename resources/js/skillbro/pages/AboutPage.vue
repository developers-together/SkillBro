<script setup lang="ts">
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
</script>

<template>
    <ShellLayout>
        <section class="mb-10">
            <p class="text-xs uppercase tracking-[0.72px] text-muted-links">About SkillBro</p>
            <h1 class="mt-3 sb-display-section">A commerce-first platform for creators who teach</h1>
            <p class="mt-4 max-w-4xl text-muted-links-2">
                SkillBro is built around one mission: help instructors transform expertise into reliable digital income.
                We blend cinematic storefront design with serious backend operations, so selling knowledge feels premium.
            </p>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Storefront quality</h2>
                <p class="mt-2 text-sm text-muted-links-2">Editorial product pages designed for conversion and trust.</p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Commerce controls</h2>
                <p class="mt-2 text-sm text-muted-links-2">Checkout flows, refunds, moderation, and payment lifecycle built in.</p>
            </article>
            <article class="rounded-xl border border-white/10 bg-black/50 p-5">
                <h2 class="text-xl text-white">Learning outcomes</h2>
                <p class="mt-2 text-sm text-muted-links-2">Progress tracking, quizzes, reviews, and certificates for credibility.</p>
            </article>
        </section>
    </ShellLayout>
</template>
