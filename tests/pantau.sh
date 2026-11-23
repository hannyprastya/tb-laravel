#!/usr/bin/env bash
# tests/pantau.sh: jam + kode HTTP /health tiap 2 detik (Ctrl+C untuk berhenti)
URL=${URL:-http://localhost:8080}
while true; do
  printf "%s " "$(date +%T)"
  curl -s -o /dev/null -m 5 -w "%{http_code}\n" "$URL/health"
  sleep 2
done
