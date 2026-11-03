# Explore Heroes — Docker deployment

One `docker compose` stack runs the whole site:

| Service     | Image / build                         | Role |
|-------------|---------------------------------------|------|
| `db`        | `mysql:8.0`                           | Database (volume `db_data`) |
| `app`       | `Dockerfile` target `app` (PHP 8.2-FPM) | Laravel API and admin API |
| `scheduler` | same image as `app`                   | `php artisan schedule:work` |
| `frontend`  | `explore-heros-travel-website/Dockerfile` | Next.js (standalone) |
| `web`       | `Dockerfile` target `web` (nginx)     | The only public port |

`web` routes by path, so the API, media and pages all share the site domain:

```
/api/*  /admin/module/*  /sanctum/*  → Laravel (app:9000)
/module/*                            → Laravel /api/module/*
/uploads/*  /storage/*               → media volumes
everything else                      → Next.js (frontend:3000)
```

Laravel state lives in volumes: `backend_storage` (logs, sessions, **built
translations** in `storage/app/translations`) and `backend_uploads` (media).

## Layout

The compose file expects both repos side by side (override with `FRONTEND_PATH`):

```
explore-heroes/
├── explore-heroes-travel-website-backend/   ← this repo, compose in deploy/
└── explore-heros-travel-website/            ← Next.js repo
```

## First run (local)

```bash
cd explore-heroes-travel-website-backend/deploy
cp .env.example .env                  # ports, SITE_URL, DB passwords
cp backend.env.example backend.env    # Laravel: APP_KEY, mail …
# APP_KEY: reuse the live server's key, or generate one:
docker compose run --rm --no-deps --entrypoint "" app php artisan key:generate --show

docker compose up -d --build
docker compose ps                     # wait for web: healthy
open http://localhost:8080            # site;  /admin = Next.js admin
```

`RUN_MIGRATIONS=true` runs `php artisan migrate --force` each time `app`
starts (safe: only pending migrations run).

## Importing the current site's data

1. **Database.** Export it on the current server:
   `mysqldump --single-transaction -u root -p <db> | gzip > deploy/initdb/site.sql.gz`.
   Files in `deploy/initdb/` are imported **only on the first start**, while
   `db_data` is empty. To re-import, run `docker compose down -v` (this deletes
   the database volume) and start again.
2. **Media.** Either set `UPLOADS_PATH=/var/www/backend/public/uploads` in
   `.env` to mount the existing folder, or copy it into the volume:
   `docker compose cp /path/to/uploads/. app:/var/www/html/public/uploads/`.
3. Reuse the old `APP_KEY` in `backend.env`.
4. In Admin > Translations, click **Build All Languages** once. Built files
   now persist in the `backend_storage` volume.

## Production on the VPS (when you decide to switch)

Nothing here touches the current PM2/nginx setup until you choose to switch.

1. Install Docker, clone both repos (e.g. `/opt/explore-heroes/…`).
2. `.env`: `SITE_URL=https://exploreheroes.com`, `HTTP_PORT=80`,
   `NEXT_IMAGE_UNOPTIMIZED=false`, strong DB passwords.
   `backend.env`: real `APP_KEY`, `APP_HTTPS=true`, SMTP credentials (ST-01).
3. Stop the host nginx/PM2 (they hold port 80), then `docker compose up -d --build`.
4. TLS (SE-02): follow `docker/nginx/ssl.conf.example`, then add to the `web` service:
   ```yaml
   ports: ["80:80", "443:443"]
   volumes:
     - ./nginx-ssl.conf:/etc/nginx/conf.d/ssl.conf:ro
     - ./certbot/conf:/etc/letsencrypt:ro
   ```
   Renew monthly with the same `certbot/certbot renew --webroot …` command
   and `docker compose exec web nginx -s reload`.
5. Verify email: `docker compose exec -u www-data app php artisan mail:test you@example.com`.

## Testing

`deploy/smoke-test.mjs` checks the bug-sheet fixes against any running site
(read-only GET requests; Node 18+):

```bash
node deploy/smoke-test.mjs                         # http://localhost:8080
node deploy/smoke-test.mjs https://exploreheroes.com
```

Each check prints PASS / FAIL / SKIP with its sheet ID; SKIP means the data
it needs isn't there yet (e.g. no redirect rules). Exit code 1 on any FAIL.

## Updating

```bash
git -C ../ pull && git -C ../../explore-heros-travel-website pull
docker compose up -d --build        # rebuilds changed images, migrates
```

`NEXT_PUBLIC_*` values are compiled into the frontend, so change `SITE_URL`
→ rebuild `frontend` (`docker compose build frontend`).

## Useful commands

Run artisan as `www-data` (`-u www-data`) so log and cache files stay
writable by PHP-FPM.

```bash
docker compose logs -f app web frontend
docker compose exec -u www-data app php artisan tinker
docker compose exec -u www-data app php artisan destinations:clean-content          # dry run
docker compose exec -u www-data app php artisan destinations:clean-content --apply
docker compose exec db mysqldump -uroot -p"$DB_ROOT_PASSWORD" explore_heroes > backup.sql
```
