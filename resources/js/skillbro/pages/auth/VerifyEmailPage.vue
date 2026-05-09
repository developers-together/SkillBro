<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { Button } from '@/components/ui/button';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const { api, isAuthenticated, isVerified, bootstrapSession } = useAuthSession();
const route = useRoute();
const status = ref('');
const loading = ref(false);

const hasSignedParams = computed(() => {
    return Boolean(route.params.id && route.params.hash && route.query.expires && route.query.signature);
});

async function verifySignedLink(): Promise<void> {
    if (!hasSignedParams.value) {
        status.value = 'Open the verification link from your inbox, or resend it below.';

        return;
    }

    loading.value = true;
    status.value = '';

    try {
        await api.verifyEmail(Number(route.params.id), String(route.params.hash), {
            expires: String(route.query.expires ?? ''),
            signature: String(route.query.signature ?? ''),
        });
        await bootstrapSession();
        status.value = 'Email verified successfully.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Verification failed.';
    } finally {
        loading.value = false;
    }
}

async function resend(): Promise<void> {
    if (!isAuthenticated.value) {
        status.value = 'Log in first, then resend the verification email.';

        return;
    }

    status.value = '';

    try {
        await api.resendVerificationEmail();
        status.value = 'Verification email resent.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Resend failed.';
    }
}

onMounted(async () => {
    await bootstrapSession();
    await verifySignedLink();
});
</script>

<template>
    <ShellLayout>
        <div class="flex min-h-[calc(100vh-210px)] items-center justify-center">
            <section class="mx-auto w-full max-w-md rounded-xl border border-white/10 bg-black/50 p-6">
                <h1 class="text-2xl text-white">Verify your email</h1>
                <p class="mt-2 text-sm text-muted-links-2">
                    Verified email is required before enrollment and checkout actions.
                </p>

                <div class="mt-4 space-y-3">
                    <Button
                        class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]"
                        :disabled="loading || !hasSignedParams"
                        @click="verifySignedLink"
                    >
                        {{ loading ? 'Verifying...' : 'Verify from this link' }}
                    </Button>
                    <Button variant="outline" class="w-full border-white/20 text-white" @click="resend">
                        Resend verification email
                    </Button>
                    <RouterLink v-if="!isAuthenticated" to="/auth/login" class="block text-sm text-muted-links underline">
                        Return to login
                    </RouterLink>
                    <RouterLink v-if="isAuthenticated" to="/app/account" class="block text-sm text-muted-links underline">
                        Open account settings
                    </RouterLink>
                    <p v-if="isVerified" class="text-sm text-[#c1fbd4]">Your account is verified.</p>
                    <p v-if="status" class="text-sm text-muted-links-2">{{ status }}</p>
                </div>
            </section>
        </div>
    </ShellLayout>
</template>
