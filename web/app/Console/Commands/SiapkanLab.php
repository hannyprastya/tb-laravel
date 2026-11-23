<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menyiapkan Floci (emulator AWS) untuk lab: membuat bucket disk s3 dan,
 * bila QUEUE_CONNECTION=sqs, antrean SQS + DLQ. Aman dijalankan berulang.
 * Dipanggil otomatis saat container start bila SIAPKAN_FLOCI=1 (compose).
 * Di produksi bucket dibuat lewat dasbor provider atau Terraform (infra/).
 */
class SiapkanLab extends Command
{
    protected $signature = 'lab:siapkan {--coba=15 : percobaan bila Floci belum siap}';

    protected $description = 'Buat bucket (dan antrean SQS) di Floci untuk lab';

    public function handle(): int
    {
        $coba = max(1, (int) $this->option('coba'));
        for ($i = 1; $i <= $coba; $i++) {
            try {
                $this->buatBucket();
                if (config('queue.default') === 'sqs') {
                    $this->buatAntrean();
                }

                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->warn("percobaan $i/$coba gagal: ".strtok($e->getMessage(), "\n"));
                sleep(2);
            }
        }
        $this->error('Floci tidak bisa dihubungi; unggah berkas belum bisa dipakai.');

        return self::FAILURE;
    }

    private function buatBucket(): void
    {
        $disk = Storage::disk('s3');
        if (! $disk instanceof AwsS3V3Adapter) {
            return;
        }
        $bucket = (string) config('filesystems.disks.s3.bucket');
        $s3 = $disk->getClient();
        if ($s3->doesBucketExistV2($bucket)) {
            $this->line("bucket $bucket sudah ada");

            return;
        }
        $s3->createBucket(['Bucket' => $bucket]);
        $this->info("bucket $bucket dibuat");
    }

    /** Antrean utama + DLQ: setelah 5 kali gagal diterima, pesan pindah ke DLQ. */
    private function buatAntrean(): void
    {
        $antrean = Queue::connection('sqs');
        if (! $antrean instanceof SqsQueue) {
            return;
        }
        $sqs = $antrean->getSqs();
        $nama = (string) config('queue.connections.sqs.queue');
        $dlq = $sqs->createQueue(['QueueName' => $nama.'-dlq']);
        $arn = $sqs->getQueueAttributes([
            'QueueUrl' => $dlq->get('QueueUrl'),
            'AttributeNames' => ['QueueArn'],
        ])->get('Attributes')['QueueArn'];
        $sqs->createQueue([
            'QueueName' => $nama,
            'Attributes' => [
                'VisibilityTimeout' => '60',
                'RedrivePolicy' => json_encode([
                    'deadLetterTargetArn' => $arn,
                    'maxReceiveCount' => 5,
                ]),
            ],
        ]);
        $this->info("antrean $nama + $nama-dlq siap");
    }
}
