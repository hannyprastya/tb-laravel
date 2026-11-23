// tests/k6/uji-otel.js (P11 slide 7): 20 VU, 1 menit, campuran halaman
// (dengan sesi), halaman publik, dan API, supaya trace beberapa rute
// terlihat di Tempo dan dasbor SLO.
import http from "k6/http";
import { check, sleep } from "k6";
export const options = { vus: 20, duration: "1m" };
const RUTE = ["/", "/tamu", "/api/tamu"];
export default function () {
  const r = http.get(__ENV.URL + RUTE[Math.floor(Math.random() * RUTE.length)]);
  check(r, { "status 200": (x) => x.status === 200 });
  sleep(1);
}
