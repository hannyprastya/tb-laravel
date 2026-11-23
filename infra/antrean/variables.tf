variable "nim" {
  type = string
}
# P14 Bagian C: job KirimNotifikasi selesai < 10 s, visibility = 6 x 10 s =
# 60 s. Worker Laravel mencoba 3 kali (--tries=3) lalu mencatat job di tabel
# failed_jobs; maxReceiveCount 5 (> 3) = DLQ hanya menangkap pesan yang
# membuat worker mati sebelum sempat mencatat kegagalan.
variable "visibility_detik" {
  type    = number
  default = 60
}
variable "max_terima" {
  type    = number
  default = 5
}
