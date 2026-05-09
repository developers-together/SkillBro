<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />

    <div class="sb-cinematic min-h-screen text-white">
        <header class="mx-auto flex w-full max-w-[1500px] items-center justify-between px-6 py-6 md:px-10">
            <p class="font-display text-xl font-[500] tracking-[0.42px]">SkillBro</p>

            <nav class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="dashboard()"
                    class="inline-flex h-11 items-center justify-center rounded-full border border-white px-6 text-[16px] font-medium hover:bg-white hover:text-black"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-flex h-11 items-center justify-center rounded-full border border-white/60 px-6 text-[16px] font-medium hover:border-white"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-flex h-11 items-center justify-center rounded-full border border-white px-6 text-[16px] font-medium hover:bg-white hover:text-black"
                    >
                        Start free trial
                    </Link>
                </template>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid w-full max-w-[1500px] gap-10 px-6 pb-20 pt-12 md:px-10 lg:grid-cols-[1.2fr_1fr] lg:items-end lg:pb-28 lg:pt-20">
                <div>
                    <p class="mb-5 text-xs tracking-[0.72px] text-muted-links uppercase">E-commerce Learning Platform</p>
                    <h1 class="sb-display-hero max-w-4xl">
                        Run your course business like a cinematic storefront.
                    </h1>
                    <p class="mt-6 max-w-2xl text-[18px] font-[550] text-muted-links-2">
                        SkillBro gives creators the full commerce stack: courses, subscriptions, analytics, and growth automation in one dark-first experience.
                    </p>
                    <div class="mt-8">
                        <Link
                            :href="$page.props.auth?.user ? dashboard() : login()"
                            class="inline-flex h-11 items-center justify-center rounded-full border-2 border-white bg-black px-8 text-[16px] font-medium hover:bg-white hover:text-black"
                        >
                            Explore platform
                        </Link>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[20px] border border-white/10 bg-black/60">
                    <img
                        src="https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=80"
                        alt="Merchants managing storefront growth"
                        class="h-[440px] w-full object-cover"
                    />
                </div>
            </section>

            <section class="bg-canvas-cream px-6 py-14 text-black md:px-10 lg:py-20">
                <div class="mx-auto w-full max-w-[1500px]">
                    <p class="mb-3 text-xs tracking-[0.72px] uppercase">Transactional Track</p>
                    <h2 class="sb-display-section max-w-3xl text-black">Choose the operating model for your storefront team.</h2>

                    <div class="mt-10 grid gap-5 lg:grid-cols-3">
                        <article class="sb-card-light flex flex-col p-8">
                            <p class="text-[28px] font-[500] tracking-[0.42px]">Starter</p>
                            <p class="mt-3 text-[48px] leading-none font-[330] font-display">$0</p>
                            <p class="mt-4 text-base text-black/70">Launch one public course and collect emails.</p>
                            <Link
                                :href="$page.props.auth?.user ? dashboard() : login()"
                                class="mt-8 inline-flex h-11 items-center justify-center rounded-full bg-black px-6 text-white"
                            >
                                Get started
                            </Link>
                        </article>

                        <article class="flex flex-col rounded-xl border border-[#e4e4e7] bg-aloe-10 p-8 shadow-[0_8px_8px_rgba(0,0,0,0.10),0_4px_4px_rgba(0,0,0,0.10),0_2px_2px_rgba(0,0,0,0.10),0_0_0_1px_rgba(0,0,0,0.10)]">
                            <p class="text-[28px] font-[500] tracking-[0.42px]">Growth</p>
                            <p class="mt-3 text-[48px] leading-none font-[330] font-display">$39</p>
                            <p class="mt-4 text-base text-black/70">Unlock subscriptions, API workflows, and cohort analytics.</p>
                            <Link
                                :href="$page.props.auth?.user ? dashboard() : register()"
                                class="mt-8 inline-flex h-11 items-center justify-center rounded-full bg-black px-6 text-white"
                            >
                                Start free trial
                            </Link>
                        </article>

                        <article class="sb-card-light flex flex-col p-8">
                            <p class="text-[28px] font-[500] tracking-[0.42px]">Scale</p>
                            <p class="mt-3 text-[48px] leading-none font-[330] font-display">Custom</p>
                            <p class="mt-4 text-base text-black/70">Dedicated support, unlimited seats, advanced security controls.</p>
                            <Link
                                :href="$page.props.auth?.user ? dashboard() : login()"
                                class="mt-8 inline-flex h-11 items-center justify-center rounded-full border border-black px-6 text-black"
                            >
                                Talk to sales
                            </Link>
                        </article>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
