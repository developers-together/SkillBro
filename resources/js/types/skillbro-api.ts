export type ApiError = {
    status: number;
    message: string;
    details?: Record<string, string[]>;
};

export type PaginatedResponse<T> = {
    data: T[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

export type SkillbroCategory = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    children?: SkillbroCategory[];
};

export type SkillbroUser = {
    id: number;
    name: string;
    email: string;
    role: 'student' | 'instructor' | 'admin';
    avatar: string | null;
    bio: string | null;
    email_verified_at: string | null;
    created_at: string;
    is_banned?: boolean;
};

export type SkillbroLecture = {
    id: number;
    title: string;
    type: 'video' | 'text' | 'quiz';
    is_preview: boolean;
    position: number;
    video_duration: number | null;
    content?: string | null;
    video_path?: string | null;
};

export type SkillbroSection = {
    id: number;
    title: string;
    position: number;
    lectures?: SkillbroLecture[];
};

export type SkillbroCourse = {
    id: number;
    title: string;
    slug: string;
    description: string;
    thumbnail: string | null;
    price: string;
    status: 'draft' | 'pending' | 'published' | 'archived';
    level: 'beginner' | 'intermediate' | 'advanced';
    language: string;
    requirements?: string[] | null;
    what_you_learn?: string[] | null;
    sections_count?: number;
    lectures_count?: number;
    instructor?: SkillbroUser;
    category?: SkillbroCategory | null;
    tags?: string[];
    sections?: SkillbroSection[];
    created_at: string;
    updated_at?: string;
};

export type SkillbroEnrollment = {
    id: number;
    course: SkillbroCourse;
    enrolled_at: string;
    completed_at: string | null;
    is_completed: boolean;
    progress?: Array<{
        lecture_id: number;
        completed_at: string | null;
    }>;
};
