@extends('layouts.app')
@section('title', __('queue.queue_board'))

@push('styles')
<style>
    .queue-board .department-card {
        min-height: 200px;
    }
    .queue-number-display {
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
    }
    .queue-patient-name {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .priority-emergency { border-left: 4px solid #dc3545 !important; }
    .priority-urgent { border-left: 4px solid #ffc107 !important; }
    .priority-normal { border-left: 4px solid #198754 !important; }
    .serving-card {
        animation: pulse-border 2s infinite;
    }
    @keyframes pulse-border {
        0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(13, 110, 253, 0); }
    }
</style>
@endpush

@section('content')
@include('queue.partials.board-content')
@endsection

@push('scripts')
@unless(request()->boolean('embedded'))
<script>
(function () {
    var refreshMs = 30000;
    var boardSelector = '#queueBoardContent';

    if (window.UhmsQueueBoardRefreshTimer) {
        clearInterval(window.UhmsQueueBoardRefreshTimer);
    }

    async function refreshQueueBoard() {
        var board = document.querySelector(boardSelector);
        if (!board || document.hidden) {
            return;
        }

        try {
            var response = await fetch(board.dataset.refreshUrl || '{{ route('admin.queue.board', ['embedded' => 1]) }}', {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            var html = await response.text();
            var parsed = new DOMParser().parseFromString(html, 'text/html');
            var freshBoard = parsed.querySelector(boardSelector);

            if (!response.ok || !freshBoard) {
                throw new Error('Unable to refresh queue board.');
            }

            board.innerHTML = freshBoard.innerHTML;
            board.dataset.refreshUrl = freshBoard.dataset.refreshUrl || board.dataset.refreshUrl;
        } catch (error) {
            if (window.UhmsInertia && typeof window.UhmsInertia.toast === 'function') {
                window.UhmsInertia.toast('Unable to refresh queue board.', 'warning');
            }
        }
    }

    document.addEventListener('click', function (event) {
        var refreshButton = event.target.closest('[data-queue-board-refresh]');
        if (!refreshButton || !refreshButton.closest(boardSelector)) {
            return;
        }

        event.preventDefault();
        refreshQueueBoard();
    });

    window.UhmsQueueBoardRefreshTimer = setInterval(refreshQueueBoard, refreshMs);
})();
</script>
@endunless
@endpush
