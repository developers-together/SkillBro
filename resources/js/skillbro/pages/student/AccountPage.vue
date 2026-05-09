<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const { api, currentUser, bootstrapSession, isVerified } = useAuthSession();
const form = reactive({
    name: '',
    bio: '',
});
const status = ref('');

async function load(): Promise<void> {
    try {
        await bootstrapSession();
        form.name = currentUser.value?.name ?? '';
        form.bio = currentUser.value?.bio ?? '';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to load account details.';
    }
}

async function save(): Promise<void> {
    try {
        await api.updateProfile({
            name: form.name,
            bio: form.bio,
        });
        await load();
        status.value = 'Profile updated.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Profile update failed.';
    }
}

async function onAvatarSelected(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    await api.uploadAvatar(file);
    await load();
    status.value = 'Avatar uploaded.';
}

async function resendVerification(): Promise<void> {
    try {
        await api.resendVerificationEmail();
        status.value = 'Verification email sent. Check your inbox.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Unable to send verification email.';
    }
}

onMounted(load);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Account</h1>
            <p class="mt-2 text-muted-links-2">
                Verification: {{ isVerified ? 'Verified' : 'Not verified' }}
            </p>
            <div v-if="!isVerified" class="mt-3 flex flex-wrap gap-2">
                <Button variant="outline" class="border-white/20 text-white" @click="resendVerification">
                    Resend verification email
                </Button>
                <RouterLink to="/auth/verify-email">
                    <Button variant="outline" class="border-white/20 text-white">Open verify page</Button>
                </RouterLink>
            </div>
        </section>

        <section class="grid gap-4 rounded-xl border border-white/10 bg-black/50 p-5 md:grid-cols-2">
            <Input v-model="form.name" placeholder="Name" class="bg-black/30 text-white" />
            <Input v-model="form.bio" placeholder="Bio" class="bg-black/30 text-white" />
            <input type="file" accept="image/*" class="text-sm text-muted-links" @change="onAvatarSelected">
            <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="save">
                Save profile
            </Button>
        </section>

        <p v-if="status" class="mt-3 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
