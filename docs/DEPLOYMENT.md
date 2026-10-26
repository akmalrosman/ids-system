# Deploying IDS to a server

Two options. **A (Docker)** is the easiest: one command builds and starts the web server,
the FDR queue worker and PostgreSQL. **B** is a classic Ubuntu install if Docker isn't allowed.

Both work fully offline after installation: map tiles, fonts, icons and the 3D model are all served
by the app itself.

---

## A. Docker Compose (recommended)

Needs: Docker Engine + Compose plugin on the server.

```bash
git clone <your-repo-url> ids-system && cd ids-system
cp .env.production.example .env
```

`.env.production.example` already has the production values (`APP_ENV=production`, `APP_DEBUG=false`, …).
Fill in the lines marked **CHANGE ME**:

```dotenv
APP_URL=http://your-server-ip:8080
DB_PASSWORD=choose-a-strong-password
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=choose-another-strong-password
```

Generate the app key and start:

```bash
# Writes a random APP_KEY into .env (the container refuses to start without one).
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
docker compose build
docker compose up -d
docker compose exec app php artisan db:seed --force                     # roles, aircraft types, admin account
```

Open `http://your-server-ip:8080`.

| Task | Command |
| --- | --- |
| See logs | `docker compose logs -f app queue` |
| FDR conversion log | `docker compose exec app tail -f storage/logs/fdr-$(date +%F).log` |
| Update to new code | `git pull && docker compose up -d --build` (migrations run automatically) |
| Re-convert all FDR files | `docker compose exec app php artisan fdr:process --all` |
| Load demo flights too | `docker compose exec app php artisan db:seed --class=DemoSeeder --force` |
| Backup database | `docker compose exec db pg_dump -U ids ids > backup.sql` |

Data lives in Docker volumes: `ids-db` (PostgreSQL), `ids-files` (uploads + telemetry), `ids-logs`.

**Map tiles** are part of the image (`public/tiles`). If the server has no internet, download tiles
on your laptop (`npm run tiles:download …`) and commit or copy `public/tiles` before building.

---

## B. Ubuntu 24.04 without Docker

```bash
# PHP 8.4, extensions, nginx, PostgreSQL, supervisor
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.4-fpm php8.4-pgsql php8.4-intl php8.4-zip php8.4-mbstring php8.4-xml php8.4-curl php8.4-bcmath \
                    nginx postgresql supervisor unzip git
# Node.js 22 (needed at runtime for fdr-parser, and to build assets)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

Database:

```bash
sudo -u postgres psql -c "CREATE USER ids WITH PASSWORD 'strong-password';"
sudo -u postgres psql -c "CREATE DATABASE ids OWNER ids;"
```

App:

```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone <your-repo-url> ids-system && sudo chown -R $USER:www-data ids-system && cd ids-system
cp .env.production.example .env # fill in the CHANGE ME lines (APP_URL, DB_PASSWORD, ADMIN_*)
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --seed --force
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
```

Raise upload limits in `/etc/php/8.4/fpm/php.ini` (`upload_max_filesize = 2048M`, `post_max_size = 2100M`)
and `sudo systemctl restart php8.4-fpm`.

nginx: copy `docker/nginx.conf` to `/etc/nginx/sites-available/ids`, change
`root` to `/var/www/ids-system/public` and `fastcgi_pass` to `unix:/run/php/php8.4-fpm.sock`, then:

```bash
sudo ln -s /etc/nginx/sites-available/ids /etc/nginx/sites-enabled/ && sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

Queue worker (converts uploaded FDR files). Create `/etc/supervisor/conf.d/ids-queue.conf`:

```ini
[program:ids-queue]
command=php /var/www/ids-system/artisan queue:work --tries=1 --timeout=900 --sleep=2
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/ids-system/storage/logs/queue.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

Updating later: `bash scripts/deploy.sh`.

---

## Checklist when something doesn't work

| Symptom | Look at |
| --- | --- |
| FDR stays "Queued" | Queue worker isn't running (`docker compose ps` / `supervisorctl status`) |
| FDR "Failed" with `NODE_NOT_FOUND` | Node.js missing or set `NODE_BINARY` to its full path |
| Upload error "failed to upload" | PHP `upload_max_filesize` / `post_max_size`, nginx `client_max_body_size` |
| Map is plain dark blue | `public/tiles/manifest.json` missing: run the tile download |
| Aircraft flies sideways | Settings → Aircraft Types → *Heading offset* (try 90 / -90 / 180) |
| 500 error | `storage/logs/laravel.log` |
| `.env` change has no effect | Settings are cached: `php artisan optimize` (Docker: `docker compose restart app queue`) |
| "Class ... ServiceProvider not found" | A dev-only package was cached; run `php artisan package:discover` (the Docker image does this itself) |
