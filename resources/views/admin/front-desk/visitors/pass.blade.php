<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('front_desk.pass.title') }} — {{ $log->badge_number ?? $log->visitor_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 0; padding: 24px; }
        .pass { max-width: 420px; margin: 0 auto; border: 2px solid #0d6efd; border-radius: 10px; padding: 20px; }
        .pass h1 { font-size: 18px; margin: 0 0 2px; text-align: center; }
        .pass .facility { text-align: center; color: #555; font-size: 13px; margin-bottom: 12px; }
        .pass .badge-no { text-align: center; font-size: 22px; font-weight: 700; letter-spacing: 1px; margin: 8px 0 14px; }
        .pass table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .pass td { padding: 5px 4px; vertical-align: top; border-bottom: 1px solid #eee; }
        .pass td.k { color: #666; width: 42%; }
        .pass td.v { font-weight: 600; }
        .pass .note { margin-top: 12px; font-size: 11px; color: #666; text-align: center; }
        .actions { max-width: 420px; margin: 16px auto 0; text-align: center; }
        .actions button { padding: 8px 18px; font-size: 14px; border: 1px solid #0d6efd; background: #0d6efd; color: #fff; border-radius: 6px; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="pass">
        <h1>{{ __('front_desk.pass.title') }}</h1>
        <div class="facility">{{ config('app.name', 'UHMS') }}</div>
        <div class="badge-no">{{ $log->badge_number ?? '—' }}</div>
        <table>
            <tr><td class="k">{{ __('front_desk.fields.visitor_name') }}</td><td class="v">{{ $log->visitor_name }}</td></tr>
            @if($log->patient)
            <tr><td class="k">{{ __('front_desk.fields.patient') }}</td><td class="v">{{ $log->patient->patient_number }} — {{ $log->patient->full_name }}</td></tr>
            @endif
            @if($log->ward)
            <tr><td class="k">{{ __('front_desk.fields.ward') }}</td><td class="v">{{ $log->ward->name }}</td></tr>
            @endif
            @if($log->bed)
            <tr><td class="k">{{ __('front_desk.fields.bed') }}</td><td class="v">{{ $log->bed->bed_number ?? $log->bed_id }}</td></tr>
            @endif
            @if($log->department)
            <tr><td class="k">{{ __('front_desk.fields.department') }}</td><td class="v">{{ $log->department->name }}</td></tr>
            @endif
            @if($log->purpose)
            <tr><td class="k">{{ __('front_desk.fields.purpose') }}</td><td class="v">{{ $log->purpose }}</td></tr>
            @endif
            <tr><td class="k">{{ __('front_desk.fields.time_in') }}</td><td class="v">{{ $log->time_in?->format('d M Y H:i') }}</td></tr>
            <tr><td class="k">{{ __('front_desk.fields.expected_checkout') }}</td><td class="v">{{ $log->expectedCheckoutAt()?->format('d M Y H:i') ?? '—' }}</td></tr>
            <tr><td class="k">{{ __('front_desk.fields.checked_in_by') }}</td><td class="v">{{ $log->checkedInBy?->full_name ?? '—' }}</td></tr>
        </table>
        <div class="note">{{ __('front_desk.pass.instructions') }}</div>
    </div>
    <div class="actions">
        <button type="button" id="printPassBtn">{{ __('front_desk.actions.print') }}</button>
    </div>
    <script>
        document.getElementById('printPassBtn').addEventListener('click', function () { window.print(); });
    </script>
</body>
</html>
