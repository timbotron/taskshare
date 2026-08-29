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
docker compose run --rm composer                       # one-time: install vendor/
docker compose up -d                                   # app on http://localhost:8080
```

The `db` container auto-imports `001-migration-start.sql` (the Initium `users` table) on
first boot. App tables (boards / lists / tasks / permissions) come in a follow-on migration
(CODE-78). Document root is `www/`; everything above it (`app/`, `vendor/`) is not web-exposed.

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
