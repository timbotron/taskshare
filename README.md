# TaskShare

Dead-simple shared task lists. This is the full rewrite of the original TaskShare
(the legacy Fat-Free Framework + Bootstrap app is gone). Live: http://taskshare.org

## Stack

- **Backend:** PHP on the [initium-php-core](https://github.com/timbotron/initium-php-core)
  package (consumed via Composer; TaskShare is an Initium skeleton-style app) — FastRoute
  routing, League Plates templates, Medoo (PDO) data layer, Valitron validation, turnkey
  auth (signup / password reset / Mailgun) + an admin area. MySQL.
- **Frontend:** [Mithril.js](https://mithril.js.org/) — no build step, single vendored file.
- **CSS:** Tailwind via the **standalone CLI** (CSS-only, JIT-purged). No JS bundler.

## Dev setup (Docker)

```bash
cp config/_env.php.template config/_env.php            # DB_SERVER=db, DB_NAME/USER/PASS=taskshare
docker compose run --rm composer                       # one-time: install vendor/
docker compose up -d                                   # app on http://localhost:8080
```

On first boot the `db` container auto-imports core's migrations from
`vendor/timbotron/initium-php-core/migrations` (`users`, `login_attempts`, `settings` + `is_admin`)
then the app migration `db/100-migration-taskshare.sql` (boards / lists / tasks /
board_permissions + a `theme` column on `users`), in filename order. To load demo data:

```bash
docker compose exec -T db mysql -utaskshare -ptaskshare taskshare < seed.sql   # demo@taskshare.test / password
```

Migrations only run on a fresh volume — `docker compose down -v` to re-init from scratch.
Document root is `public/`; everything above it (`src/`, `config/`, `templates/`, `routes/`,
`vendor/`) is not web-exposed.

## CSS build (Tailwind standalone CLI)

No npm. Grab the standalone binary once (it is gitignored):

```bash
curl -fsSL -o bin/tailwindcss \
  https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-linux-x64
chmod +x bin/tailwindcss
```

Build the shipped stylesheet (`public/css/app.css`, committed):

```bash
./bin/tailwindcss -i tailwind/input.css -o public/css/app.css --minify   # one-off
./bin/tailwindcss -i tailwind/input.css -o public/css/app.css --watch    # dev
```

`tailwind.config.js` holds the token set (dark theme via a `data-theme` attribute) and the
content globs that drive purge. Component classes (`.btn`, `.list-card`, …) live in
`tailwind/input.css` via `@apply`.

## License

Copyright 2026 Tim Habersack. Released under an MIT license. http://opensource.org/licenses/MIT
