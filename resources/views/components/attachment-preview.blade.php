@props([
    'url',
    'name' => null,
    'label' => null,
    'icon' => null,
    'class' => 'btn btn-sm btn-outline-primary',
])

@php
    $resolvedName = $name ?: basename(parse_url($url, PHP_URL_PATH) ?: $url);
    $ext = strtolower(pathinfo($resolvedName, PATHINFO_EXTENSION));
    $kind = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
        ? 'image'
        : ($ext === 'pdf' ? 'pdf' : 'other');
    $resolvedIcon = $icon ?: ($kind === 'image' ? 'ti-photo' : ($kind === 'pdf' ? 'ti-file-type-pdf' : 'ti-file'));
    $resolvedLabel = $label ?: $resolvedName;
@endphp

<button type="button" {{ $attributes->merge(['class' => $class]) }}
    data-attachment-preview
    data-attachment-url="{{ $url }}"
    data-attachment-name="{{ $resolvedName }}"
    data-attachment-kind="{{ $kind }}">
    <i class="ti {{ $resolvedIcon }} me-1"></i>{{ $resolvedLabel }}
</button>

@once
    @push('styles')
    <style>
        #attachmentPreviewModal .attachment-stage {
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            max-height: 82vh;
            overflow: auto;
            border-radius: 6px;
        }
        #attachmentPreviewModal .attachment-stage img {
            max-width: 100%;
            max-height: 82vh;
            object-fit: contain;
        }
        #attachmentPreviewModal .attachment-stage iframe {
            width: 100%;
            height: 80vh;
            border: 0;
            background: #fff;
        }
        #attachmentPreviewModal .attachment-fallback {
            color: #e5e7eb;
            text-align: center;
            padding: 3rem 1rem;
        }
    </style>
    @endpush

    @push('scripts')
    <div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-truncate" id="attachmentPreviewTitle"><i class="ti ti-paperclip me-1"></i></h5>
                    <div class="d-flex align-items-center gap-2">
                        <a href="#" id="attachmentPreviewOpen" target="_blank" rel="noopener" data-no-inertia class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-external-link me-1"></i>{{ __('common.open_in_new_tab') }}
                        </a>
                        <a href="#" id="attachmentPreviewDownload" download data-no-inertia class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-download me-1"></i>{{ __('common.download') }}
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>
                </div>
                <div class="modal-body p-2">
                    <div class="attachment-stage" id="attachmentPreviewStage"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function () {
        var modalEl = document.getElementById('attachmentPreviewModal');
        if (!modalEl || modalEl.dataset.bound === '1') return;
        modalEl.dataset.bound = '1';

        var stage = document.getElementById('attachmentPreviewStage');
        var titleEl = document.getElementById('attachmentPreviewTitle');
        var openLink = document.getElementById('attachmentPreviewOpen');
        var downloadLink = document.getElementById('attachmentPreviewDownload');
        var notAvailable = @json(__('common.preview_not_available'));

        function render(url, name, kind) {
            stage.innerHTML = '';
            titleEl.textContent = name || '';
            openLink.setAttribute('href', url);
            downloadLink.setAttribute('href', url);

            if (kind === 'image') {
                var img = document.createElement('img');
                img.src = url;
                img.alt = name || '';
                stage.appendChild(img);
            } else if (kind === 'pdf') {
                var frame = document.createElement('iframe');
                frame.setAttribute('src', url);
                frame.setAttribute('title', name || 'PDF');
                stage.appendChild(frame);
            } else {
                var msg = document.createElement('div');
                msg.className = 'attachment-fallback';
                var icon = document.createElement('i');
                icon.className = 'ti ti-file-off d-block mb-2';
                icon.style.fontSize = '2.5rem';
                var text = document.createElement('div');
                text.textContent = notAvailable;
                msg.appendChild(icon);
                msg.appendChild(text);
                stage.appendChild(msg);
            }
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-attachment-preview]');
            if (!trigger) return;
            e.preventDefault();
            render(
                trigger.getAttribute('data-attachment-url'),
                trigger.getAttribute('data-attachment-name'),
                trigger.getAttribute('data-attachment-kind')
            );
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            stage.innerHTML = '';
        });
    })();
    </script>
    @endpush
@endonce
