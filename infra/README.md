# infra/ · komponen Tugas Besar sebagai kode (P14)

Satu komponen per anggota, alur bukti slide 27: init + validate → plan →
apply → plan ulang *No changes* → `trivy config` → destroy. Tidak ada
secret di folder ini; state (`*.tfstate`) dan `terraform.tfvars` tidak
di-commit (`infra/.gitignore`). Yang di-commit per folder: `main.tf`,
`variables.tf`, `outputs.tf`, `.terraform.lock.hcl`.

| Komponen | Folder | Target | Provider | Dipakai aplikasi lewat env |
|----------|--------|--------|----------|----------------------------|
| Object storage berkas unggahan | `storage-file/` | Floci 2.4.0 | hashicorp/aws ~> 6.0 | `AWS_BUCKET` = output `bucket` |
| Antrean job + DLQ | `antrean/` | Floci 2.4.0 | hashicorp/aws ~> 6.0 | `QUEUE_CONNECTION=sqs`, `SQS_QUEUE` = `antrean-NIM` |

Kelompok menambah folder lain sesuai komponen kasusnya (mis. `db-dev/`
dengan provider Docker atau `db-neon/` dengan provider Neon, seperti modul
P6 dan P14).

## Cara menjalankan (setiap folder)
1. Floci menyala: `docker compose up -d --wait` dari akar repo (service
   `floci` membuka port 4566 di localhost).
2. Buat `terraform.tfvars` berisi `nim = "NIM"` (tidak di-commit).
3. Kunci palsu Floci lewat environment, sengaja tidak ditulis di `main.tf`:
   `export AWS_ACCESS_KEY_ID=test AWS_SECRET_ACCESS_KEY=test`
4. `terraform init`, `validate`, `plan`, `apply`; `plan` ulang = No changes;
   `docker run --rm -v "${PWD}:/src" aquasec/trivy:0.76.0 config /src`;
   `terraform destroy`. OpenTofu: ganti `terraform` dengan `tofu`.

Hasil yang diharapkan (NIM contoh `dosen`):

```
storage-file  Plan: 3 to add, 0 to change, 0 to destroy.   (bucket, blok publik, versioning)
              bucket = "tb-dosen-berkas"
              No changes. Your infrastructure matches the configuration.
              Destroy complete! Resources: 3 destroyed.
antrean       Plan: 2 to add, 0 to change, 0 to destroy.
              antrean_url = "http://localhost:4566/000000000000/antrean-dosen"
              dlq_arn = "arn:aws:sqs:ap-southeast-3:000000000000:antrean-dosen-dlq"
              No changes. Your infrastructure matches the configuration.
              Destroy complete! Resources: 2 destroyed.
```

## Menyambungkan ke aplikasi
Compose sudah membuat bucket `tb-berkas` dan (bila `QUEUE_CONNECTION=sqs`)
antrean `antrean-tb` lewat `php artisan lab:siapkan`. Untuk memakai
komponen buatan Terraform, ubah env service `app`, `worker`, `scheduler` di
`docker-compose.yml`: `AWS_BUCKET=tb-NIM-berkas`, `SQS_QUEUE=antrean-NIM`,
`AWS_DEFAULT_REGION=ap-southeast-3`, lalu `docker compose up -d --wait`.

## Temuan trivy config dan keputusan
| Folder | Temuan | Keputusan |
|--------|--------|-----------|
| antrean | AWS-0096 (HIGH) ×2: antrean tanpa enkripsi | sudah diperbaiki: `sqs_managed_sse_enabled = true` |
| storage-file | AWS-0090 (MEDIUM): tanpa versioning | sudah diperbaiki: `aws_s3_bucket_versioning` |
| storage-file | AWS-0132 (HIGH): tanpa customer managed key (KMS) | terima risiko: bucket lab Floci tanpa data pribadi; dicatat di `.trivyignore.yaml` |
| storage-file | AWS-0089 (LOW): tanpa access logging | terima risiko: log akses dipasang di rancangan target |
