#!/usr/bin/env bash
# tests/fungsional.sh (P10 D.2): uji fungsional starter Laravel terhadap
# compose yang sudah jalan (docker compose up -d --wait). Dipakai di CI
# (langkah Test) dan di codespace/laptop, dari akar repo:
#   bash tests/fungsional.sh              # semua, termasuk F-18/F-19 (db dimatikan)
#   TANPA_DB_MATI=1 bash tests/fungsional.sh   # tanpa mematikan db
# Uji modul kasus: bila ada web/tests/kasus.sh, berkas itu ikut dijalankan
# (boleh memakai fungsi cek, kode, token, dan variabel URL/KUKI di bawah).
# Keluar dengan kode 1 bila ada test case yang tidak sesuai harapan.
# Butuh: curl, jq, docker compose.
set -u
URL=${URL:-http://localhost:8080}
KUKI=$(mktemp)                  # cookie sesi (seperti browser)
KERJA=$(mktemp -d)              # berkas sementara untuk uji unggah
trap 'rm -rf "$KUKI" "$KERJA"' EXIT
gagal=0

cek() {   # cek ID harapan aktual keterangan
  local status=Lulus
  if [ "$3" != "$2" ]; then status=GAGAL; gagal=1; fi
  printf "%-5s %-6s harap %-4s dapat %-4s %s\n" "$1" "$status" "$2" "$3" "$4"
}
kode() { curl -s -o /dev/null -m 10 -w "%{http_code}" "$@"; }
# token CSRF dari formulir di halaman $1 (cookie sesi disimpan di $KUKI)
token() {
  curl -s -m 10 -b "$KUKI" -c "$KUKI" "$URL$1" \
    | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -n 1
}
# POST formulir dengan cookie sesi; keluaran: "KODE ALAMAT_TUJUAN"
kirim() {   # kirim PATH [opsi curl ...]
  local p=$1; shift
  curl -s -o /dev/null -m 15 -b "$KUKI" -c "$KUKI" \
    -w "%{http_code} %{redirect_url}" "$@" "$URL$p"
}

echo "Uji fungsional ke $URL ($(date +%T))"

# ---- kontrak dasar (P10 slide 23)
badan=$(curl -s -m 10 "$URL/health")
cek F-01 200 "$(kode "$URL/health")" "/health saat db normal: $badan"
cek F-02 200 "$(kode "$URL/healthz")" "/healthz: $(curl -s -m 10 "$URL/healthz")"
cek F-03 200 "$(kode "$URL/")" "halaman utama buku tamu"
kepala=$(curl -s -m 10 -D - -o /dev/null "$URL/")
server=$(grep -i '^server:' <<<"$kepala" | tr -d '\r' | cut -d' ' -f2-)
xpb=$(grep -ci '^x-powered-by:' <<<"$kepala")
case "$server" in */*) versi=ada ;; *) versi=tanpa ;; esac
cek F-04 "tanpa/0" "$versi/$xpb" "Server: $server (tanpa versi), tanpa X-Powered-By"

# ---- buku tamu lewat formulir (sesi + CSRF)
t=$(token /)
read -r k tujuan <<<"$(kirim /tamu --data-urlencode "_token=$t" \
  --data-urlencode "nama=Ani (uji)")"
cek F-05 302 "$k" "simpan nama -> $tujuan"
cek F-06 422 "$(kode -b "$KUKI" -H 'Accept: application/json' \
  -d "_token=$t&nama=" "$URL/tamu")" "nama kosong ditolak (validasi)"
cek F-07 419 "$(kode -d "nama=tanpa-token" "$URL/tamu")" \
  "POST tanpa token CSRF ditolak"
kepala=$(curl -s -m 10 -D - -o /dev/null "$URL/tamu")
cc=$(grep -i '^cache-control:' <<<"$kepala" | tr -d '\r' | cut -d' ' -f2-)
kuki=$(grep -ci '^set-cookie:' <<<"$kepala")
cek F-08 "0" "$kuki" "/tamu publik tanpa cookie, Cache-Control: $cc"

# ---- login dan registrasi (Breeze)
cek F-09 200 "$(kode "$URL/login")" "halaman login"
SUREL="uji$(date +%s)$RANDOM@contoh.test"
SANDI="Uji-$(date +%s)-$RANDOM"
t=$(token /register)
read -r k tujuan <<<"$(kirim /register --data-urlencode "_token=$t" \
  --data-urlencode "name=Pengguna Uji" --data-urlencode "email=$SUREL" \
  --data-urlencode "password=${SANDI}" \
  --data-urlencode "password_confirmation=${SANDI}")"
cek F-10 "302 /dashboard" "$k ${tujuan#"$URL"}" "daftar akun baru $SUREL"
cek F-10b 200 "$(kode -b "$KUKI" "$URL/dashboard")" "dasbor setelah daftar"
t=$(token /dashboard)
kirim /logout --data-urlencode "_token=$t" >/dev/null
cek F-11 302 "$(kode -b "$KUKI" "$URL/dashboard")" "dasbor setelah keluar -> login"
t=$(token /login)
read -r k tujuan <<<"$(kirim /login --data-urlencode "_token=$t" \
  --data-urlencode "email=$SUREL" --data-urlencode "password=${SANDI}")"
cek F-11b "302 /dashboard" "$k ${tujuan#"$URL"}" "masuk lagi dengan akun tadi"

# ---- unggah ke object storage (Floci di compose)
base64 --decode > "$KERJA/foto.png" <<'PNG'
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==
PNG
t=$(token /berkas)
read -r k tujuan <<<"$(kirim /berkas --form-string "_token=$t" \
  -F "berkas=@$KERJA/foto.png;filename=foto uji.png")"
cek F-12 "302 /berkas" "$k ${tujuan#"$URL"}" "unggah PNG"
halaman=$(curl -s -m 10 -b "$KUKI" "$URL/berkas")
tautan=$(sed -n 's/.*href="\([^"]*\/unduh?[^"]*\)".*/\1/p' <<<"$halaman" \
  | head -n 1 | sed 's/&amp;/\&/g')
curl -s -m 10 -o "$KERJA/unduhan" "$tautan"
if cmp -s "$KERJA/foto.png" "$KERJA/unduhan"; then sama=ya; else sama=tidak; fi
cek F-12b "200/ya" "$(kode "$tautan")/$sama" "unduh lewat tautan bertanda tangan, isi sama"
cek F-13 403 "$(kode "${tautan}x")" "tanda tangan diubah ditolak"
printf '<?php echo 1;' > "$KERJA/jahat.png"
t=$(token /berkas)
kirim /berkas --form-string "_token=$t" -F "berkas=@$KERJA/jahat.png" >/dev/null
if curl -s -m 10 -b "$KUKI" "$URL/berkas" | grep -q "harus berkas bertipe"; then
  ditolak=ya; else ditolak=tidak; fi
cek F-14 ya "$ditolak" "PHP berkedok .png ditolak (jenis dari isi berkas)"

# ---- antrean: job dikerjakan service worker
id=$(curl -s -m 10 -H 'Accept: application/json' -d "nama=Budi (antrean)" \
  "$URL/api/tamu" | jq -r .id)
status=false
for _ in $(seq 1 30); do
  status=$(curl -s -m 10 "$URL/api/tamu/$id" | jq -r .notifikasi_terkirim)
  [ "$status" = true ] && break
  sleep 1
done
cek F-15 true "$status" "POST /api/tamu (id $id) -> notifikasi dikirim worker"
cek F-16 404 "$(kode "$URL/tidak-ada")" "alamat tidak ada"
cek F-17 422 "$(kode -H 'Accept: application/json' -d 'nama=' "$URL/api/tamu")" \
  "API: nama kosong ditolak"

# ---- P15: database mati (stop) dan macet (pause) -> 503 ramah
tunggu_pulih() {
  for _ in $(seq 1 60); do
    [ "$(kode "$URL/health")" = 200 ] && return
    sleep 1
  done
}
if [ -z "${TANPA_DB_MATI:-}" ]; then
  docker compose stop db >/dev/null 2>&1
  badan=$(curl -s -m 10 "$URL/health")
  cek F-18 503 "$(kode "$URL/health")" "/health saat db mati: $badan"
  kepala=$(curl -s -m 10 -D - -o "$KERJA/halaman" "$URL/")
  kode_h=$(head -n 1 <<<"$kepala" | cut -d' ' -f2)
  ra=$(grep -i '^retry-after:' <<<"$kepala" | tr -d '\r' | cut -d' ' -f2)
  maaf=$(grep -c "Maaf" "$KERJA/halaman")
  cek F-18b "503/60/1" "$kode_h/$ra/$maaf" "halaman utama: 503 ramah + Retry-After"
  cek F-18c 200 "$(kode "$URL/healthz")" "/healthz tetap hidup"
  docker compose start db >/dev/null 2>&1
  tunggu_pulih

  docker compose pause db >/dev/null 2>&1     # db hidup tetapi tidak menjawab
  dtk=$(curl -s -o /dev/null -m 15 -w "%{time_total}" "$URL/health")
  cek F-19 503 "$(kode "$URL/health")" "db macet: /health menyerah dalam ${dtk%.*} s (batas 3 s)"
  docker compose unpause db >/dev/null 2>&1
  tunggu_pulih
fi

# ---- uji modul kasus (bila sudah dipasang)
if [ -f web/tests/kasus.sh ]; then
  # shellcheck source=/dev/null
  source web/tests/kasus.sh
fi

if [ "$gagal" -ne 0 ]; then echo "ADA YANG GAGAL"; exit 1; fi
echo "Semua test case lulus"
