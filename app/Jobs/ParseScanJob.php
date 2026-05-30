<?php

namespace App\Jobs;

use App\Models\ScanParseJob;
use App\Services\ScanParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs the InBody extraction off the web request so POST /scans/upload can
 * return a jobId immediately and the app polls GET /scans/parse/{jobId}.
 */
class ParseScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A Claude vision + thinking call can run well past the default 60s. */
    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(public readonly string $scanParseJobId) {}

    public function handle(ScanParserService $parser): void
    {
        $parser->fulfill($this->scanParseJobId);
    }

    public function failed(?Throwable $e): void
    {
        ScanParseJob::whereKey($this->scanParseJobId)
            ->where('status', '!=', 'done')
            ->update(['status' => 'failed']);
    }
}
