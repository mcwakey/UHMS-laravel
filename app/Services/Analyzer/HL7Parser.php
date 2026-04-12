<?php

namespace App\Services\Analyzer;

/**
 * HL7 v2.x message parser.
 *
 * Parses ORU^R01 (Observation Result) messages which are the primary message type
 * for lab analyzer result delivery. Supports standard HL7 v2.3-2.5 segment structure.
 *
 * Message structure:
 *   MSH — Message Header
 *   PID — Patient Identification
 *   OBR — Observation Request (order)
 *   OBX — Observation Result (result value)
 */
class HL7Parser
{
    protected string $segmentSeparator = "\r";
    protected string $fieldSeparator = '|';
    protected string $componentSeparator = '^';
    protected string $repetitionSeparator = '~';
    protected string $escapeCharacter = '\\';
    protected string $subComponentSeparator = '&';

    /**
     * Parse a raw HL7 message string into structured data.
     *
     * @return array{
     *     message_type: string,
     *     sample_id: string|null,
     *     patient_id: string|null,
     *     patient_name: string|null,
     *     results: array<int, array{test_code: string, result_value: string, unit: string|null, reference_range: string|null, abnormal_flag: string|null}>,
     *     raw_segments: array
     * }
     */
    public function parse(string $rawMessage): array
    {
        $rawMessage = $this->stripMLLP($rawMessage);
        $segments = $this->splitSegments($rawMessage);

        if (empty($segments)) {
            throw new \RuntimeException('Empty HL7 message — no segments found.');
        }

        // Parse MSH to get encoding characters
        $msh = $this->parseSegment($segments[0]);
        if (($msh[0] ?? '') !== 'MSH') {
            throw new \RuntimeException('Invalid HL7 message — must start with MSH segment.');
        }

        // Extract encoding characters from MSH-2 (the value after MSH|)
        if (isset($msh[1]) && strlen($msh[1]) >= 4) {
            $this->componentSeparator = $msh[1][0];
            $this->repetitionSeparator = $msh[1][1];
            $this->escapeCharacter = $msh[1][2];
            $this->subComponentSeparator = $msh[1][3];
        }

        $messageType = $msh[8] ?? 'UNKNOWN';
        $sampleId = null;
        $patientId = null;
        $patientName = null;
        $results = [];
        $rawSegments = [];

        foreach ($segments as $segmentStr) {
            $fields = $this->parseSegment($segmentStr);
            $segType = $fields[0] ?? '';
            $rawSegments[] = ['type' => $segType, 'fields' => $fields];

            switch ($segType) {
                case 'PID':
                    $patientId = $this->getComponent($fields[3] ?? '', 0);
                    $patientName = $this->formatPatientName($fields[5] ?? '');
                    break;

                case 'OBR':
                    // OBR-3: Filler order number (often used as sample/accession ID)
                    $sampleId = $this->getComponent($fields[3] ?? '', 0) ?: $sampleId;
                    // Fallback: OBR-2: Placer order number
                    if (!$sampleId) {
                        $sampleId = $this->getComponent($fields[2] ?? '', 0);
                    }
                    break;

                case 'OBX':
                    $result = $this->parseOBX($fields);
                    if ($result) {
                        $results[] = $result;
                    }
                    break;
            }
        }

        return [
            'message_type' => $messageType,
            'sample_id' => $sampleId,
            'patient_id' => $patientId,
            'patient_name' => $patientName,
            'results' => $results,
            'raw_segments' => $rawSegments,
        ];
    }

    /**
     * Parse an OBX (Observation Result) segment.
     */
    protected function parseOBX(array $fields): ?array
    {
        // OBX-3: Observation Identifier (test code^description)
        $testIdentifier = $fields[3] ?? '';
        $testCode = $this->getComponent($testIdentifier, 0);

        if (empty($testCode)) {
            return null;
        }

        // OBX-5: Observation Value
        $resultValue = $fields[5] ?? '';

        // OBX-6: Units
        $unit = $this->getComponent($fields[6] ?? '', 0);

        // OBX-7: Reference Range
        $referenceRange = $fields[7] ?? null;

        // OBX-8: Abnormal Flags (H=High, L=Low, HH=Critical High, LL=Critical Low, N=Normal, A=Abnormal)
        $abnormalFlag = $fields[8] ?? null;

        // OBX-11: Observation Result Status (F=Final, P=Preliminary, C=Corrected)
        $status = $fields[11] ?? 'F';

        return [
            'test_code' => $testCode,
            'test_description' => $this->getComponent($testIdentifier, 1),
            'result_value' => $resultValue,
            'unit' => $unit ?: null,
            'reference_range' => $referenceRange ?: null,
            'abnormal_flag' => $abnormalFlag ?: null,
            'status' => $status,
        ];
    }

    /**
     * Strip MLLP (Minimal Lower Layer Protocol) framing characters.
     * MLLP wraps HL7 messages: <VT> message <FS><CR>
     */
    protected function stripMLLP(string $message): string
    {
        // Remove vertical tab (0x0B) at start
        $message = ltrim($message, "\x0B");
        // Remove file separator (0x1C) and trailing CR at end
        $message = rtrim($message, "\x1C\r\n");

        return trim($message);
    }

    /**
     * Split raw message into segments by CR.
     */
    protected function splitSegments(string $message): array
    {
        // HL7 uses \r as segment separator, but handle \r\n and \n too
        $segments = preg_split('/[\r\n]+/', $message, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($segments, fn ($s) => trim($s) !== ''));
    }

    /**
     * Parse a single segment string into an array of fields.
     */
    protected function parseSegment(string $segment): array
    {
        return explode($this->fieldSeparator, $segment);
    }

    /**
     * Get a specific component from a field value.
     */
    protected function getComponent(string $field, int $index): string
    {
        $components = explode($this->componentSeparator, $field);

        return trim($components[$index] ?? '');
    }

    /**
     * Format HL7 patient name (Last^First^Middle) into readable form.
     */
    protected function formatPatientName(string $nameField): string
    {
        $parts = explode($this->componentSeparator, $nameField);
        $last = $parts[0] ?? '';
        $first = $parts[1] ?? '';

        return trim("{$first} {$last}");
    }
}
