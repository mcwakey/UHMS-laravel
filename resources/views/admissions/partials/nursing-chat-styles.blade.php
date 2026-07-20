@once
@push('styles')
<style>
    .nursing-thread { display:flex; flex-direction:column; gap:.75rem; }
    .nursing-message { display:flex; gap:.65rem; align-items:flex-start; max-width:84%; }
    .nursing-message--mine { align-self:flex-end; flex-direction:row-reverse; }
    .nursing-message__avatar { width:34px; height:34px; border-radius:50%; flex:0 0 34px; display:flex;
        align-items:center; justify-content:center; font-weight:700; font-size:.72rem;
        color:#fff; background:linear-gradient(135deg,#64748b,#94a3b8); }
    .nursing-message--mine .nursing-message__avatar { background:linear-gradient(135deg,#2E37A4,#4C56C5); }
    .nursing-message__bubble { border:1px solid var(--bs-border-color); border-radius:12px; border-top-left-radius:4px;
        padding:.65rem .8rem; background:#fff; min-width:220px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .nursing-message--mine .nursing-message__bubble { border-color:rgba(var(--bs-primary-rgb),.22);
        border-top-left-radius:12px; border-top-right-radius:4px; background:rgba(var(--bs-primary-rgb),.06); }
    .nursing-message__meta { display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        color:var(--bs-secondary-color); font-size:.72rem; margin-bottom:.35rem; }
    .nursing-message__body { color:var(--bs-body-color); white-space:pre-wrap; overflow-wrap:anywhere; }
    .nursing-message__actions { margin-top:.55rem; display:flex; justify-content:flex-end; gap:.35rem; }
    @media (max-width: 767px) {
        .nursing-message { max-width:100%; }
        .nursing-message__bubble { min-width:0; flex:1; }
    }
</style>
@endpush
@endonce
