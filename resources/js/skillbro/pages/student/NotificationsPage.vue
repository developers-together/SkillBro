<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroNotification } from '@/types/skillbro-api';

const api = useSkillbroApi();
const notifications = ref<SkillbroNotification[]>([]);
const status = ref('');

async function load(): Promise<void> {
    status.value = '';

    try {
        const response = await api.getNotifications();
        notifications.value = response.data;
    } catch (caught) {
        notifications.value = [];
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load notifications.';
    }
}

async function markRead(id: string): Promise<void> {
    await api.markNotificationRead(id);
    await load();
}

async function markAllRead(): Promise<void> {
    await api.markAllNotificationsRead();
    await load();
}

onMounted(load);
</script>

<template>
    <ShellLayout>
        <section class="mb-6 flex items-center justify-between">
            <h1 class="sb-display-section">Notifications</h1>
            <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="markAllRead">
                Mark all as read
            </Button>
        </section>

        <section class="space-y-3">
            <article
                v-for="notification in notifications"
                :key="notification.id"
                class="rounded-xl border border-white/10 bg-black/50 p-4"
            >
                <p class="text-sm text-muted-links">{{ notification.type }}</p>
                <pre class="mt-2 overflow-auto rounded bg-black/60 p-2 text-xs text-muted-links-2">{{ notification.data }}</pre>
                <Button
                    v-if="!notification.read_at"
                    class="mt-2 bg-[#c1fbd4] text-black hover:bg-[#97efb8]"
                    @click="markRead(notification.id)"
                >
                    Mark as read
                </Button>
            </article>
        </section>

        <p v-if="!notifications.length" class="mt-4 text-sm text-muted-links-2">
            You have no notifications yet.
        </p>
        <p v-if="status" class="mt-2 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
