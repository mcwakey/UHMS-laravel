<div id="patientCameraPanel" class="border rounded p-2 mt-2 d-none">
    <div class="ratio ratio-4x3 bg-dark rounded overflow-hidden">
        <video id="patientCameraVideo" autoplay playsinline muted></video>
        <canvas id="patientCameraCanvas" class="d-none"></canvas>
    </div>
    <div class="d-flex gap-2 flex-wrap mt-2">
        <button type="button" class="btn btn-sm btn-primary" id="capturePatientCameraBtn">
            <i class="ti ti-camera-check me-1"></i>{{ __('patients.capture_photo') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="stopPatientCameraBtn">
            <i class="ti ti-video-off me-1"></i>{{ __('patients.stop_webcam') }}
        </button>
    </div>
    <div class="small text-danger mt-2 d-none" id="patientCameraError"></div>
</div>
