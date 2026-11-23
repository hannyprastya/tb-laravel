output "antrean_url" {
  value = aws_sqs_queue.utama.url
}
output "dlq_arn" {
  value = aws_sqs_queue.dlq.arn
}
