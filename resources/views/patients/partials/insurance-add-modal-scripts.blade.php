<script>
(function () {
    const I18N = {
        selectTypeFirst: @json(__('patients.select_type_first')),
        selectProviderFirst: @json(__('patients.select_provider_first')),
        loadingProviders: @json(__('patients.loading_providers')),
        noProvidersForType: @json(__('patients.no_providers_for_type')),
        failedLoadProviders: @json(__('patients.failed_load_providers')),
        selectProvider: @json(__('patients.select_provider')),
        loadingTiers: @json(__('patients.loading_tiers')),
        noTiers: @json(__('patients.no_tiers')),
        selectTier: @json(__('patients.select_tier')),
        failedLoadTiers: @json(__('patients.failed_load_tiers')),
        defaultTierUsed: @json(__('patients.default_tier_used')),
    };
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

    function resetProviderSelect(message = I18N.selectTypeFirst) {
        providerSelect.innerHTML = '<option value="">' + message + '</option>';
        providerSelect.disabled = true;
    }

    function resetTierSelect(message = I18N.selectProviderFirst) {
        tierSelect.innerHTML = '<option value="">' + message + '</option>';
        tierSelect.disabled = true;
        setTierInfo('');
    }

    typeSelect.addEventListener('change', function() {
        const type = this.value;
        resetProviderSelect(type ? I18N.loadingProviders : I18N.selectTypeFirst);
        resetTierSelect();

        if (!type) return;

        fetch(providerByTypeUrl + '?type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(providers => {
            if (!providers.length) {
                resetProviderSelect(I18N.noProvidersForType);
                return;
            }

            providerSelect.innerHTML = '<option value="">' + I18N.selectProvider + '</option>';
            providers.forEach(provider => {
                const opt = document.createElement('option');
                opt.value = provider.id;
                opt.textContent = provider.short_name ? provider.name + ' (' + provider.short_name + ')' : provider.name;
                providerSelect.appendChild(opt);
            });
            providerSelect.disabled = false;
        })
        .catch(() => {
            resetProviderSelect(I18N.failedLoadProviders);
        });
    });

    providerSelect.addEventListener('change', function() {
        const providerId = this.value;

        if (!providerId) {
            resetTierSelect();
            return;
        }

        tierSelect.innerHTML = '<option value="">' + I18N.loadingTiers + '</option>';
        tierSelect.disabled = true;
        setTierInfo('');

        fetch(tiersForProviderUrl.replace(':pid', providerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(tiers => {
            if (!tiers.length) {
                tierSelect.innerHTML = '<option value="">' + I18N.noTiers + '</option>';
                return;
            }

            tierSelect.innerHTML = '<option value="">' + I18N.selectTier + '</option>';
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
            tierSelect.innerHTML = '<option value="">' + I18N.failedLoadTiers + '</option>';
        });
    });

    tierSelect.addEventListener('change', updateTierInfo);

    function updateTierInfo() {
        const opt = tierSelect.options[tierSelect.selectedIndex];
        setTierInfo(opt && opt.value ? opt.textContent : I18N.defaultTierUsed);
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
