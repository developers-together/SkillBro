<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-8 space-y-0.5'">
        <h2
            :class="
                variant === 'small'
                    ? 'mb-1 text-[20px] font-medium tracking-[0.3px]'
                    : 'sb-display-section text-[48px] md:text-[55px]'
            "
        >
            {{ title }}
        </h2>
        <p v-if="description" class="text-base font-[420] text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
