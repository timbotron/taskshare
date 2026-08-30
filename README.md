# TaskShare

Dead-simple shared task lists. This is the full rewrite of the original TaskShare
(the legacy Fat-Free Framework + Bootstrap app is gone). Live: http://taskshare.org

## Stack

- **Backend:** PHP on [Initium PHP](https://github.com/timbotron/initium-php) — FastRoute
  routing, League Plates templates, Medoo (PDO) data layer, Valitron validation, turnkey
  auth (signup / password reset / Mailgun). MySQL.
- **Frontend:** [Mithril.js](https://mithril.js.org/) — no build step, single vendored file.
- **CSS:** Tailwind via the **standalone CLI** (CSS-only, JIT-purged). No JS bundler.

## Dev setup (Docker)

```bash
cp app/config/_env.php.template app/config/_env.php   # DB_SERVER=db, DB_NAME/USER/PASS=taskshare
docker compose up -d                                   # installs vendor/ on first run; app on http://localhost:8080
```

`up` runs a one-shot `composer` service that installs `vendor/` when it's missing (the `php`
service waits for it), so a fresh clone needs no separate install step. To reinstall deps, delete
`vendor/` and `up` again (or run `docker compose run --rm composer sh -c 'composer install'`).

On first boot the `db` container auto-imports the migrations in order:
`001-migration-start.sql` (the Initium `users` table) then `002-migration-taskshare.sql`
(boards / lists / tasks / board_permissions + a `theme` column on `users`). To load demo data:

```bash
docker compose exec -T db mysql -utaskshare -ptaskshare taskshare < seed.sql   # demo@taskshare.test / password
```

Migrations only run on a fresh volume — `docker compose down -v` to re-init from scratch.
Document root is `www/`; everything above it (`app/`, `vendor/`) is not web-exposed.

## CSS build (Tailwind standalone CLI)

No npm. Grab the standalone binary once (it is gitignored):

```bash
curl -fsSL -o bin/tailwindcss \
  https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-linux-x64
chmod +x bin/tailwindcss
```

Build the shipped stylesheet (`www/css/app.css`, committed):

```bash
./bin/tailwindcss -i tailwind/input.css -o www/css/app.css --minify   # one-off
./bin/tailwindcss -i tailwind/input.css -o www/css/app.css --watch    # dev
```

`tailwind.config.js` holds the token set (dark theme via a `data-theme` attribute) and the
content globs that drive purge. Component classes (`.btn`, `.list-card`, …) live in
`tailwind/input.css` via `@apply`.

## License

Copyright 2026 Tim Habersack. Released under an MIT license. http://opensource.org/licenses/MIT
