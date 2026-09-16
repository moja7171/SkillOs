# SkillOS

A personal learning OS: authored courses → lessons (video + rewritten text + practices) → daily plan → learn / practice / AI feedback → spaced review. Persian UI, RTL, Jalali dates. Built for one person plus a couple of friends.

Product docs live in `docs/` — `PRD.md`, `DESIGN.md`, `DECISIONS.md` (algorithms and numbers), `STORIES.md` (build tracker), and **`AUTHORING.md` — the manual for writing lessons; read it before touching `content/`**.

## Local setup

Needs a MySQL (or MariaDB) server — the quickest way, if you have Docker:

```sh
docker run -d --name skillos-mysql -p 3306:3306 \
  -e MYSQL_DATABASE=skillos -e MYSQL_USER=skillos -e MYSQL_PASSWORD=skillos -e MYSQL_ROOT_PASSWORD=root \
  mysql:8
```

Then:

```sh
composer install
cp .env.example .env && php artisan key:generate   # DB_* already points at the container above
php artisan migrate
php artisan content:import sample-course     # demo course
npm install --registry=https://registry.npmjs.org/ && npm run build
php artisan serve
```

No server handy, or don't want one? SQLite still works — `touch database/database.sqlite`, set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite` in `.env`, skip the `docker run` step, everything else is the same.

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

See `docs/AUTHORING.md` for the full workflow and style rules, `content/sample-course/` for the shape, and `tools/authoring/` for the helper scripts (`vtt2txt.py`, `srt2vtt.py`, `nbdump.py`, `validate.py`). Then:

```sh
php artisan content:import <course-slug>          # upsert by slug/key — learner progress survives edits
php artisan content:import <course-slug> --prune  # also delete lessons/practices removed from the files
```

## Deploying

Two paths, depending on whether you have git access to the host's cPanel account. Both end up running the same artisan commands; the git path just automates the trigger.

### Git-based deploy (cPanel Git Version Control) — use this when you have it

One-time setup on your machine:

```sh
git worktree add ~/skillos-deploy-worktree -b deploy   # from a commit that already has this README section
cd ~/skillos-deploy-worktree
composer install --no-dev --optimize-autoloader --no-interaction
git add -Af vendor && git commit -m "Deploy branch: initial vendor/"
# then add .cpanel.yml and public/deploy.php (see below) and commit those too
git push -u origin deploy
```

- **`.cpanel.yml`** (deploy branch only — not on `main`) tells cPanel's Git Version Control what to do after every pull:
  ```yaml
  deployment:
    tasks:
      - export DEPLOYPATH=/home/USER/skillos.your-domain.tld/
      - /bin/cp -R * $DEPLOYPATH
      - curl -kfsS --connect-to skillos.your-domain.tld:443:127.0.0.1:443 https://skillos.your-domain.tld/deploy.php
  ```
  No `|| true` here on purpose — a real failure (wrong loopback port, TLS/SNI mismatch, …) should show up as a failed task in cPanel's own deployment log instead of being silently swallowed. `-f` makes curl return non-zero on an HTTP error status so that failure is actually visible.
- **`public/deploy.php`** (deploy branch only) is the file that curl hits: it bootstraps Laravel directly (no routing/CSRF involved) and runs `migrate --force`, `storage:link`, `content:import --prune` for every course under `content/`, then `config:cache`/`route:cache`/`view:cache`. Guarded so it only runs for the internal `127.0.0.1` curl above, or with `?token=` matching `DEPLOY_TOKEN` in `.env` (a manual fallback if the internal trigger ever needs checking by hand). Every invocation — rejected or not — also appends its full output to `storage/logs/deploy-hook.log`, independent of whether the triggering curl itself succeeded; read it with `/_ops/deploy-log?token=T` to confirm a deploy's migrate/import step actually ran, without needing SSH.

