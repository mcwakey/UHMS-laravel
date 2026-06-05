<script>
(function () {
    const providerByTypeUrl = '{{ route("admin.insurance-providers.by-type") }}';
    const tiersForProviderUrl = '{{ route("admin.insurance-providers.tiers.for-patient", ":pid") }}';
    const typeSelect = document.getElementById('addInsType');
    const providerSelect = document.getElementById('addInsProvider');
    const tierSelect = document.getElementById('addInsTier');
    const tierInfo = document.getElementById('addInsTierInfo');

    if (!typeSelect || !providerSelect || !tierSelect) {
        return;
    }

    function setTierInfo(text) {
        if (tierInfo) {
            tierInfo.textContent = text || '';
        }
    }

    function resetProviderSelect(message = 'Select type first') {
        providerSelect.innerHTML = '<option value="">' + message + '</option>';
        providerSelect.disabled = true;
    }

    function resetTierSelect(message = 'Select provider first') {
        tierSelect.innerHTML = '<option value="">' + message + '</option>';
        tierSelect.disabled = true;
        setTierInfo('');
    }

    typeSelect.addEventListener('change', function() {
        const type = this.value;
        resetProviderSelect(type ? 'Loading providers...' : 'Select type first');
        resetTierSelect();

        if (!type) return;

        fetch(providerByTypeUrl + '?type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(providers => {
            if (!providers.length) {
                resetProviderSelect('No providers for selected type');
                return;
            }

            providerSelect.innerHTML = '<option value="">Select Provider</option>';
            providers.forEach(provider => {
                const opt = document.createElement('option');
                opt.value = provider.id;
                opt.textContent = provider.short_name ? provider.name + ' (' + provider.short_name + ')' : provider.name;
                providerSelect.appendChild(opt);
            });
            providerSelect.disabled = false;
        })
        .catch(() => {
            resetProviderSelect('Failed to load providers');
        });
    });

    providerSelect.addEventListener('change', function() {
        const providerId = this.value;

        if (!providerId) {
            resetTierSelect();
            return;
        }

        tierSelect.innerHTML = '<option value="">Loading...</option>';
        tierSelect.disabled = true;
        setTierInfo('');

        fetch(tiersForProviderUrl.replace(':pid', providerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(tiers => {
            if (!tiers.length) {
                tierSelect.innerHTML = '<option value="">No tiers available</option>';
                return;
            }

            tierSelect.innerHTML = '<option value="">Select Tier</option>';
            tiers.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.coverage_percentage ? ' (' + t.coverage_percentage + '% coverage)' : '');
                if (t.is_default) opt.selected = true;
                tierSelect.appendChild(opt);
            });
            tierSelect.disabled = false;
            updateTierInfo();
        })
        .catch(() => {
            tierSelect.innerHTML = '<option value="">Failed to load tiers</option>';
        });
    });

    tierSelect.addEventListener('change', updateTierInfo);

    function updateTierInfo() {
        const opt = tierSelect.options[tierSelect.selectedIndex];
        setTierInfo(opt && opt.value ? opt.textContent : 'If no tier is chosen, the provider\'s default tier will be used.');
    }

    document.querySelectorAll('input[name="member_type"]').forEach(r => {
        r.addEventListener('change', function() {
            const row = document.getElementById('addCardHolderRow');
            const sel = document.getElementById('addCardHolder');
            if (!row || !sel) return;

            if (this.value === 'beneficiary') {
                row.style.display = '';
                sel.required = true;
            } else {
                row.style.display = 'none';
                sel.required = false;
                sel.value = '';
            }
        });
    });
})();
</script>
