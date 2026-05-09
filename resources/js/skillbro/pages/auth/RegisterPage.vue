<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const router = useRouter();
const { api, bootstrapSession } = useAuthSession();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    device_name: 'spa-web',
});

const error = ref('');

async function submit(): Promise<void> {
    error.value = '';

    try {
        await api.register(form);
        await bootstrapSession();
        await router.push('/app/account');
    } catch (caught) {
        error.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Registration failed.';
    }
}
</script>

<template>
    <ShellLayout>
        <div class="flex min-h-[calc(100vh-210px)] items-center justify-center">
            <section class="mx-auto w-full max-w-md rounded-xl border border-white/10 bg-black/50 p-6">
                <h1 class="text-3xl text-white">Create account</h1>
                <div class="mt-4 space-y-3">
                    <Input v-model="form.name" placeholder="Full name" class="bg-black/30 text-white" />
                    <Input v-model="form.email" type="email" placeholder="email@example.com" class="bg-black/30 text-white" />
                    <Input v-model="form.password" type="password" placeholder="Password" class="bg-black/30 text-white" />
                    <Input v-model="form.password_confirmation" type="password" placeholder="Confirm password" class="bg-black/30 text-white" />
                    <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="submit">
                        Create account
                    </Button>
                    <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
                </div>
            </section>
        </div>
    </ShellLayout>
</template>
