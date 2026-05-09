<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';
import type { SkillbroUser } from '@/types/skillbro-api';

const api = useSkillbroApi();
const users = ref<SkillbroUser[]>([]);
const status = ref('');

const form = reactive({
    userId: '',
    role: 'student' as 'student' | 'instructor' | 'admin',
    ban: false,
});

function getErrorMessage(caught: unknown, fallback: string): string {
    return caught && typeof caught === 'object' && 'message' in caught
        ? String((caught as { message: string }).message)
        : fallback;
}

async function loadUsers(): Promise<void> {
    status.value = '';

    try {
        const response = await api.getAdminUsers();
        users.value = response.data;
    } catch (caught) {
        users.value = [];
        status.value = getErrorMessage(caught, 'Unable to load users.');
    }
}

async function changeRole(): Promise<void> {
    try {
        await api.updateUserRole(Number(form.userId), form.role);
        status.value = 'User role updated.';
        await loadUsers();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Role update failed.');
    }
}

async function setBan(): Promise<void> {
    try {
        await api.updateUserBan(Number(form.userId), form.ban);
        status.value = form.ban ? 'User banned.' : 'User unbanned.';
        await loadUsers();
    } catch (caught) {
        status.value = getErrorMessage(caught, 'Ban update failed.');
    }
}

onMounted(loadUsers);
</script>

<template>
    <ShellLayout>
        <section class="mb-6">
            <h1 class="sb-display-section">Admin users</h1>
            <p class="mt-2 text-muted-links-2">Manage roles and moderation flags across student, instructor, and admin accounts.</p>
        </section>

        <section class="grid gap-4 rounded-xl border border-white/10 bg-black/50 p-5 lg:grid-cols-3">
            <Input v-model="form.userId" placeholder="User ID" class="bg-black/30 text-white" />
            <select v-model="form.role" class="h-11 rounded-md border border-white/20 bg-black/30 px-3 text-sm text-white">
                <option value="student">student</option>
                <option value="instructor">instructor</option>
                <option value="admin">admin</option>
            </select>
            <div class="flex flex-wrap gap-2">
                <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="changeRole">Change role</Button>
                <label class="inline-flex items-center gap-2 rounded-full border border-white/20 px-3 text-sm text-white">
                    <input v-model="form.ban" type="checkbox" class="size-4">
                    Ban
                </label>
                <Button variant="outline" class="border-white/20 text-white" @click="setBan">Apply ban</Button>
            </div>
        </section>

        <section class="mt-6 rounded-xl border border-white/10 bg-black/50 p-5">
            <h2 class="text-xl text-white">Users</h2>
            <table class="mt-4 w-full text-left text-sm text-muted-links-2">
                <thead>
                    <tr class="border-b border-white/10 text-muted-links">
                        <th class="py-2">ID</th>
                        <th class="py-2">Name</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-b border-white/5">
                        <td class="py-2">{{ user.id }}</td>
                        <td class="py-2">{{ user.name }}</td>
                        <td class="py-2">{{ user.email }}</td>
                        <td class="py-2 uppercase">{{ user.role }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!users.length" class="mt-3 text-sm text-muted-links">No users found.</p>
        </section>

        <p v-if="status" class="mt-4 text-sm text-muted-links-2">{{ status }}</p>
    </ShellLayout>
</template>
