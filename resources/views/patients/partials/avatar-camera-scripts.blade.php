<script>
(function () {
    const fileInput = document.getElementById('patientAvatarInput');
    const preview = document.getElementById('avatar-preview');
    const startBtn = document.getElementById('startPatientCameraBtn');
    const panel = document.getElementById('patientCameraPanel');
    const video = document.getElementById('patientCameraVideo');
    const canvas = document.getElementById('patientCameraCanvas');
    const captureBtn = document.getElementById('capturePatientCameraBtn');
    const stopBtn = document.getElementById('stopPatientCameraBtn');
    const errorBox = document.getElementById('patientCameraError');
    let stream = null;

    if (!fileInput || !preview || !startBtn || !panel || !video || !canvas || !captureBtn || !stopBtn) {
        return;
    }

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message || '';
        errorBox.classList.toggle('d-none', !message);
    }

    function setPreview(src) {
        preview.innerHTML = '';
        const img = document.createElement('img');
        img.src = src;
        img.alt = @json(__('patients.profile_image'));
        img.className = 'rounded-circle';
        preview.appendChild(img);
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        video.srcObject = null;
        panel.classList.add('d-none');
    }

    async function startCamera() {
        showError('');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError(@json(__('patients.webcam_not_supported')));
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            video.srcObject = stream;
            panel.classList.remove('d-none');
            await video.play();
        } catch (error) {
            showError(@json(__('patients.webcam_unavailable')));
        }
    }

    function assignCapturedFile(blob) {
        const file = new File([blob], 'patient-webcam-photo.jpg', { type: 'image/jpeg' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
    }

    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);

    captureBtn.addEventListener('click', function () {
        if (!stream || !video.videoWidth || !video.videoHeight) {
            showError(@json(__('patients.webcam_unavailable')));
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function (blob) {
            if (!blob) {
                showError(@json(__('patients.webcam_capture_failed')));
                return;
            }

            assignCapturedFile(blob);
            setPreview(URL.createObjectURL(blob));
            stopCamera();
        }, 'image/jpeg', 0.9);
    });

    fileInput.addEventListener('change', function () {
        const file = fileInput.files && fileInput.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        setPreview(URL.createObjectURL(file));
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stopCamera();
    });
})();
</script>
