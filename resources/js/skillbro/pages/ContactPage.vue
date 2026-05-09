<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ShellLayout from '@/skillbro/layouts/ShellLayout.vue';

const form = reactive({
    name: '',
    email: '',
    message: '',
});
const submitted = ref(false);

function submit(): void {
    submitted.value = true;
}
</script>

<template>
    <ShellLayout>
        <section class="mb-10">
            <p class="text-xs uppercase tracking-[0.72px] text-muted-links">Contact</p>
            <h1 class="mt-3 sb-display-section">Talk to the SkillBro team</h1>
            <p class="mt-4 max-w-3xl text-muted-links-2">
                Need migration help, enterprise pricing, or launch strategy for your course business? We can help.
            </p>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <article class="rounded-xl border border-white/10 bg-black/50 p-6">
                <h2 class="text-xl text-white">Send a message</h2>
                <div class="mt-4 space-y-3">
                    <Input v-model="form.name" placeholder="Your name" class="bg-black/30 text-white" />
                    <Input v-model="form.email" type="email" placeholder="you@company.com" class="bg-black/30 text-white" />
                    <textarea
                        v-model="form.message"
                        class="min-h-32 w-full rounded-md border border-white/20 bg-black/30 px-3 py-2 text-sm text-white"
                        placeholder="What do you want to build on SkillBro?"
                    />
                    <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]" @click="submit">Send</Button>
                    <p v-if="submitted" class="text-sm text-muted-links-2">Thanks. Your message has been recorded for follow-up.</p>
                </div>
            </article>

            <article class="rounded-xl bg-[#fbfbf5] p-6 text-black">
                <h2 class="text-xl">Company</h2>
                <p class="mt-3 text-sm text-[#52525b]">SkillBro Inc.</p>
                <p class="text-sm text-[#52525b]">Cairo · Remote-first</p>
                <p class="mt-4 text-sm text-[#27272a]">support@skillbro.dev</p>
                <p class="text-sm text-[#27272a]">+20 100 000 0000</p>
            </article>
        </section>
    </ShellLayout>
</template>
