@props([
    'title',
    'patient' => null,       // App\Models\Patient or null
    'visit' => null,         // App\Models\Visit or null
    'subtitle' => null,
    'generatedAt' => null,
    'signatures' => null,    // array of signature labels, e.g. ['Prepared by', 'Authorised by']
])

@php
    $generatedAt = $generatedAt ?: now();
    $hospital = config('app.name', 'UHMS');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} · {{ $hospital }}</title>
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}">
    <style>
        body { background:#fff; color:#000; font-size:13px; }
        .print-sheet { max-width: 820px; margin: 0 auto; padding: 24px; }
        .print-doc-title { letter-spacing:.5px; }
        table { font-size: 12.5px; }
        @media print {
            .d-print-none { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-sheet { max-width: 100%; padding: 0; }
            a { color:#000; text-decoration:none; }
        }
    </style>
</head>
<body>
    <div class="print-sheet">
        {{-- Hospital header --}}
        <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-3">
            <div>
                <div class="h5 fw-bold mb-0">{{ $hospital }}</div>
                <div class="text-muted small">Ultimate Hospital Management System</div>
            </div>
            <div class="text-end small text-muted">
                Generated: {{ $generatedAt instanceof \Illuminate\Support\Carbon || $generatedAt instanceof \Carbon\Carbon ? $generatedAt->format('d M Y, h:i A') : $generatedAt }}
            </div>
        </div>

        {{-- Document title --}}
        <div class="text-center mb-3">
            <div class="h5 fw-bold text-uppercase print-doc-title mb-0">{{ $title }}</div>
            @if($subtitle)<div class="text-muted small">{{ $subtitle }}</div>@endif
        </div>

        {{-- Patient / visit context --}}
        @if($patient || $visit)
            <div class="row g-2 border rounded p-2 mb-3 small">
                @if($patient)
                    <div class="col-6"><span class="text-muted">Patient:</span> <strong>{{ $patient->full_name ?? trim(($patient->first_name ?? '').' '.($patient->last_name ?? '')) }}</strong></div>
                    <div class="col-3"><span class="text-muted">Patient No:</span> {{ $patient->patient_number ?? '—' }}</div>
                    <div class="col-3"><span class="text-muted">Gender / Age:</span> {{ $patient->gender ?? '—' }}{{ isset($patient->date_of_birth) && $patient->date_of_birth ? ' · '.$patient->date_of_birth->age.'y' : '' }}</div>
                @endif
                @if($visit)
                    <div class="col-6"><span class="text-muted">Visit No:</span> {{ $visit->visit_number ?? '—' }}</div>
                    <div class="col-6"><span class="text-muted">Visit Date:</span> {{ optional($visit->visit_date ?? $visit->created_at)->format('d M Y') }}</div>
                @endif
            </div>
        @endif

        {{-- Content --}}
        <div class="print-content">
            {{ $slot }}
        </div>

        {{-- Signatures --}}
        @if(!empty($signatures))
            <div class="row g-4 mt-5 pt-4">
                @foreach($signatures as $sig)
                    <div class="col text-center">
                        <div class="border-top pt-1 mx-3 small text-muted">{{ $sig }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Print control (never printed) --}}
    <div class="d-print-none text-center my-4">
        <button onclick="window.print()" class="btn btn-primary"><i class="ti ti-printer me-1"></i>Print</button>
        <button onclick="window.history.back()" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</button>
    </div>
</body>
</html>
