<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAnalyzerMessage;
use App\Models\Analyzer;
use App\Services\Analyzer\AnalyzerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AnalyzerListenCommand extends Command
{
    protected $signature = 'analyzer:listen
        {--analyzer= : ID of a specific analyzer to listen for}
        {--port= : Override the listening port}
        {--timeout=0 : Socket timeout in seconds (0 = no timeout)}';

    protected $description = 'Listen for incoming lab analyzer data via TCP socket';

    protected bool $running = true;

    public function handle(AnalyzerService $analyzerService): int
    {
        $analyzerId = $this->option('analyzer');

        if ($analyzerId) {
            $analyzer = Analyzer::active()->find($analyzerId);
            if (!$analyzer) {
                $this->error("Analyzer #{$analyzerId} not found or inactive.");
                return self::FAILURE;
            }
            $analyzers = collect([$analyzer]);
        } else {
            $analyzers = Analyzer::active()->tcp()->get();
        }

        if ($analyzers->isEmpty()) {
            $this->warn('No active TCP analyzers configured.');
            return self::SUCCESS;
        }

        $this->info('Starting analyzer listener...');
        $this->table(
            ['ID', 'Name', 'Protocol', 'Address', 'Port'],
            $analyzers->map(fn ($a) => [$a->id, $a->name, $a->protocol, $a->ip_address, $a->port])
        );

        // For simplicity, listen on a single port
        $port = (int) ($this->option('port') ?: $analyzers->first()->port ?: 9100);
        $timeout = (int) $this->option('timeout');

        return $this->listenOnPort($port, $analyzers, $analyzerService, $timeout);
    }

    protected function listenOnPort(
        int $port,
        $analyzers,
        AnalyzerService $analyzerService,
        int $timeout
    ): int {
        $socket = @stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);

        if (!$socket) {
            $this->error("Failed to bind to port {$port}: {$errstr} ({$errno})");
            return self::FAILURE;
        }

        $this->info("Listening on TCP port {$port}...");
        Log::info("Analyzer listener started on port {$port}");

        // Handle graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, fn () => $this->running = false);
            pcntl_signal(SIGTERM, fn () => $this->running = false);
        }

        while ($this->running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $conn = @stream_socket_accept($socket, 5);

            if (!$conn) {
                continue;
            }

            $remoteAddr = stream_socket_get_name($conn, true);
            $this->line("[" . now()->format('H:i:s') . "] Connection from {$remoteAddr}");

            try {
                $data = $this->readConnection($conn, $timeout);

                if (empty(trim($data))) {
                    $this->warn("  Empty data received, skipping.");
                    fclose($conn);
                    continue;
                }

                // Match analyzer by IP address
                $clientIp = explode(':', $remoteAddr)[0];
                $analyzer = $analyzers->first(fn ($a) => $a->ip_address === $clientIp);

                $protocol = $analyzer ? $analyzer->protocol : $this->detectProtocol($data);
                $analyzerId = $analyzer ? $analyzer->id : null;

                $this->info("  Protocol: {$protocol} | Data size: " . strlen($data) . " bytes");

                // Update last connected timestamp
                if ($analyzer) {
                    $analyzerService->touchConnection($analyzer);
                }

                // Store raw message
                $message = $analyzerService->storeRawMessage(
                    $analyzerId,
                    $protocol,
                    $data,
                    'inbound'
                );

                if ($message->processing_status === 'duplicate') {
                    $this->warn("  Duplicate message detected (ID: {$message->id})");
                } else {
                    $this->info("  Message stored (ID: {$message->id}), dispatching job...");
                    ProcessAnalyzerMessage::dispatch($message->id);
                }

                // Send ACK if HL7 (MLLP)
                if ($protocol === 'hl7') {
                    $ack = $this->buildHL7Ack();
                    fwrite($conn, $ack);
                }

                // Send ACK if ASTM
                if ($protocol === 'astm') {
                    fwrite($conn, "\x06"); // ACK
                }

            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('Analyzer listener error', [
                    'remote' => $remoteAddr,
                    'error' => $e->getMessage(),
                ]);
            }

            fclose($conn);
        }

        fclose($socket);
        $this->info("\nListener stopped.");
        Log::info('Analyzer listener stopped');

        return self::SUCCESS;
    }

    /**
     * Read all data from a connection.
     */
    protected function readConnection($conn, int $timeout): string
    {
        $data = '';
        $readTimeout = $timeout > 0 ? $timeout : 10;
        stream_set_timeout($conn, $readTimeout);

        while (!feof($conn)) {
            $chunk = fread($conn, 8192);

            if ($chunk === '' || $chunk === false) {
                break;
            }

            $data .= $chunk;

            // Check for HL7 end: FS + CR
            if (str_contains($data, "\x1c\x0d")) {
                break;
            }

            // Check for ASTM EOT
            if (str_contains($data, "\x04")) {
                break;
            }

            $info = stream_get_meta_data($conn);
            if ($info['timed_out']) {
                break;
            }
        }

        return $data;
    }

    /**
     * Auto-detect protocol from raw data.
     */
    protected function detectProtocol(string $data): string
    {
        // HL7: starts with VT (0x0B) or contains MSH|
        if (str_starts_with($data, "\x0b") || str_contains($data, 'MSH|')) {
            return 'hl7';
        }

        // ASTM: starts with ENQ (0x05) or contains H|\^&
        if (str_starts_with($data, "\x05") || str_contains($data, 'H|\\^&')) {
            return 'astm';
        }

        return 'hl7'; // Default
    }

    /**
     * Build a minimal HL7 ACK message wrapped in MLLP.
     */
    protected function buildHL7Ack(): string
    {
        $timestamp = now()->format('YmdHis');
        $msg = "MSH|^~\\&|UHMS|HOSPITAL|||{$timestamp}||ACK|{$timestamp}|P|2.3\rMSA|AA|{$timestamp}\r";

        return "\x0b" . $msg . "\x1c\x0d";
    }
}