On the cPanel side (once): **Git Version Control** → clone `https://github.com/…/SkillOs.git`, branch `deploy`, repository path `/home/USER/skillos.your-domain.tld` (same as `DEPLOYPATH` above). Create the subdomain first with **Document Root** = `.../skillos.your-domain.tld/public` (standard Laravel-on-shared-hosting layout — the app itself lives one level above the document root). Create `skillos.your-domain.tld/.env` by hand the first time (from `.env.production.example`; `database/`, `storage/` and `.env` are gitignored on every branch, so they're never touched by a pull).

Every later update:

```sh
tools/deploy-push.sh              # merges main into the deploy worktree, refreshes vendor/, pushes
```

Then click **"Update from Remote"** (or **"Deploy HEAD Commit"**) in cPanel's Git Version Control screen for this repo — that's the whole update path; `deploy.php` re-runs the migrate/import/cache steps automatically via `.cpanel.yml`. **Always check `/_ops/deploy-log?token=T` afterward** to confirm it actually ran (the hook failed silently at least once before the fix above) — if it's stale or missing, fall back to hitting `/_ops/migrate` and `/_ops/import` by hand.

### Manual zip upload (no git access to the host)

The host only needs PHP 8.3+ with `pdo_mysql` (or `pdo_sqlite` if you're using SQLite instead), `mbstring`, `openssl`, `fileinfo` and a file manager. No Node, no Composer, no cron, no queue worker on the host: the release zip is built on your machine and every deploy step runs over HTTP via `/_ops/…`.

1. **Build the bundle** (from a committed tree): `tools/build-release.sh` → `release/skillos-<date>-<sha>.zip` containing `skillos/` (the app with `vendor/`) and `public_html/` (the web root).
2. **Upload + extract** the zip in the host's file manager into your home directory, so you get `~/skillos/` next to `~/public_html/`. If the host gives you only `public_html`, extract there instead: `public_html/skillos/` is denied by its `.htaccess` and the front controller finds it.
3. **Create a MySQL database + user** in cPanel's "MySQL Databases" tool (SQLite needs no such step — skip if using it instead).
4. **Create `skillos/.env`** from `skillos/.env.production.example`: `APP_URL`, `APP_KEY` (run `php artisan key:generate --show` locally and paste), the `DB_*` values from the database you just created, `GEMINI_API_KEY`, a long random `OPS_TOKEN`, optionally `REGISTRATION_CODE`, and `MEDIA_BASE_URL` (see below). Make sure `skillos/storage` and `skillos/bootstrap/cache` are writable (usually already, PHP runs as your user; `skillos/database` only matters for SQLite).
5. In the browser, with `T` = your `OPS_TOKEN`:
   - `https://your-domain/_ops/status?token=T` — sanity check (PHP version, DB connection, writable dirs, courses found)
   - `https://your-domain/_ops/migrate?token=T` — runs migrations (also creates the SQLite file first, if that's what you're using)
   - `https://your-domain/_ops/import?token=T` — imports every course under `content/` except `sample-course` (a test fixture, never auto-shipped); `&slug=x` for one course (any slug, including `sample-course`), `&prune=1` to delete removed lessons
   - `https://your-domain/_ops/delete-course?token=T&slug=x` — permanently removes a course and everything under it (there's no other way to remove a course on a host with no shell access; `slug` is required, no bulk form)
   - `https://your-domain/_ops/optimize?token=T` — caches config/routes/views
   - `https://your-domain/_ops/deploy-log?token=T` — tail of `storage/logs/deploy-hook.log` (only relevant on the git-based path above; nothing writes it here)
6. Register the first account at `/register` (with the invite code if you set one).

Every later update: `tools/build-release.sh`, upload + extract over the previous release (the zip never contains `.env`, `database/` or `storage/`, so nothing is lost), then hit `/_ops/migrate`, `/_ops/import` and `/_ops/optimize` again; `/_ops/clear` drops the caches if something looks stale. Content-only updates can skip the zip: upload the changed `content/<course>/` files into `skillos/content/` and hit `/_ops/import`.

### Videos that stay on your own machine

Course media is never in the bundle. With `MEDIA_BASE_URL=http://localhost:8765` in the host's `.env`, lesson pages render video/subtitle/file URLs pointing at **the viewer's own machine**, and browsers treat `http://localhost` as a secure origin so an https site may load from it. Run this on the machine that has the `course/` folder:

```sh
python3 tools/media-server.py          # serves ./course at http://localhost:8765 with Range + CORS
```

Leave it running while you study; `--root`/`--port` change the folder/port, `--bind 0.0.0.0` exposes it to your LAN (then use the machine's LAN IP as `MEDIA_BASE_URL`). Leave `MEDIA_BASE_URL` empty when the media is uploaded to the host under `public_html/media/<course>/` instead.
