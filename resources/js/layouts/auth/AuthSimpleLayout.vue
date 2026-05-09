<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="sb-cinematic flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10"
    >
        <div class="w-full max-w-md">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium"
                    >
                        <div
                            class="mb-1 flex h-10 w-10 items-center justify-center rounded-full border border-white/30"
                        >
                            <AppLogoIcon
                                class="size-6 fill-current text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="sb-display-section text-[36px] text-white">
                            {{ title }}
                        </h1>
                        <p class="text-center text-base text-muted-links-2">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="sb-card-light p-8 text-black">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
