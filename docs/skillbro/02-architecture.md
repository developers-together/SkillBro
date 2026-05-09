# Architecture

## Backend layers

- Routes: `routes/api.php`
- Controllers: `app/Http/Controllers/Api/V1/**`
- Requests/validation: `app/Http/Requests/**`
- Models: `app/Models/**`
- Resources: `app/Http/Resources/**`
- Policies: `app/Policies/**`
- Middleware alias: `role` => `CheckRole`

## Frontend layers

- SPA entry: `resources/js/skillbro/main.ts`
- Main workspace: `resources/js/skillbro/SkillbroApp.vue`
- API client: `resources/js/composables/useSkillbroApi.ts`
- API types: `resources/js/types/skillbro-api.ts`
- Blade mount: `resources/views/skillbro.blade.php`

## Runtime contracts

- API responses always JSON on `/api/*` paths.
- Auth via `Authorization: Bearer <token>`.
- Route-level role restrictions for admin-only and instructor-sensitive paths.
