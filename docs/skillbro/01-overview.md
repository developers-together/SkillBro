# SkillBro Overview

SkillBro = API-first learning platform.

- Backend: Laravel 13 REST API under `/api/v1`.
- Frontend: Vue 3 SPA workspace mounted at `/skillbro`.
- Auth: Sanctum bearer tokens.
- Access model: role enum (`student`, `instructor`, `admin`).

## Product Scope

- Course authoring and publishing workflow.
- Section/lecture content structure.
- Free and paid enrollment flows.
- Progress tracking and completion state.
- Reviews and instructor replies.
- Quiz authoring and attempts.
- Notifications.
- Admin governance and platform stats.

## Project mode

This project currently optimized for showcase / CV usage.

- Payment flow implemented as gateway-agnostic mock flow.
- No hard dependency on external payment SDK.
