import { computed, ref } from 'vue';
import { useSkillbroApi } from '@/composables/useSkillbroApi';
import type { SkillbroUser } from '@/types/skillbro-api';

const api = useSkillbroApi();
const currentUser = ref<SkillbroUser | null>(null);
const isVerified = ref(false);
const loadingUser = ref(false);
let bootstrapPromise: Promise<void> | null = null;

const isAuthenticated = computed(() => Boolean(api.token.value));

async function bootstrapSession(): Promise<void> {
    if (!api.token.value) {
        currentUser.value = null;
        isVerified.value = false;

        return;
    }

    if (bootstrapPromise) {
        await bootstrapPromise;

        return;
    }

    bootstrapPromise = (async () => {
        loadingUser.value = true;

        try {
            const response = await api.getMe();
            currentUser.value = response.user;
            isVerified.value = response.verified;
        } catch {
            api.setToken(null);
            currentUser.value = null;
            isVerified.value = false;
        } finally {
            loadingUser.value = false;
        }
    })();

    try {
        await bootstrapPromise;
    } finally {
        bootstrapPromise = null;
    }
}

function clearSession(): void {
    api.setToken(null);
    currentUser.value = null;
    isVerified.value = false;
}

export function useAuthSession() {
    return {
        api,
        currentUser,
        isAuthenticated,
        isVerified,
        loadingUser,
        bootstrapSession,
        clearSession,
    };
}
