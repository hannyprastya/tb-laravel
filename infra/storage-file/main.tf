terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 6.0"
    }
  }
}
# provider aws diarahkan ke Floci (emulator AWS) di codespace/laptop, bukan
# ke AWS sungguhan. Kunci "test" bawaan Floci dibaca dari environment
# (AWS_ACCESS_KEY_ID/AWS_SECRET_ACCESS_KEY, P14 E.3), tidak ditulis di kode.
provider "aws" {
  region                      = "ap-southeast-3" # Jakarta
  skip_credentials_validation = true
  skip_requesting_account_id  = true
  s3_use_path_style           = true
  endpoints {
    s3 = "http://localhost:4566" # alamat Floci
  }
}
# bucket berkas unggahan aplikasi (disk s3 Laravel, halaman /berkas). Privat:
# berkas hanya dibuka lewat tautan bertanda tangan buatan aplikasi.
# Dipakai aplikasi dengan env AWS_BUCKET = output "bucket".
resource "aws_s3_bucket" "file" {
  bucket = "tb-${var.nim}-berkas"
}
resource "aws_s3_bucket_public_access_block" "file" {
  bucket                  = aws_s3_bucket.file.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}
# P14 E.7: perbaikan AWS-0090 (berkas tertimpa/terhapus masih bisa dipulihkan)
resource "aws_s3_bucket_versioning" "file" {
  bucket = aws_s3_bucket.file.id
  versioning_configuration {
    status = "Enabled"
  }
}
