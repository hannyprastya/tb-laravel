// tests/k6/uji-maks.js (P13 C.0): salinan uji.js TANPA sleep(1), untuk
// kapasitas maksimum (req/s). Hanya ke compose sendiri: --vus 50 -d 30s.
import http from "k6/http";
import { check } from "k6";
const RUTE = __ENV.RUTE || "/tamu";
export const options = {
  vus: 10, duration: "1m",
  thresholds: {
    http_req_duration: ["p(95)<1000"],
    http_req_failed: ["rate<0.01"],
  },
};
export default function () {
  const r = http.get(__ENV.URL + RUTE);
  check(r, { "status 200": (x) => x.status === 200 });
}
