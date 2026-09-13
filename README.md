# SkillOS

A personal learning OS: authored courses → lessons (video + rewritten text + practices) → daily plan → learn / practice / AI feedback → spaced review. Persian UI, RTL, Jalali dates. Built for one person plus a couple of friends.

Product docs live in `docs/` — `PRD.md`, `DESIGN.md`, `DECISIONS.md` (algorithms and numbers), `STORIES.md` (build tracker).

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan content:import sample-course     # demo course
npm install --registry=https://registry.npmjs.org/ && npm run build
php artisan serve
```

Set `GEMINI_API_KEY` in `.env` (Google AI Studio, free tier). Gemini is used for one thing only: grading open practice answers. Without a key, MCQ practices still work and open answers show a retry message.

Tests: `php artisan test --compact`.

## Authoring a course

Content is files, not a UI:

```
content/<course-slug>/
  course.json                          title, description, outcome_statement, source_note, lessons[]
  lessons/<lesson-slug>.md             rewritten lesson text (Markdown; Persian, English terms kept)
  lessons/<lesson-slug>.practices.json practices[] with key, form, prompt, hints[2], expected_outcome, rubric, difficulty
```

See `content/sample-course/` for the shape and `docs/DESIGN.md` §4 for field rules. Then:

```sh
php artisan content:import <course-slug>          # upsert by slug/key — learner progress survives edits
php artisan content:import <course-slug> --prune  # also delete lessons/practices removed from the files
```

## Deploying to shared hosting

No Node, no queue worker, no cron needed. `public/build` is committed, so the server never runs `npm`.

1. Upload the repo (or `git pull`) and point the web root at `public/`.
2. `composer install --no-dev --optimize-autoloader`
3. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `APP_KEY` (`php artisan key:generate`), `GEMINI_API_KEY`, keep `DB_CONNECTION=sqlite`.
4. `touch database/database.sqlite` and make `database/` and `storage/` writable by PHP.
5. `php artisan migrate --force`, then `php artisan content:import <slug>` for each course.
6. `php artisan optimize` (config/route/view caches). Re-run after every deploy.

Videos: a lesson's `url` may be an external link (YouTube/Aparat are embedded) or a file you host anywhere — the app only stores the URL.
