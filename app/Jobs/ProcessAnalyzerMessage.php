<?php

namespace App\Jobs;

use App\Models\AnalyzerRawMessage;
use App\Services\Analyzer\ASTMParser;
use App\Services\Analyzer\HL7Parser;
use App\Services\Analyzer\ResultDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAnalyzerMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int $messageId
    ) {}

    public function handle(ResultDispatchService $dispatcher): void
    {
        $message = AnalyzerRawMessage::find($this->messageId);

        if (!$message) {
            Log::warning("ProcessAnalyzerMessage: Message #{$this->messageId} not found.");
            return;
        }

        // Skip if already processed or duplicate
        if (in_array($message->processing_status, ['processed', 'duplicate'])) {
            return;
        }

        $message->markProcessing();

        try {
            // Select parser based on protocol
            $parsed = $this->parseMessage($message);

            // Dispatch results to lab system
            $report = $dispatcher->dispatch($message, $parsed);

            $message->markProcessed();

            Log::info('Analyzer message processed', [
                'message_id' => $message->id,
                'sample_id' => $parsed['sample_id'] ?? null,
                'matched' => $report['matched'],
                'unmatched' => $report['unmatched'],
                'errors' => count($report['errors']),
            ]);

            // Store processing report in error_message field for diagnostics
            if (!empty($report['errors'])) {
                $message->update([
                    'error_message' => 'Warnings: ' . implode('; ', $report['errors']),
                ]);
            }

        } catch (\Throwable $e) {
            $message->markFailed($e->getMessage());

            Log::error('Analyzer message processing failed', [
                'message_id' => $message->id,
                'attempt' => $message->processing_attempts,
                'error' => $e->getMessage(),
            ]);

            // Re-throw so the queue can retry
            throw $e;
        }
    }

    /**
     * Parse the raw message using the appropriate protocol parser.
     */
    protected function parseMessage(AnalyzerRawMessage $message): array
    {
        return match ($message->protocol) {
            'hl7' => (new HL7Parser())->parse($message->content),
            'astm' => (new ASTMParser())->parse($message->content),
            default => throw new \RuntimeException("Unsupported protocol: {$message->protocol}"),
        };
    }

    /**
     * Handle job failure after all retries exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $message = AnalyzerRawMessage::find($this->messageId);

        if ($message) {
            $message->update([
                'processing_status' => 'failed',
                'error_message' => 'Final failure: ' . $exception->getMessage(),
            ]);
        }

        Log::error('Analyzer message processing permanently failed', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
