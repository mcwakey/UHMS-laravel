<script>
(function () {
    const form = document.getElementById('editInsuranceForm');
    const modalEl = document.getElementById('editInsuranceModal');

    if (!form || !modalEl) {
        return;
    }

    function field(id) {
        return document.getElementById(id);
    }

    function setValue(id, value) {
        const el = field(id);
        if (el) {
            el.value = value || '';
        }
    }

    function setText(id, value) {
        const el = field(id);
        if (el) {
            el.textContent = value || '-';
        }
    }

    function setChecked(id, checked) {
        const el = field(id);
        if (el) {
            el.checked = !!checked;
        }
    }

    function truthy(value) {
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    function valueFrom(data, keys, fallback = '') {
        for (const key of keys) {
            if (data[key] !== undefined && data[key] !== null) {
                return data[key];
            }
        }

        return fallback;
    }

    function resolveAction(data, insuranceId, patientId) {
        const explicitAction = valueFrom(data, ['action', 'formAction']);
        if (explicitAction) {
            return explicitAction;
        }

        const template = form.dataset.actionTemplate || '';
        if (template && template !== '#') {
            return template.replace('__INSURANCE__', insuranceId);
        }

        if (patientId && insuranceId) {
            return '{{ url("/admin/patients") }}/' + patientId + '/insurances/' + insuranceId;
        }

        return '#';
    }

    function clearFeedback() {
        const feedback = field('editInsuranceFormFeedback');
        if (feedback) {
            feedback.className = 'alert d-none';
            feedback.innerHTML = '';
        }

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());
    }

    window.populatePatientInsuranceEditModal = function (payload) {
        const data = payload?.dataset ? payload.dataset : (payload || {});
        const insuranceId = valueFrom(data, ['id', 'insuranceId', 'insId']);
        const patientId = valueFrom(data, ['patientId', 'patient']);
        const memberType = valueFrom(data, ['memberType', 'member_type'], 'holder');

        clearFeedback();

        form.action = resolveAction(data, insuranceId, patientId);
        form.dataset.insuranceId = insuranceId || '';
        form.dataset.patientId = patientId || '';
        setValue('editInsProviderId', valueFrom(data, ['provider', 'providerId', 'insurance_provider_id']));
        setText('editInsProviderName', valueFrom(data, ['providerName', 'provider_name', 'insuranceProviderName'], '-'));
        setValue('editInsMembership', valueFrom(data, ['membership', 'membershipNumber', 'membership_number']));
        setValue('editInsPolicy', valueFrom(data, ['policy', 'policyNumber', 'policy_number']));
        setValue('editInsCccCode', valueFrom(data, ['cccCode', 'ccc_code']));
        setValue('editInsExpiry', valueFrom(data, ['expiry', 'expiryDate', 'expiry_date']));
        setChecked('editInsActive', truthy(valueFrom(data, ['active', 'isActive', 'is_active'], true)));
        setValue('editInsTierId', valueFrom(data, ['tier', 'tierId', 'insurance_tier_id']));
        setText('editInsTierDisplay', valueFrom(data, ['tierName', 'tier_name'], '-'));
        setChecked('editMemberHolder', memberType === 'holder');
        setChecked('editMemberBeneficiary', memberType === 'beneficiary');

        return { form, modalEl, insuranceId, patientId };
    };

    window.openPatientInsuranceEditModal = function (payload) {
        const state = window.populatePatientInsuranceEditModal(payload);
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        return state;
    };

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.edit-insurance-btn');
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        window.openPatientInsuranceEditModal(button);
    }, true);
})();
</script>
