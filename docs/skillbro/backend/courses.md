# Course Domain

## Core entities

- `courses`
- `sections`
- `lectures`
- `categories`
- `tags`

## Course lifecycle

- `draft` -> `pending` -> `published` -> `archived`

## Main endpoints

- Course CRUD + submit/publish/archive
- Thumbnail upload
- Section CRUD + reorder
- Lecture CRUD + reorder + video upload

## Authorization model

- Instructor manages own course graph.
- Admin can publish and review all.
- Public can browse published courses.

## Search/filter

Course listing supports filtered scope (search/category/level/free/price ceiling).
