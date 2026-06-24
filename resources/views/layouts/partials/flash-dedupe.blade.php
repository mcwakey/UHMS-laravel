@php
    $flashAlerts = collect([
        ['className' => 'alert-success', 'message' => session('success')],
        ['className' => 'alert-danger', 'message' => session('error')],
    ])->filter(fn ($alert) => filled($alert['message']))->values();
@endphp

@if($flashAlerts->isNotEmpty())
<script>
(function(flashAlerts) {
    const root = document.currentScript.parentElement || document.body;
    const normalize = function(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    };

    flashAlerts.forEach(function(flashAlert) {
        const message = normalize(flashAlert.message);
        if (!message) return;

        const matchingAlerts = Array.from(root.querySelectorAll('.alert.' + flashAlert.className))
            .filter(function(alert) {
                return normalize(alert.textContent).includes(message);
            });

        matchingAlerts.slice(1).forEach(function(alert) {
            alert.remove();
        });
    });
})(@json($flashAlerts));
</script>
@endif
