<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6">
        <section class="rounded-2xl border border-white/10 bg-black/50 p-6 md:p-10">
            <p class="mb-4 text-xs tracking-[0.72px] text-muted-links uppercase">
                Merchant Performance
            </p>
            <h1 class="sb-display-hero max-w-4xl text-white">
                Build your storefront command center.
            </h1>
            <p class="mt-5 max-w-2xl text-base text-muted-links-2">
                Track growth metrics, optimize active courses, and ship updates from one cinematic workspace.
            </p>
        </section>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="sb-card-light p-6">
                <p class="text-sm text-muted-foreground">Active learners</p>
                <p class="mt-2 text-4xl font-[550] text-black">4,892</p>
                <p class="mt-2 text-sm text-black/70">+14.2% from last week</p>
            </div>
            <div class="sb-card-light p-6">
                <p class="text-sm text-muted-foreground">Course conversion</p>
                <p class="mt-2 text-4xl font-[550] text-black">12.8%</p>
                <p class="mt-2 text-sm text-black/70">Aloe tier is leading this month</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-surface-dark p-6 text-white">
                <p class="text-sm text-muted-links">Automation health</p>
                <p class="mt-2 text-4xl font-[550]">98.6%</p>
                <p class="mt-2 text-sm text-muted-links-mint">All API workflows operational</p>
            </div>
        </div>

        <section class="rounded-xl border border-white/10 bg-black/40 p-6">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-medium text-white">Revenue timeline</h2>
                <span class="rounded-full border border-white/15 px-4 py-1 text-xs tracking-[0.72px] text-muted-links uppercase">
                    Last 30 days
                </span>
            </div>
            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-black/60 p-4">
                    <p class="text-xs text-muted-links uppercase tracking-[0.72px]">Mon</p>
                    <p class="mt-3 text-lg text-white">$8,420</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/60 p-4">
                    <p class="text-xs text-muted-links uppercase tracking-[0.72px]">Tue</p>
                    <p class="mt-3 text-lg text-white">$9,110</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/60 p-4">
                    <p class="text-xs text-muted-links uppercase tracking-[0.72px]">Wed</p>
                    <p class="mt-3 text-lg text-white">$10,023</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/60 p-4">
                    <p class="text-xs text-muted-links uppercase tracking-[0.72px]">Thu</p>
                    <p class="mt-3 text-lg text-white">$11,304</p>
                </div>
            </div>
        </section>
    </div>
</template>
