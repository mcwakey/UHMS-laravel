@php
    $acronyms = ['ui', 'seo', 'api', 'faq', 'gdpr', 'ip', 'rtl', 'hrm']; // Acronyms
    $formTypes = ['basic', 'cover', 'illustration'];
    $specialPrefixes = ['icon', 'chart', 'maps', 'form'];
    $pluralMap = [
        'icon' => 'Icons',
        'chart' => 'Charts',
        'maps' => 'Maps',
        'form' => 'Forms',
    ];

    $segments = explode('-', Route::currentRouteName());

    // Remove 'ui' prefix if it's the first segment
    if ($segments[0] === 'ui') {
        array_shift($segments);
    }

    // Move special prefix to suffix if present
    $prefix = null;
    if (in_array($segments[0], $specialPrefixes)) {
        $prefix = $pluralMap[$segments[0]];
        array_shift($segments);
    }

    // Check for form type in last segment
    $lastSegment = end($segments);
    $hasFormType = in_array($lastSegment, $formTypes);

    // Build title
    $title = collect($segments)
        ->map(function ($word) use ($acronyms) {
            return in_array(strtolower($word), $acronyms)
                ? strtoupper($word)
                : ucfirst($word);
        })
        ->toArray();

    // Add 'Form' if last word is form type
    if ($hasFormType) {
        $title[] = 'Form';
    }

    // Add pluralized prefix if applicable
    if ($prefix) {
        $title[] = $prefix;
    }

    $title = implode(' ', $title);
@endphp

<head>   
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    @if (Route::is(['index']))
    <title> Admin Dashboard - Medical & Hospital - Bootstrap 5 Admin Template</title>
    @endif
    @if (!Route::is(['index']))
    <title>{{ $title }} | Preclinic - Medical & Hospital - Bootstrap 5 Admin Template</title>
    @endif
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Dreams Technologies">

    @include('layout.partials.head-css')

</head>