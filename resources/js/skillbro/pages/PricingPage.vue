<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const plans = [
    {
        name: 'Starter',
        price: '$0',
        subtitle: 'For first-time creators testing demand',
        features: ['1 course', 'Basic storefront', 'Community support'],
        cta: 'Start free',
        featured: false,
    },
    {
        name: 'Growth',
        price: '$29/mo',
        subtitle: 'For active instructors scaling revenue',
        features: ['Unlimited courses', 'Coupons + promotions', 'Revenue analytics'],
        cta: 'Start 14-day trial',
        featured: true,
    },
    {
        name: 'Scale',
        price: '$99/mo',
        subtitle: 'For teams running a real education business',
        features: ['Team roles', 'Priority support', 'Advanced moderation + exports'],
        cta: 'Talk to sales',
        featured: false,
    },
];
</script>

<template>
    <ShellLayout>
        <section class="mb-10 text-center">
            <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Pricing</p>
            <h1 class="mt-3 sb-display-section">Simple pricing for digital course commerce</h1>
            <p class="mx-auto mt-4 max-w-3xl text-muted-links-2">
                Start free, launch quickly, and move up when your storefront revenue grows.
            </p>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article
                v-for="plan in plans"
                :key="plan.name"
                :class="plan.featured ? 'bg-[#d4f9e0]' : 'bg-[#fbfbf5]'"
                class="rounded-xl border border-[#e4e4e7] p-6 text-black"
            >
                <p class="text-sm uppercase tracking-[0.72px] text-[#3f3f46]">{{ plan.name }}</p>
                <p class="mt-2 text-4xl">{{ plan.price }}</p>
                <p class="mt-2 text-sm text-[#52525b]">{{ plan.subtitle }}</p>

                <ul class="mt-4 space-y-2 text-sm text-[#27272a]">
                    <li v-for="feature in plan.features" :key="feature">• {{ feature }}</li>
                </ul>

                <RouterLink to="/auth/register" class="mt-5 block">
                    <Button class="w-full bg-black text-white hover:bg-[#27272a]">{{ plan.cta }}</Button>
                </RouterLink>
            </article>
        </section>
    </ShellLayout>
</template>
