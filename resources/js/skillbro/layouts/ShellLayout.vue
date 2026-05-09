<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { Button } from '@/components/ui/button';
import { useAuthSession } from '@/skillbro/composables/useAuthSession';

const { currentUser, isAuthenticated, api, clearSession } = useAuthSession();
const route = useRoute();
const mobileMenuOpen = ref(false);

type NavItem = {
    label: string;
    to: string;
};

type NavGroup = {
    label: string;
    items: NavItem[];
};

const publicPrimaryItems: NavItem[] = [
    { label: 'Home', to: '/' },
    { label: 'Catalog', to: '/courses' },
    { label: 'Pricing', to: '/pricing' },
];

const publicCompanyItems: NavItem[] = [
    { label: 'About', to: '/about' },
    { label: 'FAQ', to: '/faq' },
    { label: 'Contact', to: '/contact' },
];

const navGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [
        { label: 'Company', items: publicCompanyItems },
    ];

    if (!isAuthenticated.value) {
        return groups;
    }

    const workspaceItems: NavItem[] = [
        { label: 'Payments', to: '/app/payments' },
        { label: 'Notifications', to: '/app/notifications' },
        { label: 'Account', to: '/app/account' },
    ];

    if (currentUser.value?.role === 'student') {
        workspaceItems.unshift({ label: 'My Learning', to: '/app/learn' });
    }

    groups.push({ label: 'Workspace', items: workspaceItems });

    if (currentUser.value?.role === 'instructor') {
        groups.push({
            label: 'Instructor',
            items: [
                { label: 'Studio', to: '/app/instructor/studio' },
                { label: 'Curriculum', to: '/app/instructor/curriculum' },
                { label: 'Reviews', to: '/app/instructor/reviews' },
                { label: 'Revenue', to: '/app/instructor/revenue' },
            ],
        });
    }

    if (currentUser.value?.role === 'admin') {
        groups.push({
            label: 'Admin',
            items: [
                { label: 'Overview', to: '/app/admin/overview' },
                { label: 'Users', to: '/app/admin/users' },
                { label: 'Courses', to: '/app/admin/courses' },
                { label: 'Payments', to: '/app/admin/payments' },
                { label: 'Stats', to: '/app/admin/stats' },
            ],
        });
    }

    return groups;
});

const mobileSections = computed<NavGroup[]>(() => {
    return [
        { label: 'Browse', items: publicPrimaryItems },
        ...navGroups.value,
    ];
});

async function logout(): Promise<void> {
    try {
        await api.logout();
    } finally {
        clearSession();
        window.location.href = '/';
    }
}

function toggleMobileMenu(): void {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}

watch(() => route.fullPath, () => {
    mobileMenuOpen.value = false;
});
</script>

<template>
    <div class="sb-cinematic min-h-screen text-white">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-black/70 backdrop-blur">
            <div class="mx-auto flex w-full max-w-[1500px] items-center justify-between px-6 py-4">
                <RouterLink to="/" class="font-display text-2xl text-white">
                    SkillBro
                </RouterLink>

                <nav class="hidden items-center gap-2 md:flex">
                    <RouterLink
                        v-for="item in publicPrimaryItems"
                        :key="item.to"
                        :to="item.to"
                        class="rounded-full px-4 py-2 text-sm text-muted-links transition hover:bg-white/10 hover:text-white"
                    >
                        {{ item.label }}
                    </RouterLink>

                    <details
                        v-for="group in navGroups"
                        :key="group.label"
                        class="relative"
                    >
                        <summary
                            class="list-none cursor-pointer rounded-full px-4 py-2 text-sm text-muted-links transition hover:bg-white/10 hover:text-white [&::-webkit-details-marker]:hidden"
                        >
                            {{ group.label }}
                        </summary>
                        <div class="absolute left-0 top-full mt-2 min-w-[180px] rounded-xl border border-white/15 bg-black/95 p-2 shadow-lg">
                            <RouterLink
                                v-for="item in group.items"
                                :key="`${group.label}-${item.to}`"
                                :to="item.to"
                                class="block rounded-lg px-3 py-2 text-sm text-muted-links transition hover:bg-white/10 hover:text-white"
                            >
                                {{ item.label }}
                            </RouterLink>
                        </div>
                    </details>
                </nav>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        class="border-white/30 text-white md:hidden"
                        @click="toggleMobileMenu"
                    >
                        {{ mobileMenuOpen ? 'Close' : 'Menu' }}
                    </Button>

                    <RouterLink v-if="!isAuthenticated" to="/auth/login">
                        <Button variant="outline" class="border-white/70 text-white">
                            Log in
                        </Button>
                    </RouterLink>
                    <RouterLink v-if="!isAuthenticated" to="/auth/register">
                        <Button class="bg-[#c1fbd4] text-black hover:bg-[#97efb8]">
                            Start selling
                        </Button>
                    </RouterLink>

                    <RouterLink v-if="isAuthenticated" to="/app/account">
                        <Button variant="outline" class="border-white/30 text-white">
                            {{ currentUser?.name ?? 'Account' }}
                        </Button>
                    </RouterLink>
                    <Button
                        v-if="isAuthenticated"
                        variant="outline"
                        class="border-white/30 text-white"
                        @click="logout"
                    >
                        Logout
                    </Button>
                </div>
            </div>

            <div v-if="mobileMenuOpen" class="border-t border-white/10 px-6 pb-4 md:hidden">
                <div class="mt-3 space-y-4">
                    <section v-for="section in mobileSections" :key="`mobile-section-${section.label}`">
                        <p class="px-1 text-xs uppercase tracking-[0.72px] text-muted-links-2">
                            {{ section.label }}
                        </p>
                        <nav class="mt-2 flex flex-col gap-2">
                            <RouterLink
                                v-for="item in section.items"
                                :key="`mobile-${section.label}-${item.to}`"
                                :to="item.to"
                                class="rounded-full px-4 py-2 text-sm text-muted-links transition hover:bg-white/10 hover:text-white"
                            >
                                {{ item.label }}
                            </RouterLink>
                        </nav>
                    </section>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1500px] px-6 py-8">
            <slot />
        </main>

        <footer class="mt-16 border-t border-white/10">
            <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-4 px-6 py-8 text-sm text-muted-links md:flex-row md:items-center md:justify-between">
                <p>© {{ new Date().getFullYear() }} SkillBro. Build, sell, and scale course commerce.</p>
                <div class="flex flex-wrap gap-3">
                    <RouterLink to="/pricing" class="underline">Pricing</RouterLink>
                    <RouterLink to="/about" class="underline">About</RouterLink>
                    <RouterLink to="/faq" class="underline">FAQ</RouterLink>
                    <RouterLink to="/contact" class="underline">Contact</RouterLink>
                </div>
            </div>
        </footer>
    </div>
</template>
