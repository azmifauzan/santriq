# Deploy Produksi

Domain: `santriq.web.id` (+ wildcard `*.santriq.web.id`). Server: `43.129.52.206` (Ubuntu, akses lewat `DEPLOY_HOST`/`DEPLOY_USERNAME`/`DEPLOY_PASSWORD` di `.env` lokal).

## Arsitektur di server

Server ini melayani beberapa aplikasi sekaligus lewat satu reverse proxy bersama:

- **nginx** (`jonasal/nginx-certbot`, di `/home/ubuntu/nginx`) — satu-satunya container yang bind ke port 80/443. Vhost tiap aplikasi ada di `/home/ubuntu/nginxconf/user_conf.d/*.conf`, sertifikat di `/home/ubuntu/nginxconf/nginx_secrets` (persisten, di-mount ke `/etc/letsencrypt` di container).
- **postgres** (`postgres:18`, satu instance untuk semua aplikasi) — satu database per aplikasi (`santriq`), semua pakai user `postgres` (password sama, lihat `/home/docker/postgre/.env` di server).
- **redis** (`redis:8-alpine`, satu instance untuk semua aplikasi) — dipakai bersama, password sama untuk semua (lihat `/home/docker/redis/.env` di server).
- Aplikasi SantriQ sendiri hidup di `/home/ubuntu/santriq` (image `azmifauzan/santriq` dari Docker Hub, dibangun dari `Dockerfile` di root repo ini) dan terhubung ke tiga network Docker: `santriq` (dipakai bareng nginx), `postgres`, `redis` (masing-masing `external: true`, sudah ada di server).

Queue Telegram (`database` driver, sesuai keputusan arsitektur di `docs/RENCANA-IMPLEMENTASI.md`) dan Apache jalan dalam satu container lewat `supervisord` (lihat `docker/supervisord.conf`).

## Redeploy setelah ada perubahan kode

Jalankan dari mesin lokal (folder repo ini), dengan Docker sudah login ke Docker Hub sebagai `azmifauzan`:

```bash
# 1. Build image baru (multi-stage: Node build assets + PHP build vendor, lalu image php8.5-apache)
TAG=$(date +%Y%m%d)-1   # naikkan angka di belakang kalau build ulang di hari yang sama
docker build -t azmifauzan/santriq:$TAG -t azmifauzan/santriq:latest .

# 2. (opsional tapi disarankan) sanity check lokal sebelum push
docker run --rm azmifauzan/santriq:latest php artisan --version

# 3. Push ke Docker Hub
docker push azmifauzan/santriq:$TAG
docker push azmifauzan/santriq:latest
```

Lalu di server (ganti `$TAG` dengan tag yang baru dibuat):

```bash
ssh ubuntu@43.129.52.206
cd /home/ubuntu/santriq
sed -i "s/santriq:[^ ]*/santriq:$TAG/" docker-compose.yml   # atau edit manual "image:" di docker-compose.yml
sudo docker compose pull
sudo docker compose up -d
```

Kalau ada migrasi baru, jalankan setelah container baru up:

```bash
sudo docker exec santriq-app php artisan migrate --force
```

Kalau tidak ada perubahan skema, migrasi tidak perlu dijalankan ulang (aman dijalankan berkali-kali karena Laravel skip migrasi yang sudah tercatat).

Cek log kalau ada yang aneh:

```bash
sudo docker compose logs -f --tail=100
```

## Konfigurasi environment (`/home/ubuntu/santriq/.env`)

File ini **tidak ada di git** (secret produksi), dan dibuat sekali secara manual di server. Isinya mengikuti `.env.example` di repo ditambah:

- `DB_HOST=postgres`, `REDIS_HOST=redis` — nama service, resolve lewat Docker DNS karena container ini join network `postgres`/`redis`.
- `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=database` (queue tetap `database` sesuai keputusan arsitektur — **jangan diubah** ke `redis` tanpa mengubah dokumen arsitektur).
- `APP_TENANT_SUBDOMAIN_ACTIVE=true` — wildcard DNS `*.santriq.web.id` sudah aktif (lihat bagian TLS di bawah), jadi mode subdomain penuh dipakai, bukan fallback path.
- `TELEGRAM_BOT_TOKEN` / `TELEGRAM_SECRET_TOKEN` — token bot produksi. Kalau token bot diganti, jalankan ulang `setWebhook` (lihat bagian Telegram di bawah).
- Kalau perlu ubah env (ganti password, ganti mode subdomain, dll): edit `/home/ubuntu/santriq/.env` langsung di server lalu `sudo docker compose up -d` (recreate container agar env baru terbaca). Ganti `APP_TENANT_SUBDOMAIN_ACTIVE` juga butuh `npm run build` ulang di image (Wayfinder membakukan bentuk rute saat build) — jadi build ulang image, bukan cukup ubah `.env`.

