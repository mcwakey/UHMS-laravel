@php
    $setupCountry = $countrySettings ?? config('patient_reference.countries.' . config('patient_reference.setup_country_code'), []);
    $setupCountryCode = strtoupper($setupCountry['iso2'] ?? config('patient_reference.setup_country_code', 'GH'));
    $locationMap = ($regions ?? collect())
        ->mapWithKeys(fn ($region) => [
            $region->name => $region->cities
                ->map(fn ($city) => [
                    'value' => $city->name,
                    'label' => $city->name,
                    'towns' => $city->towns->pluck('name')->values(),
                ])
                ->values(),
        ]);
@endphp

<script>
(function () {
    const locationMap = @json($locationMap);
    const setupCountryCode = @json($setupCountryCode);
    const cityUrl = @json(route('admin.locations.cities'));
    const townUrl = @json(route('admin.locations.towns'));
    const regionSelect = document.getElementById('patientRegionSelect');
    const citySelect = document.getElementById('patientCitySelect');
    const townSelect = document.getElementById('patientTownSelect');

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function refreshSelect2(select) {
        if (!hasSelect2() || !select) return;
        const $select = jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.prop('disabled', select.disabled).trigger('change.select2');
        }
    }

    function initSearchableSelects() {
        if (!hasSelect2()) return;

        document.querySelectorAll('.patient-searchable-select').forEach(function (select) {
            const $select = jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) return;

            $select.select2({
                placeholder: select.dataset.placeholder || '',
                allowClear: true,
                minimumResultsForSearch: 0,
                width: '100%',
            });
        });
    }

    function normalizeOption(row) {
        if (typeof row === 'string') {
            return { value: row, label: row, towns: [] };
        }

        return {
            value: row.value || row.label || '',
            label: row.label || row.value || '',
            towns: row.towns || [],
        };
    }

    function fillSelect(select, options, selected, placeholder) {
        if (!select) return;

        select.innerHTML = '';
        select.appendChild(new Option(placeholder || '', ''));

        options.map(normalizeOption).forEach(function (row) {
            const option = new Option(row.label, row.value, false, selected === row.value);
            select.appendChild(option);
        });

        select.disabled = options.length === 0;
        refreshSelect2(select);
    }

    function findSelectedCity() {
        const region = regionSelect ? regionSelect.value : '';
        const city = citySelect ? citySelect.value : '';
        const cities = locationMap[region] || [];

        return cities.map(normalizeOption).find(row => row.value === city);
    }

    function loadCityTowns(preserveSelected) {
        if (!regionSelect || !citySelect || !townSelect) return;

        const region = regionSelect.value;
        const city = citySelect.value;
        const selectedTown = preserveSelected ? (townSelect.dataset.selected || townSelect.value || '') : '';
        const selectedCity = findSelectedCity();

        if (!region || !city) {
            fillSelect(townSelect, [], '', townSelect.dataset.placeholder || 'Town');
            return;
        }

        if (selectedCity && selectedCity.towns.length) {
            fillSelect(townSelect, selectedCity.towns, selectedTown, townSelect.dataset.placeholder || 'Town');
            return;
        }

        fetch(townUrl + '?country=' + encodeURIComponent(setupCountryCode) + '&region=' + encodeURIComponent(region) + '&city=' + encodeURIComponent(city), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(response => response.json())
            .then(rows => rows.map(normalizeOption))
            .then(options => fillSelect(townSelect, options, selectedTown, townSelect.dataset.placeholder || 'Town'))
            .catch(() => fillSelect(townSelect, [], '', townSelect.dataset.placeholder || 'Town'));
    }

    function loadRegionPlaces(preserveSelected) {
        if (!regionSelect || !citySelect || !townSelect) return;

        const region = regionSelect.value;
        const selectedCity = preserveSelected ? (citySelect.dataset.selected || citySelect.value || '') : '';
        const localOptions = locationMap[region] || [];

        if (!region) {
            fillSelect(citySelect, [], '', citySelect.dataset.placeholder || 'City');
            fillSelect(townSelect, [], '', townSelect.dataset.placeholder || 'Town');
            return;
        }

        if (localOptions.length) {
            fillSelect(citySelect, localOptions, selectedCity, citySelect.dataset.placeholder || 'City');
            loadCityTowns(preserveSelected);
            return;
        }

        fetch(cityUrl + '?country=' + encodeURIComponent(setupCountryCode) + '&region=' + encodeURIComponent(region), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(response => response.json())
            .then(rows => rows.map(normalizeOption))
            .then(options => {
                fillSelect(citySelect, options, selectedCity, citySelect.dataset.placeholder || 'City');
                loadCityTowns(preserveSelected);
            })
            .catch(() => {
                fillSelect(citySelect, [], '', citySelect.dataset.placeholder || 'City');
                fillSelect(townSelect, [], '', townSelect.dataset.placeholder || 'Town');
            });
    }

    function normalizeIdCard(input) {
        let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

        if (setupCountryCode !== 'GH') {
            input.value = input.value.toUpperCase().replace(/[^A-Z0-9\-\/ ]/g, '').slice(0, 30);
            return;
        }

        value = value.replace(/^GHA/, '');
        value = value.replace(/^[A-Z]+/, '');
        const body = value.slice(0, 9);
        const check = value.replace(/\D/g, '').slice(9, 10);
        input.value = body || check ? 'GHA-' + body + (check ? '-' + check : '') : '';
    }

    function maskDigitalAddress(input) {
        if (setupCountryCode !== 'GH') return;

        const value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        const area = value.slice(0, 2).replace(/[^A-Z]/g, '');
        const first = value.slice(2, 6).replace(/\D/g, '');
        const second = value.slice(6, 10).replace(/\D/g, '');
        input.value = [area, first, second].filter(Boolean).join('-');
    }

    function maskPhone(input) {
        let value = input.value.replace(/[^\d+]/g, '');

        if (setupCountryCode === 'GH') {
            if (value.startsWith('+233')) {
                input.value = '+233' + value.slice(4).replace(/\D/g, '').slice(0, 9);
                return;
            }

            value = value.replace(/\D/g, '');
            input.value = value.slice(0, 10);
            return;
        }

        if (setupCountryCode === 'TG') {
            value = value.startsWith('+228')
                ? '+228' + value.slice(4).replace(/\D/g, '').slice(0, 8)
                : '+228' + value.replace(/\D/g, '').slice(0, 8);
            input.value = value;
            return;
        }

        input.value = value.slice(0, 20);
    }

    function normalizeEmail(input) {
        input.value = input.value.trim().toLowerCase().replace(/\s+/g, '');
    }

    document.addEventListener('input', function (event) {
        if (event.target.matches('.js-phone-mask')) {
            maskPhone(event.target);
        }

        if (event.target.matches('.js-id-card-input')) {
            normalizeIdCard(event.target);
        }

        if (event.target.matches('.js-digital-address-mask')) {
            maskDigitalAddress(event.target);
        }

        if (event.target.matches('.js-email-input')) {
            normalizeEmail(event.target);
        }
    });

    document.querySelectorAll('.js-id-card-input').forEach(function (input) {
        if (input.value) normalizeIdCard(input);
    });

    document.querySelectorAll('.js-digital-address-mask').forEach(function (input) {
        if (input.value) maskDigitalAddress(input);
    });

    if (regionSelect) {
        regionSelect.addEventListener('change', function () {
            if (citySelect) citySelect.dataset.selected = '';
            if (townSelect) townSelect.dataset.selected = '';
            loadRegionPlaces(false);
        });

        if (hasSelect2()) {
            jQuery(regionSelect).on('select2:select select2:clear', function () {
                window.setTimeout(function () {
                    if (citySelect) citySelect.dataset.selected = '';
                    if (townSelect) townSelect.dataset.selected = '';
                    loadRegionPlaces(false);
                }, 0);
            });
        }
    }

    if (citySelect) {
        citySelect.addEventListener('change', function () {
            if (townSelect) townSelect.dataset.selected = '';
            loadCityTowns(false);
        });

        if (hasSelect2()) {
            jQuery(citySelect).on('select2:select select2:clear', function () {
                window.setTimeout(function () {
                    if (townSelect) townSelect.dataset.selected = '';
                    loadCityTowns(false);
                }, 0);
            });
        }
    }

    const detectBtn = document.getElementById('detectDigitalAddressBtn');
    if (detectBtn) {
        detectBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert(@json(__('patients.device_location_unavailable')));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function () {
                    const provider = setupCountryCode === 'GH' ? 'GhanaPostGPS/address provider' : 'address provider';
                    alert(@json(__('patients.digital_address_api_required_prefix')) + ' ' + provider + ' ' + @json(__('patients.digital_address_api_required_suffix')));
                },
                function () {
                    alert(@json(__('patients.unable_to_read_device_location')));
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });
    }

    initSearchableSelects();
    loadRegionPlaces(true);
})();
</script>
