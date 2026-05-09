<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { Toaster } from '@/components/ui/sonner';
import { useAuthSession } from './composables/useAuthSession';

const { bootstrapSession } = useAuthSession();

onMounted(async () => {
    await bootstrapSession();
});
</script>

<template>
    <RouterView />
    <Toaster />
</template>