## TLS / Sertifikat wildcard

Domain `santriq.web.id` di Cloudflare: root domain proxied (orange cloud), record wildcard `*.santriq.web.id` **DNS-only** (grey cloud, A record ke IP server) — sengaja dipisah karena sertifikat wildcard wajib pakai tantangan DNS-01, bukan HTTP-01.

Sertifikat wildcard diterbitkan sekali lewat plugin `certbot-dns-cloudflare` yang sudah include di image `jonasal/nginx-certbot`, memakai token API Cloudflare yang disimpan di `/home/ubuntu/nginxconf/nginx_secrets/cloudflare.ini` (persisten, ikut volume `nginx_secrets`). Auto-renewal certbot bawaan image ini akan memakai kredensial yang sama tanpa perlu diulang manual, selama file `cloudflare.ini` tidak dihapus.

Kalau sertifikat perlu diterbitkan ulang manual (mis. ganti akun Let's Encrypt, atau menambah domain):

```bash
sudo docker exec nginx certbot certonly \
  --dns-cloudflare --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
  --dns-cloudflare-propagation-seconds 30 \
  -d santriq.web.id -d "*.santriq.web.id" \
  --email mail2fauzan@gmail.com --agree-tos --non-interactive \
  --cert-name santriq.web.id
```

## Cache Cloudflare untuk aset statis

Domain di-proxy Cloudflare (orange cloud untuk root domain), jadi aset statis (`favicon.ico`, `favicon.svg`, gambar, dll di `public/`) bisa kena cache di edge Cloudflare terlepas dari isi terbaru di origin. Kalau mengganti file statis dan tidak langsung terlihat setelah deploy, cek dulu origin langsung (`sudo docker exec santriq-app curl -s -H "Host: santriq.web.id" http://localhost/<file>`) untuk pastikan originnya sudah benar sebelum curiga ke kode.

Purge cache lewat dashboard Cloudflare (santriq.web.id → Caching → Configuration → Purge Everything, atau purge by URL untuk file tertentu). `CLOUDFLARE_TOKEN` di `.env` sengaja dibatasi scope **Zone:DNS:Edit** saja (dipakai certbot DNS-01) — tidak punya izin cache purge lewat API. Kalau mau otomasi purge dari sini juga, token perlu ditambah scope **Zone:Cache Purge**.

## Vhost nginx

Config di `/home/ubuntu/nginxconf/user_conf.d/santriq.conf`, proxy ke container `santriq-app` lewat nama service `santriq` (network Docker). Perhatikan `proxy_buffer_size`/`proxy_buffers` sengaja dinaikkan (16k/8x16k) — default nginx terlalu kecil untuk header response Laravel (kena `upstream sent too big header` kalau default dipakai).

Setelah edit vhost:

```bash
sudo docker exec nginx nginx -t      # validasi dulu sebelum reload
sudo docker exec nginx nginx -s reload
```

`nginx -s reload` cukup untuk perubahan vhost/sertifikat (tidak mengganggu aplikasi lain). Hanya perlu `docker compose up -d` di `/home/ubuntu/nginx` (yang me-recreate container, restart singkat untuk **semua** aplikasi di belakang proxy ini) kalau menambah/mengubah **network** yang di-join nginx — ini sudah dilakukan sekali saat setup awal SantriQ, tidak perlu diulang untuk update kode SantriQ biasa.

## Telegram

Ganti webhook (perlu dijalankan ulang kalau `TELEGRAM_BOT_TOKEN` atau `TELEGRAM_SECRET_TOKEN` berubah):

```bash
curl -X POST "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook" \
  -d "url=https://santriq.web.id/telegram/webhook" \
  -d "secret_token=<TELEGRAM_SECRET_TOKEN>"
```

Cek status webhook:

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

## Rollback

Image lama tetap ada di Docker Hub per tag tanggal. Untuk rollback:

```bash
cd /home/ubuntu/santriq
sed -i "s/santriq:[^ ]*/santriq:<TAG_LAMA>/" docker-compose.yml
sudo docker compose up -d
```

Migrasi database **tidak otomatis di-rollback** — kalau versi baru menambah migrasi yang perlu dibatalkan, jalankan `php artisan migrate:rollback` manual sebelum downgrade image.

## Backup

Belum ada backup terjadwal untuk `santriq` (lihat catatan "Fase 7 — Rilis" di `docs/RENCANA-IMPLEMENTASI.md`). Server punya folder `/home/ubuntu/deploy-backups` yang dipakai aplikasi lain — pola serupa (dump `pg_dump` terjadwal via cron) bisa dipakai untuk `santriq` kalau/ketika fitur ini dikerjakan.
