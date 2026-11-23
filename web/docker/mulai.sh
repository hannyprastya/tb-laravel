#!/bin/sh
# docker/mulai.sh: ENTRYPOINT image, dijalankan setiap container start
# (app, worker, scheduler). Urutan: APP_KEY -> folder storage -> migrasi
# (bila diminta) -> bucket Floci (bila diminta) -> cache konfigurasi ->
# perintah container (bawaan: Apache).
set -e
cd /var/www/html

# 1. APP_KEY wajib dari env (Render: Environment; k8s: Secret). Khusus lab
#    (KUNCI_LAB=1 di compose) kunci dibuat sekali dan disimpan di volume,
#    dipakai bersama app/worker/scheduler; tidak pernah masuk repo.
if [ -z "${APP_KEY:-}" ]; then
    if [ "${KUNCI_LAB:-0}" = "1" ]; then
        f=storage/kunci/app_key
        mkdir -p storage/kunci
        if [ ! -s "$f" ]; then
            (umask 077; php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$f")
        fi
        APP_KEY=$(cat "$f")
        export APP_KEY
        echo "mulai: APP_KEY kosong, memakai kunci lab $f (khusus lab)" >&2
    else
        echo "mulai: APP_KEY belum diisi. Buat dengan:" >&2
        echo "  docker run --rm --entrypoint php IMAGE artisan key:generate --show" >&2
        exit 1
    fi
fi

# 2. folder kerja Laravel (di Kubernetes storage/ bisa berupa emptyDir kosong)
mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache

# 3. migrasi database (app saja; worker/scheduler tidak). Database belum
#    siap -> dicoba 5 kali; tetap gagal -> aplikasi tetap dinyalakan supaya
#    /healthz hidup dan halaman menampilkan pesan ramah 503 (P15).
if [ "${MIGRASI_SAAT_START:-0}" = "1" ]; then
    n=1
    until php artisan migrate --force --no-interaction; do
        if [ "$n" -ge 5 ]; then
            echo "mulai: migrasi gagal $n kali, aplikasi tetap dinyalakan" >&2
            break
        fi
        n=$((n + 1))
        sleep 3
    done
fi

# 4. lab: bucket (dan antrean SQS) di Floci
if [ "${SIAPKAN_FLOCI:-0}" = "1" ]; then
    php artisan lab:siapkan || echo "mulai: Floci belum siap" >&2
fi

# 5. cache konfigurasi, rute, view, event: membaca env SEKARANG, sekali
php artisan config:cache -q
php artisan route:cache -q
php artisan view:cache -q
php artisan event:cache -q

# 6. mode satu container (Render Free): Apache + worker + scheduler
if [ "${JALANKAN_WORKER:-0}" = "1" ] && [ "${1:-}" = "apache2-foreground" ]; then
    exec supervisord -c /etc/supervisor/tb.conf
fi

exec "$@"
