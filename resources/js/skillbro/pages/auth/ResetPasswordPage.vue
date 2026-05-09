<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const api = useSkillbroApi();
const route = useRoute();
const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const status = ref('');

async function submit(): Promise<void> {
    status.value = '';

    try {
        await api.resetPassword(form);
        status.value = 'Password reset succeeded.';
    } catch (caught) {
        status.value = caught && typeof caught === 'object' && 'message' in caught
            ? String((caught as { message: string }).message)
            : 'Reset failed.';
    }
}

onMounted(() => {
    form.token = String(route.params.token ?? '');
    form.email = String(route.query.email ?? '');
});
</script>

<template>
    <ShellLayout>
        <section class="mx-auto max-w-md rounded-xl border border-white/10 bg-black/50 p-6">
            <h1 class="text-2xl text-white">Reset password</h1>
            <div class="mt-4 space-y-3">
                <Input v-model="form.token" placeholder="Reset token" class="bg-black/30 text-white" />
                <Input v-model="form.email" type="email" placeholder="email@example.com" class="bg-black/30 text-white" />
                <Input v-model="form.password" type="password" placeholder="New password" class="bg-black/30 text-white" />
                <Input v-model="form.password_confirmation" type="password" placeholder="Confirm password" class="bg-black/30 text-white" />
                <Button class="w-full bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="submit">
                    Update password
                </Button>
                <p v-if="status" class="text-sm text-muted-links-2">{{ status }}</p>
            </div>
        </section>
    </ShellLayout>
</template>
