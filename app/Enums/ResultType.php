<?php

namespace App\Enums;

enum ResultType: string
{
    case PARAMETERS = 'parameters'; // structured: value + normal range + abnormal flag (lab)
    case RICHTEXT   = 'richtext';   // narrative text report (radiology, scan)
    case IMAGE      = 'image';      // image file upload (X-ray, ultrasound still)
    case DOCUMENT   = 'document';   // document/PDF upload (pathology reports, etc.)
    case NONE       = 'none';       // no result expected (procedure-only departments)

    public function label(): string
    {
        return match ($this) {
            self::PARAMETERS => 'Parameters (Lab)',
            self::RICHTEXT   => 'Rich Text Report',
            self::IMAGE      => 'Image Upload',
            self::DOCUMENT   => 'Document Upload',
            self::NONE       => 'No Result',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PARAMETERS => 'ti-list-check',
            self::RICHTEXT   => 'ti-align-left',
            self::IMAGE      => 'ti-photo',
            self::DOCUMENT   => 'ti-file-text',
            self::NONE       => 'ti-ban',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PARAMETERS => 'primary',
            self::RICHTEXT   => 'info',
            self::IMAGE      => 'warning',
            self::DOCUMENT   => 'secondary',
            self::NONE       => 'light',
        };
    }

    /** Whether this result type accepts lab test catalog items */
    public function usesTestCatalog(): bool
    {
        return $this === self::PARAMETERS;
    }

    /** Whether a file is expected */
    public function isFileBased(): bool
    {
        return in_array($this, [self::IMAGE, self::DOCUMENT]);
    }

    /**
     * Result types selectable when configuring a department.
     * Image / Document are deprecated as primary types — file uploads are
     * now always available alongside parameters or rich text.
     */
    public static function selectableCases(): array
    {
        return [self::NONE, self::PARAMETERS, self::RICHTEXT];
    }
}
