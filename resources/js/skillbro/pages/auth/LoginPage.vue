<script setup lang="ts">
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const router = useRouter();
const { api, bootstrapSession } = useAuthSession();

const form = reactive({
    email: '',
    password: '',
    device_name: 'spa-web',
});

const error = ref('');

async function submit(): Promise<void> {
    error.value = '';

    try {
        await api.login(form);
        await bootstrapSession();
        await router.push('/app/learn');
    } catch (caught) {
        error.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Login failed.';
    }
}
</script>

<template>
    <ShellLayout>
        <div class="flex min-h-[calc(100vh-210px)] items-center justify-center">
            <section class="mx-auto w-full max-w-md rounded-xl border border-white/10 bg-black/50 p-6">
                <h1 class="text-3xl text-white">Log in</h1>
                <div class="mt-4 space-y-3">
                    <Input v-model="form.email" type="email" placeholder="email@example.com" class="bg-black/30 text-white" />
                    <Input v-model="form.password" type="password" placeholder="Password" class="bg-black/30 text-white" />
                    <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="submit">
                        Continue
                    </Button>
                    <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
                    <p class="text-sm text-muted-links">
                        Need account?
                        <RouterLink to="/auth/register" class="text-white underline">Register</RouterLink>
                    </p>
                    <RouterLink to="/auth/forgot-password" class="text-sm text-muted-links underline">
                        Forgot password
                    </RouterLink>
                </div>
            </section>
        </div>
    </ShellLayout>
</template>
