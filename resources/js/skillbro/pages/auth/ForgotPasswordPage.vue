<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const api = useSkillbroApi();
const email = ref('');
const status = ref('');

async function submit(): Promise<void> {
    status.value = '';

    try {
        await api.forgotPassword({ email: email.value });
        status.value = 'Reset request sent.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Request failed.';
    }
}
</script>

<template>
    <ShellLayout>
        <section class="mx-auto max-w-md rounded-xl border border-white/10 bg-black/50 p-6">
            <h1 class="text-2xl text-white">Forgot password</h1>
            <div class="mt-4 space-y-3">
                <Input v-model="email" type="email" placeholder="email@example.com" class="bg-black/30 text-white" />
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="submit">
                    Send reset link
                </Button>
                <p v-if="status" class="text-sm text-muted-links-2">{{ status }}</p>
            </div>
        </section>
    </ShellLayout>
</template>

