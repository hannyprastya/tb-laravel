terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 6.0"
    }
  }
}
# sama dengan storage-file: provider aws diarahkan ke Floci, kunci "test"
# dari environment (P14 E.3)
provider "aws" {
  region                      = "ap-southeast-3" # Jakarta
  skip_credentials_validation = true
  skip_requesting_account_id  = true
  endpoints {
    sqs = "http://localhost:4566" # alamat Floci
  }
}
# antrean job Laravel (QUEUE_CONNECTION=sqs) + DLQ. Aplikasi memakai env
# SQS_PREFIX = antrean_url tanpa nama antrean, SQS_QUEUE = antrean-NIM.
resource "aws_sqs_queue" "dlq" {
  name                      = "antrean-${var.nim}-dlq"
  message_retention_seconds = 1209600 # 14 hari: waktu memeriksa pesan rusak
  sqs_managed_sse_enabled   = true    # P14 E.7: perbaikan AWS-0096
}
resource "aws_sqs_queue" "utama" {
  name                       = "antrean-${var.nim}"
  visibility_timeout_seconds = var.visibility_detik
  sqs_managed_sse_enabled    = true # P14 E.7: perbaikan AWS-0096
  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.dlq.arn
    maxReceiveCount     = var.max_terima
  })
}
