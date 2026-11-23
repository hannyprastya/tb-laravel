# syntax=docker/dockerfile:1
# web/Dockerfile: image starter Laravel (P7 multi-stage, P12 digest, P13
# hardening). Konteks build = folder web/:
#   docker build -t tb-web:dev web              # image produksi (tahap akhir)
#   docker build --target uji -t tb-web:uji web # image uji (test, Larastan)
# Base image dikunci digest (P12 B.4); tag hanya label untuk manusia.

# ---- tahap 1: dependensi PHP produksi (tanpa paket dev)
FROM composer:2.11.2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist \
      --no-scripts --no-autoloader --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction

# ---- tahap 1b: dependensi PHP + paket dev (PHPUnit, Larastan), untuk tahap uji
FROM composer:2.11.2@sha256:50ea4d33fdcde1adb0f045547295faa1548b33872e5f789dae5b02ce2fb5de93 AS vendor-dev
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist \
      --no-scripts --no-autoloader --no-progress
COPY . .
RUN composer dump-autoload --optimize --no-interaction

# ---- tahap 2: CSS/JS (Vite + Tailwind). Node hanya ada di tahap ini,
# tidak ikut ke image akhir; yang diambil hanya folder public/build.
FROM node:24-alpine@sha256:116b800c3754b440ee0aa3ed2f20f5bf3d99a554bfd9adeea09ea8017f6e85f5 AS aset
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# kelas Tailwind yang dipakai view paginasi bawaan Laravel
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---- tahap 3: dasar runtime, dipakai tahap uji dan tahap produksi
FROM php:8.4-apache@sha256:3550aedd1c7bdfe26283c7b1550ef76509e6c2b1284aef814e6f2e325b7c9688 AS dasar
# konfigurasi PHP produksi: error tidak tampil ke pengguna, dicatat ke log
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# pdo_pgsql (PostgreSQL), pcntl (worker berhenti dengan rapi), opcache;
# supervisor untuk mode satu container. libpq-dev hanya untuk kompilasi.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev supervisor \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql pcntl opcache \
 && apt-mark manual libpq5 \
 && apt-get purge -y --auto-remove libpq-dev \
 && rm -rf /var/lib/apt/lists/*
# Apache: docroot public/, port $PORT (bawaan 8080), tanpa nomor versi,
# jumlah worker prefork = $WORKERS (P11 B, P13 C.1; bawaan 4)
ENV PORT=8080 WORKERS=4
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-tb.ini"
COPY docker/supervisord.conf /etc/supervisor/tb.conf
COPY --chmod=755 docker/mulai.sh /usr/local/bin/mulai
RUN a2enmod headers \
 && echo 'Listen ${PORT}' > /etc/apache2/ports.conf \
 && printf '%s\n' 'ServerName localhost' 'ServerTokens Prod' 'ServerSignature Off' \
      'TraceEnable Off' \
      > /etc/apache2/conf-enabled/sembunyi-versi.conf \
 && printf '%s\n' 'KeepAlive Off' 'StartServers 1' 'MinSpareServers 1' \
      'MaxSpareServers ${WORKERS}' 'MaxRequestWorkers ${WORKERS}' \
      > /etc/apache2/conf-enabled/worker.conf \
 && chown root:root /var/www/html && chmod 755 /var/www/html
WORKDIR /var/www/html

# ---- tahap 4: uji (TIDAK masuk image produksi)
#   docker build --target uji -t tb-web:uji web
#   docker run --rm -e DATABASE_URL=... tb-web:uji               # php artisan test
#   docker run --rm tb-web:uji vendor/bin/phpstan analyse         # Larastan
FROM dasar AS uji
COPY --from=vendor-dev /app/vendor ./vendor
COPY . .
# .env kosong: nilai uji dari phpunit.xml dan env container
RUN touch .env \
 && mkdir -p storage/app/private storage/framework/cache/data \
      storage/framework/sessions storage/framework/testing \
      storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT []
CMD ["php", "artisan", "test"]

# ---- tahap 5: produksi (tahap terakhir = hasil docker build biasa)
FROM dasar AS produksi
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=aset /app/public/build ./public/build
# Kode milik root dan hanya-baca; yang boleh ditulis www-data hanya
# storage/ dan bootstrap/cache (P13 C.4 hardening).
RUN mkdir -p storage/app/private storage/framework/cache/data \
      storage/framework/sessions storage/framework/views storage/logs \
      storage/kunci bootstrap/cache \
 && php artisan package:discover -q \
 && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 8080
ENTRYPOINT ["mulai"]
CMD ["apache2-foreground"]
