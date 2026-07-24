<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('patients.hospital_card') }} — {{ $patient->patient_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 0; padding: 24px; background: #f4f4f4; }
        .wrapper { max-width: 400px; margin: 0 auto; }
        .card-id { border: 1px solid #d0d0d0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .card-id .header { background: #0d6efd; color: #fff; padding: 10px 14px; display: flex; align-items: center; gap: 10px; }
        .card-id .header img.logo { height: 34px; width: 34px; object-fit: contain; background: #fff; border-radius: 6px; padding: 2px; }
        .card-id .header .org-name { font-size: 14px; font-weight: 700; line-height: 1.2; }
        .card-id .header .org-meta { font-size: 10px; opacity: .9; line-height: 1.3; }
        .card-id .title { text-align: center; font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #0d6efd; padding: 6px 0 2px; text-transform: uppercase; }
        .card-id .body { display: flex; gap: 12px; padding: 8px 14px 12px; }
        .card-id .photo { width: 76px; height: 90px; border: 1px solid #ddd; border-radius: 6px; flex-shrink: 0; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f0f2f5; color: #888; font-size: 24px; font-weight: 700; }
        .card-id .photo img { width: 100%; height: 100%; object-fit: cover; }
        .card-id .details { flex: 1; font-size: 12px; }
        .card-id .details .name { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .card-id .details table { width: 100%; border-collapse: collapse; }
        .card-id .details td { padding: 1px 0; vertical-align: top; }
        .card-id .details td.k { color: #666; width: 42%; }
        .card-id .details td.v { font-weight: 600; }
        .card-id .footer { border-top: 1px dashed #ddd; padding: 8px 14px; font-size: 9.5px; color: #666; text-align: center; }
        .card-id .footer .issued { margin-top: 2px; }
        .actions { max-width: 400px; margin: 16px auto 0; text-align: center; }
        .actions a, .actions button { padding: 8px 18px; font-size: 14px; border: 1px solid #0d6efd; background: #0d6efd; color: #fff; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
        .actions .secondary { background: #fff; color: #0d6efd; margin-left: 8px; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .wrapper { max-width: none; }
            .card-id { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card-id">
            <div class="header">
                @if(!empty($organization['logo']))
                    <img src="{{ asset('storage/' . $organization['logo']) }}" alt="" class="logo">
                @endif
                <div>
                    <div class="org-name">{{ $organization['name'] ?? config('app.name', 'UHMS') }}</div>
                    <div class="org-meta">
                        {{ collect([$organization['address'] ?? null, $organization['city'] ?? null])->filter()->implode(', ') }}
                        @if(!empty($organization['phone']))
                            <br>{{ $organization['phone'] }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="title">{{ __('patients.hospital_card') }}</div>
            <div class="body">
                <div class="photo">
                    @if($patient->avatar)
                        <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}">
                    @else
                        {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                    @endif
                </div>
                <div class="details">
                    <div class="name">{{ $patient->full_name }}</div>
                    <table>
                        <tr><td class="k">{{ __('patients.hospital_no') }}</td><td class="v">{{ $patient->patient_number }}</td></tr>
                        <tr><td class="k">{{ __('patients.date_of_birth') }}</td><td class="v">{{ $patient->date_of_birth?->format('d M Y') }}</td></tr>
                        <tr><td class="k">{{ __('patients.gender') }}</td><td class="v">{{ $patient->gender?->translatedLabel() ?? '—' }}</td></tr>
                        <tr><td class="k">{{ __('patients.blood_group') }}</td><td class="v">{{ $patient->blood_group?->translatedLabel() ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="footer">
                {{ __('patients.card_instructions') }}
                <div class="issued">{{ __('patients.issued_on') }}: {{ now()->format('d M Y') }}</div>
            </div>
        </div>
    </div>
    <div class="actions">
        <button type="button" id="printCardBtn">{{ __('patients.print') }}</button>
        <a href="{{ route('admin.patients.show', $patient) }}" class="secondary" data-no-inertia>{{ __('patients.back_to_profile') }}</a>
    </div>
    <script>
        document.getElementById('printCardBtn').addEventListener('click', function () { window.print(); });
    </script>
</body>
</html>
