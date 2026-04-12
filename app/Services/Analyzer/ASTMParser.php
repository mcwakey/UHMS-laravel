<?php

namespace App\Services\Analyzer;

/**
 * ASTM E1394 / LIS2-A2 message parser.
 *
 * Parses lab instrument messages using the ASTM standard record structure:
 *   H — Header Record (session start)
 *   P — Patient Record
 *   O — Order Record (test order / sample info)
 *   R — Result Record (observation value)
 *   C — Comment Record
 *   L — Terminator Record (session end)
 *
 * Fields are separated by '|', components by '^', and records end with <CR>.
 * Records may have a frame sequence number (1-7) for data link layer.
 */
class ASTMParser
{
    protected string $fieldSeparator = '|';
    protected string $componentSeparator = '^';
    protected string $repeatSeparator = '\\';
    protected string $escapeSeparator = '&';

    /**
     * Parse a raw ASTM message into structured data.
     *
     * @return array{
     *     sample_id: string|null,
     *     patient_id: string|null,
     *     patient_name: string|null,
     *     results: array<int, array{test_code: string, result_value: string, unit: string|null, reference_range: string|null, abnormal_flag: string|null}>,
     *     raw_records: array
     * }
     */
    public function parse(string $rawMessage): array
    {
        $rawMessage = $this->stripFraming($rawMessage);
        $records = $this->splitRecords($rawMessage);

        if (empty($records)) {
            throw new \RuntimeException('Empty ASTM message — no records found.');
        }

        $sampleId = null;
        $patientId = null;
        $patientName = null;
        $results = [];
        $rawRecords = [];

        foreach ($records as $recordStr) {
            $fields = $this->parseRecord($recordStr);
            $recordType = $this->getRecordType($fields);
            $rawRecords[] = ['type' => $recordType, 'fields' => $fields];

            switch ($recordType) {
                case 'H':
                    // Header record — extract delimiter definitions if present
                    $this->parseHeaderDelimiters($fields);
                    break;

                case 'P':
                    // Patient record
                    $patientId = $this->getComponent($fields[3] ?? '', 0);
                    $patientName = $this->formatPatientName($fields[6] ?? '');
                    break;

                case 'O':
                    // Order record — sample/accession ID
                    $sampleId = $this->getComponent($fields[2] ?? '', 0) ?: $sampleId;
                    // Fallback: instrument specimen ID
                    if (!$sampleId) {
                        $sampleId = $this->getComponent($fields[3] ?? '', 0);
                    }
                    break;

                case 'R':
                    // Result record
                    $result = $this->parseResultRecord($fields);
                    if ($result) {
                        $results[] = $result;
                    }
                    break;

                case 'L':
                    // Terminator — end of session
                    break;
            }
        }

        return [
            'sample_id' => $sampleId,
            'patient_id' => $patientId,
            'patient_name' => $patientName,
            'results' => $results,
            'raw_records' => $rawRecords,
        ];
    }

    /**
     * Parse an R (Result) record.
     */
    protected function parseResultRecord(array $fields): ?array
    {
        // R|seq|TestCode^TestName^...||ResultValue|Unit|RefRange|AbnormalFlag|...
        $testIdentifier = $fields[2] ?? '';
        $testCode = $this->getComponent($testIdentifier, 3); // ASTM: 4th component is often the test code

        // Fallback: try first component
        if (empty($testCode)) {
            $testCode = $this->getComponent($testIdentifier, 0);
        }

        if (empty($testCode)) {
            return null;
        }

        $resultValue = $fields[3] ?? '';
        $unit = $fields[4] ?? null;
        $referenceRange = $fields[5] ?? null;
        $abnormalFlag = $fields[6] ?? null;
        $status = $fields[8] ?? 'F'; // F=Final, P=Preliminary

        return [
            'test_code' => trim($testCode),
            'test_description' => $this->getComponent($testIdentifier, 4) ?: $this->getComponent($testIdentifier, 1),
            'result_value' => trim($resultValue),
            'unit' => $unit ? trim($unit) : null,
            'reference_range' => $referenceRange ? trim($referenceRange) : null,
            'abnormal_flag' => $abnormalFlag ? trim($abnormalFlag) : null,
            'status' => trim($status),
        ];
    }

    /**
     * Strip ASTM data link layer framing.
     *
     * ASTM frames: <STX>[frame#][data]<ETX>[checksum]<CR><LF>
     * Also handles <ENQ>, <EOT>, <ACK>, <NAK> control characters.
     */
    protected function stripFraming(string $message): string
    {
        // Remove control characters: STX(0x02), ETX(0x03), EOT(0x04), ENQ(0x05), ACK(0x06), NAK(0x15)
        $message = preg_replace('/[\x02\x03\x04\x05\x06\x15]/', '', $message);

        // Remove frame sequence numbers (single digit at start of each line after STX)
        $lines = preg_split('/[\r\n]+/', $message, -1, PREG_SPLIT_NO_EMPTY);
        $cleaned = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // Remove checksum at end (2 hex digits after ETX removal)
            $line = preg_replace('/[0-9A-F]{2}$/i', '', $line);
            // Remove leading frame number (single digit 0-7)
            $line = preg_replace('/^[0-7]/', '', $line);
            $line = trim($line);

            if ($line !== '') {
                $cleaned[] = $line;
            }
        }

        return implode("\n", $cleaned);
    }

    /**
     * Split message into individual records.
     */
    protected function splitRecords(string $message): array
    {
        $records = preg_split('/[\r\n]+/', $message, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($records, fn ($r) => trim($r) !== ''));
    }

    /**
     * Parse a single record into fields.
     */
    protected function parseRecord(string $record): array
    {
        return explode($this->fieldSeparator, trim($record));
    }

    /**
     * Get the record type (first character: H, P, O, R, C, L, Q, M).
     */
    protected function getRecordType(array $fields): string
    {
        $first = trim($fields[0] ?? '');

        // Record type is the first character
        return strtoupper(substr($first, 0, 1));
    }

    /**
     * Parse Header record delimiter definitions.
     */
    protected function parseHeaderDelimiters(array $fields): void
    {
        // H record: H|\^&| — the delimiter definition field
        $delimiters = $fields[1] ?? '';

        if (strlen($delimiters) >= 3) {
            $this->repeatSeparator = $delimiters[0];
            $this->componentSeparator = $delimiters[1];
            $this->escapeSeparator = $delimiters[2];
        }
    }

    /**
     * Get a specific component from a field.
     */
    protected function getComponent(string $field, int $index): string
    {
        $components = explode($this->componentSeparator, $field);

        return trim($components[$index] ?? '');
    }

    /**
     * Format ASTM patient name into readable form.
     */
    protected function formatPatientName(string $nameField): string
    {
        $parts = explode($this->componentSeparator, $nameField);
        $last = $parts[0] ?? '';
        $first = $parts[1] ?? '';

        return trim("{$first} {$last}");
    }
}
