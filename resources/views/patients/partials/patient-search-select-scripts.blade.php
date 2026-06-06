{{--
    Scripts for the reusable patient-search-select partial. Mirrors the Visit
    Create select2 behaviour exactly (same endpoint, same result rendering).

    Param: $id — must match the partial's $id.

    Optional global hooks (defined by the host page):
      window['{id}OnSelect'](patient)  — called when a patient is chosen
      window['{id}OnClear']()          — called when the selection is cleared
--}}
@php $id = $id ?? 'patientSearch'; @endphp
<script>
(function () {
    function init() {
        var wrapper = document.querySelector('[data-patient-search="{{ $id }}"]');
        if (!wrapper) return;
        var select = document.getElementById('{{ $id }}');
        var hidden = document.getElementById('{{ $id }}Value');
        if (!select || !hidden) return;
        if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) return;

        var url = wrapper.getAttribute('data-search-url');

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function displayText(p) {
            return (p.patient_number ? p.patient_number + ' - ' : '') + (p.full_name || p.text || '');
        }

        jQuery(select).select2({
            placeholder: select.dataset.placeholder || 'Search patient…',
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: url,
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    return {
                        results: (data || []).map(function (p) {
                            p.id = String(p.id);
                            p.text = displayText(p);
                            return p;
                        }),
                    };
                },
                cache: true,
            },
            templateResult: function (p) {
                if (p.loading) return p.text;
                var meta = [p.patient_number || '', p.phone || 'No phone'].filter(Boolean);
                if (p.last_visit_date) meta.push('Last visit: ' + p.last_visit_date);
                return jQuery('<span>').html(
                    '<span class="fw-medium">' + escapeHtml(p.full_name || p.text || '') + '</span>' +
                    '<small class="text-muted d-block">' + meta.map(escapeHtml).join(' &bull; ') + '</small>'
                );
            },
            templateSelection: function (p) { return displayText(p); },
        }).on('select2:select', function (e) {
            hidden.value = e.params.data.id || '';
            if (typeof window['{{ $id }}OnSelect'] === 'function') window['{{ $id }}OnSelect'](e.params.data);
        }).on('select2:clear', function () {
            hidden.value = '';
            if (typeof window['{{ $id }}OnClear'] === 'function') window['{{ $id }}OnClear']();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
