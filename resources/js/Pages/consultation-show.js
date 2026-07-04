const state = {
    config: {},
    abortController: null,
    freeItemIndex: 0,
    prescriptionIndex: 1,
    complaintSuggestionIndex: {},
    complaintSuggestions: [],
    complaintSuggestionActive: -1,
    timers: {},
};

function readConfig() {
    const node = document.getElementById('consultation-page-config');
    if (!node || !node.textContent) {
        return {};
    }

    try {
        return JSON.parse(node.textContent);
    } catch (error) {
        console.error('Invalid consultation page config', error);
        return {};
    }
}

function t(key, fallback) {
    const parts = String(key).split('.');
    let value = state.config.translations || {};

    for (const part of parts) {
        value = value && value[part];
    }

    return typeof value === 'string' ? value : fallback;
}

function csrfToken() {
    return state.config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function signalOptions(options = {}) {
    return Object.assign({}, options, { signal: state.abortController.signal });
}

function on(target, event, handler, options = {}) {
    if (!target) {
        return;
    }

    target.addEventListener(event, handler, signalOptions(options));
}

function ready(callback) {
    if (document.readyState === 'loading') {
        on(document, 'DOMContentLoaded', callback);
        return;
    }

    callback();
}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(value)));
    return div.innerHTML;
}

function capFirst(value) {
    return value ? String(value).charAt(0).toUpperCase() + String(value).slice(1) : '';
}

function enhanceSelect(select, options = {}) {
    if (!select || !window.jQuery?.fn?.select2) {
        return;
    }

    const $select = window.jQuery(select);
    const selected = Array.from(select.selectedOptions || []).map((option) => option.value);
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }

    if (select.multiple) {
        selected.forEach((value) => {
            const option = Array.from(select.options).find((candidate) => candidate.value === value);
            if (option) {
                option.selected = true;
            }
        });
    } else if (selected.length) {
        select.value = selected[0];
    }

    const modal = select.closest('.modal');
    const parent = modal ? window.jQuery(modal) : window.jQuery(select).closest('.card-body, form');
    const settings = Object.assign({
        theme: 'default',
        width: '100%',
        allowClear: true,
        minimumResultsForSearch: 0,
    }, options);
    if (parent.length) {
        settings.dropdownParent = parent;
    }
    $select.select2(settings);
}

function toast(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
    alert.style.cssText = 'z-index:9999;max-width:320px;font-size:.84rem;';
    alert.textContent = message;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 3200);
}

function setBusy(button, busy, text) {
    if (!button) {
        return;
    }

    if (busy) {
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${escapeHtml(text || t('ajax.submit_in_progress', 'Saving...'))}`;
        return;
    }

    button.disabled = false;
    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
    }
}

function normalizeErrors(error, fallback) {
    if (!error) {
        return fallback;
    }

    if (error.status === 419) {
        return t('ajax.session_expired', 'Your session expired. Please refresh and try again.');
    }

    if (error.status === 403) {
        return t('ajax.forbidden', 'You are not allowed to perform this action.');
    }

    if (error.errors) {
        return Object.values(error.errors).flat().join('\n');
    }

    if (error.message) {
        return error.message;
    }

    return fallback;
}

function renderFormError(form, message) {
    const boxSelector = form.dataset.errorTarget;
    let box = boxSelector ? document.querySelector(boxSelector) : null;

    if (!box) {
        box = form.querySelector('[data-form-errors], .js-form-errors, .alert-danger');
    }

    if (box) {
        box.classList.remove('d-none');
        box.innerHTML = `<i class="ti ti-alert-circle me-1"></i>${escapeHtml(message).replace(/\n/g, '<br>')}`;
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
    }

    toast(message, 'danger');
}

function clearFormError(form) {
    const boxSelector = form.dataset.errorTarget;
    const boxes = [];
    if (boxSelector) {
        const box = document.querySelector(boxSelector);
        if (box) {
            boxes.push(box);
        }
    }

    form.querySelectorAll('[data-form-errors], .js-form-errors').forEach((box) => boxes.push(box));
    boxes.forEach((box) => {
        box.classList.add('d-none');
        box.innerHTML = '';
    });
}

function renderPrescriptionSafetyOverride(form, error) {
    if (form.id !== 'prescriptionForm' || !error?.requires_override) {
        return false;
    }

    const panel = form.querySelector('[data-prescription-safety-panel]');
    const list = form.querySelector('[data-prescription-safety-warnings]');
    const codes = form.querySelector('[data-prescription-safety-codes]');
    const reason = form.querySelector('[name="safety_override_reason"]');
    const warnings = Array.isArray(error.warnings) ? error.warnings : [];

    if (!panel || !list || !codes) {
        return false;
    }

    list.innerHTML = warnings
        .map((warning) => `<li>${escapeHtml(warning.message || warning.code || t('safety.prescription_warning', 'Prescription safety warning'))}</li>`)
        .join('');

    codes.innerHTML = warnings
        .map((warning) => warning.code ? `<input type="hidden" name="safety_override_codes[]" value="${escapeHtml(warning.code)}">` : '')
        .join('');

    panel.classList.remove('d-none');
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    reason?.focus();

    return true;
}

function clearPrescriptionSafetyOverride(form) {
    const panel = form.querySelector('[data-prescription-safety-panel]');
    if (!panel) {
        return;
    }

    panel.classList.add('d-none');
    panel.querySelector('[data-prescription-safety-warnings]')?.replaceChildren();
    panel.querySelector('[data-prescription-safety-codes]')?.replaceChildren();
    const reason = panel.querySelector('[name="safety_override_reason"]');
    if (reason) {
        reason.value = '';
    }
}

const routeContext = {
    current() {
        return state.config.currentRouteId || null;
    },

    set(routeId) {
        state.config.currentRouteId = routeId || null;
        document.querySelectorAll('input[name="consultation_route_id"]').forEach((input) => {
            input.value = state.config.currentRouteId || '';
        });
    },

    ensure(form) {
        if (!form) {
            return true;
        }

        let input = form.querySelector('input[name="consultation_route_id"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'consultation_route_id';
            form.appendChild(input);
        }

        input.value = routeContext.current() || input.value || '';

        if (form.dataset.routeContextRequired === 'true' && !input.value) {
            renderFormError(form, t('ajax.route_context_missing', 'Select a consultation session before saving.'));
            return false;
        }

        return true;
    },

    syncAll(root = document) {
        root.querySelectorAll('form[action*="/consultations"], form[data-ajax-form], form[data-consultation-form]').forEach((form) => {
            routeContext.ensure(form);
        });
    },
};

const idempotency = {
    refresh(form) {
        const field = form?.querySelector('input[name="_idempotency_key"][data-idempotency-action]');
        if (!field) {
            return;
        }

        const action = field.dataset.idempotencyAction || 'consultation.action';
        const uuid = window.crypto && typeof window.crypto.randomUUID === 'function'
            ? window.crypto.randomUUID()
            : `${Date.now()}-${Math.random().toString(16).slice(2)}`;

        field.value = `${action}-${uuid}`;
        field.defaultValue = field.value;
    },
};

const tabs = {
    activate(selector) {
        if (!selector) {
            return false;
        }

        try {
            localStorage.setItem(state.config.tabStorageKey, selector);
        } catch (error) {}

        const link = document.querySelector(`#consultationTabs .nav-link[href="${selector}"]`);
        if (!link || !window.bootstrap?.Tab) {
            return false;
        }

        window.bootstrap.Tab.getOrCreateInstance(link).show();
        if (window.history?.replaceState) {
            window.history.replaceState(null, '', selector);
        }

        return true;
    },

    preserve(tabId) {
        return tabs.activate(tabId.startsWith('#') ? tabId : `#${tabId}`);
    },

    init() {
        const saved = window.location.hash || localStorage.getItem(state.config.tabStorageKey);
        if (saved) {
            tabs.activate(saved);
        }

        const params = new URLSearchParams(window.location.search || '');
        if (params.get('newPrescription') === '1') {
            tabs.activate('#prescriptions-section');
            const rxForm = document.getElementById('addPrescriptionForm');
            if (rxForm && window.bootstrap?.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(rxForm, { toggle: false }).show();
            }
        }

        document.querySelectorAll('#consultationTabs .nav-link').forEach((link) => {
            link.addEventListener('shown.bs.tab', (event) => {
                const selector = event.target.getAttribute('href');
                if (!selector) {
                    return;
                }

                localStorage.setItem(state.config.tabStorageKey, selector);
                if (window.history?.replaceState) {
                    window.history.replaceState(null, '', selector);
                }
            }, signalOptions());
        });
    },

    bindSubmitPreservation() {
        on(document, 'submit', (event) => {
            const form = event.target;
            if (form?.dataset?.preserveTab) {
                tabs.preserve(form.dataset.preserveTab);
                return;
            }

            const active = document.querySelector('#consultationTabs .nav-link.active');
            const selector = active?.getAttribute('href');
            if (selector) {
                localStorage.setItem(state.config.tabStorageKey, selector);
            }
        }, { capture: true });
    },
};

function currentPageUrl() {
    const url = new URL(window.location.href);
    if (routeContext.current()) {
        url.searchParams.set('consultation_route_id', routeContext.current());
    }
    url.searchParams.set('_refresh', Date.now());
    return url.toString();
}

function parseRefreshDocument(html) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const inertiaPage = doc.querySelector('script[data-page="app"][type="application/json"]');

    if (inertiaPage?.textContent) {
        try {
            const payload = JSON.parse(inertiaPage.textContent);
            if (payload?.props?.html) {
                return new DOMParser().parseFromString(payload.props.html, 'text/html');
            }
        } catch (error) {}
    }

    return doc;
}

const sectionRefresh = {
    syncControls(doc) {
        document.querySelectorAll('[data-consultation-refresh-control][id]').forEach((control) => {
            const fresh = doc.getElementById(control.id);
            if (!fresh || document.activeElement === control) {
                return;
            }

            const selected = control.value;
            if (window.jQuery?.fn?.select2 && window.jQuery(control).hasClass('select2-hidden-accessible')) {
                window.jQuery(control).select2('destroy');
            }

            control.innerHTML = fresh.innerHTML;
            if (selected && Array.from(control.options || []).some((option) => option.value === selected)) {
                control.value = selected;
            }

            delete control.dataset.hopcHydrationBound;
        });
    },

    refresh(section) {
        const target = document.getElementById(`${section}-list`);
        if (!target) {
            return Promise.resolve();
        }

        const scrollY = window.scrollY;

        return fetch(currentPageUrl(), {
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
        })
            .then((response) => response.text())
            .then((html) => {
                const doc = parseRefreshDocument(html);
                const fresh = doc.getElementById(`${section}-list`);
                if (fresh) {
                    target.innerHTML = fresh.innerHTML;
                }

                const badge = document.getElementById(`badge-${section}`);
                const freshBadge = doc.getElementById(`badge-${section}`);
                if (badge && freshBadge) {
                    badge.textContent = freshBadge.textContent;
                }

                sectionRefresh.syncControls(doc);
                rehydrate(target);
                window.scrollTo({ top: scrollY, behavior: 'auto' });
            })
            .catch(() => {
                toast(t('ajax.section_refresh_failed', 'Could not refresh this section.'), 'danger');
            });
    },

    summary() {
        const target = document.getElementById('consultation-summary-body');
        const urlTemplate = state.config.routes?.summaryFragment;
        if (!target || !urlTemplate) {
            return Promise.resolve();
        }

        const url = new URL(urlTemplate, window.location.origin);
        if (routeContext.current()) {
            url.searchParams.set('consultation_route_id', routeContext.current());
        }
        url.searchParams.set('_refresh', Date.now());

        return fetch(url.toString(), {
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
        })
            .then((response) => response.text())
            .then((html) => {
                target.innerHTML = html;
                rehydrate(target);
            });
    },
};

function rootOrDocument(root) {
    return root || document;
}

const ajaxForms = {
    init(root = document) {
        root.querySelectorAll('form[data-ajax-form], form[data-consultation-form][data-modal-form="true"]').forEach((form) => {
            form.dataset.consultationAjax = 'true';
        });
    },

    submit(form) {
        const section = form.dataset.refreshSection || form.dataset.ajaxForm || form.dataset.consultationForm;
        if (!routeContext.ensure(form)) {
            return;
        }

        if (form.dataset.prepare === 'prescription') {
            prescriptions.prepareSubmit();
        }

        clearFormError(form);
        if (section) {
            tabs.preserve(`${section}-section`);
        }

        const button = form.querySelector('[type="submit"]');
        setBusy(button, true);

        fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().catch(() => ({})).then((payload) => {
                        payload.status = response.status;
                        throw payload;
                    });
                }

                return response.json();
            })
            .then((data) => {
                if (data.success === false) {
                    throw data;
                }

                ajaxForms.handleSuccess(form, section, data);
            })
            .catch((error) => {
                if (renderPrescriptionSafetyOverride(form, error)) {
                    renderFormError(form, normalizeErrors(error, t('ajax.server_error', 'The action could not be completed.')));
                    return;
                }

                renderFormError(form, normalizeErrors(error, t('ajax.server_error', 'The action could not be completed.')));
            })
            .finally(() => setBusy(button, false));
    },

    handleSuccess(form, section, data) {
        idempotency.refresh(form);
        modalHelper.closeContaining(form);
        ajaxForms.reset(form, section);
        toast(data.message || t('ajax.saved_successfully', 'Saved successfully.'));

        const refreshes = [];
        if (section) {
            refreshes.push(sectionRefresh.refresh(section));
        }
        refreshes.push(sectionRefresh.summary());

        Promise.all(refreshes).then(() => {
            if (section) {
                tabs.activate(`#${section}-section`);
            }
        });
    },

    reset(form, section) {
        if (form.dataset.preserveValues !== 'true') {
            form.reset();
        }
        routeContext.ensure(form);

        const collapse = form.closest('.collapse');
        if (collapse && window.bootstrap?.Collapse) {
            window.bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).hide();
        }

        if (section === 'investigations') {
            selectLoader.resetInvestigation();
        }

        if (form.id === 'labRequestForm') {
            document.getElementById('labReqItemsContainer')?.classList.add('d-none');
            const body = document.getElementById('labReqItemsBody');
            if (body) {
                body.innerHTML = '';
            }
        }

        if (form.id === 'prescriptionForm') {
            clearPrescriptionSafetyOverride(form);
        }
    },
};

const modalHelper = {
    init() {
        document.querySelectorAll('.modal').forEach((modal) => {
            modal.addEventListener('shown.bs.modal', () => {
                modal.querySelectorAll('form').forEach((form) => {
                    clearFormError(form);
                    routeContext.ensure(form);
                });
                if (modal.id === 'sendSessionModal') {
                    sendSession.initPicker();
                }
            }, signalOptions());

            modal.addEventListener('hidden.bs.modal', () => {
                modal.querySelectorAll('[data-form-errors], .js-form-errors').forEach((box) => {
                    box.classList.add('d-none');
                    box.innerHTML = '';
                });
            }, signalOptions());
        });
    },

    closeContaining(element) {
        const modal = element.closest('.modal');
        if (modal && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getInstance(modal)?.hide();
        }
    },
};

const selectLoader = {
    loadInvestigation(departmentId) {
        const select = document.getElementById('investigationServicesSelect');
        const help = document.getElementById('investigationServicesHelp');
        if (!select) {
            return;
        }

        const reset = (message) => {
            if (window.jQuery?.fn?.select2 && window.jQuery(select).hasClass('select2-hidden-accessible')) {
                window.jQuery(select).select2('destroy');
            }
            select.innerHTML = '';
            select.disabled = true;
            if (help) {
                help.textContent = message;
                help.classList.remove('text-danger');
                help.classList.add('text-muted');
            }
        };

        if (!departmentId) {
            reset(t('selectDepartmentFirst', 'Select a department first to load services'));
            return;
        }

        reset(t('loading', 'Loading...'));

        const url = `${state.config.routes.deptServicesBase}/${encodeURIComponent(departmentId)}/investigation-services?visit_id=${encodeURIComponent(state.config.visitId)}`;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((response) => response.json())
            .then((services) => {
                if (!services.length) {
                    reset('No active services found.');
                    return;
                }

                select.innerHTML = '';
                services.forEach((service) => {
                    const option = document.createElement('option');
                    const price = service.price !== null && service.price !== undefined ? ` - GHS ${Number(service.price).toFixed(2)}` : '';
                    const fallback = service.pricing_source === 'fallback_cash_no_insurance_price' ? ' (cash fallback)' : '';
                    option.value = service.id;
                    option.textContent = `${service.code ? `${service.code} - ` : ''}${service.name}${price}${fallback}`;
                    select.appendChild(option);
                });
                select.disabled = false;
                if (help) {
                    help.textContent = t('searchServices', 'Search and select services');
                    help.classList.remove('text-danger');
                    help.classList.add('text-muted');
                }
                enhanceSelect(select, {
                    placeholder: select.dataset.placeholder || t('searchServices', 'Search and select services'),
                    closeOnSelect: false,
                    language: { noResults: () => t('noResultsFound', 'No results found') },
                });
            })
            .catch(() => {
                reset('Failed to load services.');
                if (help) {
                    help.classList.remove('text-muted');
                    help.classList.add('text-danger');
                }
            });
    },

    loadProcedure(departmentId) {
        const select = document.getElementById('procedureServiceSelect');
        if (!select) {
            return;
        }

        if (!departmentId) {
            select.innerHTML = `<option value="">${escapeHtml(t('selectDepartmentFirst', 'Select a department first'))}</option>`;
            select.disabled = true;
            enhanceSelect(select, { placeholder: t('searchProcedureService', 'Search procedure service') });
            return;
        }

        select.innerHTML = `<option value="">${escapeHtml(t('loading', 'Loading...'))}</option>`;
        select.disabled = true;

        const url = `${state.config.routes.procedureDeptServicesBase}/${encodeURIComponent(departmentId)}/services?visit_id=${encodeURIComponent(state.config.visitId)}`;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((items) => {
                if (!items || items.length === 0) {
                    select.innerHTML = `<option value="">${escapeHtml(t('noProcedureServices', 'No procedure services found'))}</option>`;
                    select.disabled = true;
                    enhanceSelect(select, { placeholder: t('searchProcedureService', 'Search procedure service') });
                    return;
                }

                select.innerHTML = '<option value="">-- Select service --</option>';
                items.forEach((item) => {
                    const option = document.createElement('option');
                    const price = item.price ?? item.selling_price;
                    const fallback = item.pricing_source === 'fallback_cash_no_insurance_price' ? ' (cash fallback)' : '';
                    option.value = item.id;
                    option.textContent = `${item.name}${price !== null && price !== undefined ? ` - GHS ${Number(price).toFixed(2)}${fallback}` : ''}`;
                    select.appendChild(option);
                });
                select.disabled = false;
                enhanceSelect(select, {
                    placeholder: select.dataset.placeholder || t('searchProcedureService', 'Search procedure service'),
                    language: { noResults: () => t('noResultsFound', 'No results found') },
                });
            })
            .catch(() => {
                select.innerHTML = '<option value="">Failed to load services</option>';
                select.disabled = true;
                enhanceSelect(select, { placeholder: t('searchProcedureService', 'Search procedure service') });
            });
    },

    loadLabItems(departmentId) {
        const container = document.getElementById('labReqItemsContainer');
        const body = document.getElementById('labReqItemsBody');
        const label = document.getElementById('labReqItemsLabel');
        if (!container || !body) {
            return;
        }

        if (!departmentId) {
            container.classList.add('d-none');
            return;
        }

        container.classList.remove('d-none');
        body.innerHTML = `<span class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>${escapeHtml(t('modal.loading', 'Loading...'))}</span>`;

        const url = `${state.config.routes.deptServicesBase}/${encodeURIComponent(departmentId)}/investigation-info`;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((response) => response.json())
            .then((data) => {
                if (label) {
                    label.textContent = `Items (${data.label}) *`;
                }

                if (data.uses_catalog) {
                    if (!data.lab_tests.length) {
                        body.innerHTML = '<span class="text-warning small">No active lab tests configured.</span>';
                        return;
                    }

                    body.innerHTML = `<div class="row g-1" style="max-height:250px;overflow-y:auto;">${data.lab_tests.map((test) => {
                        const criteria = test.criteria && test.criteria.length
                            ? `<br><span class="text-muted small">${test.criteria.map((criterion) => `${escapeHtml(criterion.name)}${criterion.normal_range ? `: ${escapeHtml(criterion.normal_range)}` : ''}${criterion.unit ? ` ${escapeHtml(criterion.unit)}` : ''}`).join(' &middot; ')}</span>`
                            : '';
                        return `<div class="col-md-6"><div class="form-check">
                            <input type="checkbox" name="items[]" value="${escapeHtml(test.id)}" class="form-check-input" id="lab-test-${escapeHtml(test.id)}">
                            <label class="form-check-label small" for="lab-test-${escapeHtml(test.id)}">${escapeHtml(test.name)}${test.code ? ` <span class="text-muted">(${escapeHtml(test.code)})</span>` : ''}${criteria}</label>
                        </div></div>`;
                    }).join('')}</div>`;
                    return;
                }

                state.freeItemIndex = 0;
                body.innerHTML = `<div id="labReqFreeItems">${selectLoader.freeTextItemRow(0)}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-consultation-action="add-free-text-item">
                        <i class="ti ti-plus me-1"></i>Add Item
                    </button>`;
            })
            .catch(() => {
                body.innerHTML = '<span class="text-danger small">Failed to load items.</span>';
            });
    },

    freeTextItemRow(index) {
        const remove = index > 0
            ? `<button aria-label="Close" title="Close" type="button" class="btn btn-outline-danger" data-consultation-action="remove-row"><i class="ti ti-x"></i></button>`
            : '';

        return `<div class="input-group mb-1" id="freeItem${index}">
            <span class="input-group-text"><i class="ti ti-point"></i></span>
            <input type="text" name="items[]" class="form-control" placeholder="e.g. Chest X-Ray, Abdominal Scan..." required>
            ${remove}
        </div>`;
    },

    addFreeTextItem() {
        state.freeItemIndex += 1;
        document.getElementById('labReqFreeItems')?.insertAdjacentHTML('beforeend', selectLoader.freeTextItemRow(state.freeItemIndex));
    },

    resetInvestigation() {
        const select = document.getElementById('investigationDeptSelect');
        const services = document.getElementById('investigationServicesSelect');
        const help = document.getElementById('investigationServicesHelp');
        if (select) {
            select.value = '';
        }
        if (services) {
            if (window.jQuery?.fn?.select2 && window.jQuery(services).hasClass('select2-hidden-accessible')) {
                window.jQuery(services).select2('destroy');
            }
            services.innerHTML = '';
            services.disabled = true;
        }
        if (help) {
            help.textContent = t('selectDepartmentFirst', 'Select a department first');
        }
    },
};

const sendSession = {
    setOptions(select, placeholder, list, labelFn, placeholderDisabled = false) {
        select.innerHTML = `<option value=""${placeholderDisabled ? ' disabled' : ''}>${escapeHtml(placeholder)}</option>`;
        list.forEach((item) => {
            select.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(item.id)}">${escapeHtml(labelFn(item))}</option>`);
        });
    },

    initPicker() {
        const dept = document.getElementById('sendSessionDeptSelect');
        const service = document.getElementById('sendSessionServiceSelect');
        const doctor = document.getElementById('sendSessionDoctorSelect');
        if (!dept || !service || !doctor || dept.dataset.sendPickerBound === 'true') {
            return;
        }

        dept.dataset.sendPickerBound = 'true';
        const groupedServices = state.config.sendSessionServicesByDept || {};
        const showServices = (services) => {
            service.disabled = services.length === 0;
            sendSession.setOptions(service, services.length ? 'Optional services to link/bill' : 'No consultation services available', services, (item) => item.name, true);
        };

        on(dept, 'change', () => {
            const departmentId = dept.value;
            const fallbackServices = groupedServices[String(departmentId)] || groupedServices[departmentId] || [];

            if (!departmentId) {
                service.disabled = true;
                doctor.disabled = true;
                service.innerHTML = `<option value="">${escapeHtml(t('selectDepartmentFirst', 'Select a department first'))}</option>`;
                doctor.innerHTML = `<option value="">${escapeHtml(t('selectDepartmentFirst', 'Select a department first'))}</option>`;
                return;
            }

            service.disabled = true;
            doctor.disabled = true;
            service.innerHTML = `<option value="">${escapeHtml(t('loadingServices', 'Loading services...'))}</option>`;
            doctor.innerHTML = `<option value="">${escapeHtml(t('loadingDoctors', 'Loading doctors...'))}</option>`;
            if (fallbackServices.length) {
                showServices(fallbackServices);
            }

            const endpoint = state.config.routes.departmentVisitOptions.replace('__ID__', encodeURIComponent(departmentId));
            fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Options request failed');
                    }
                    return response.json();
                })
                .then((payload) => {
                    const services = (payload.services || []).filter((item) => item.category === 'consultation');
                    const doctors = payload.doctors || [];
                    doctor.disabled = false;
                    showServices(services.length ? services : fallbackServices);
                    sendSession.setOptions(doctor, doctors.length ? 'Optional doctor' : 'No doctor linked through specialty', doctors, (item) => item.name);
                })
                .catch(() => {
                    showServices(fallbackServices);
                    doctor.disabled = true;
                    if (!fallbackServices.length) {
                        service.innerHTML = `<option value="">${escapeHtml(t('unableLoadServices', 'Unable to load services'))}</option>`;
                    }
                    doctor.innerHTML = `<option value="">${escapeHtml(t('unableLoadDoctors', 'Unable to load doctors'))}</option>`;
                });
        });
    },
};

function editField(name, label, value, type = 'text', attrs = '') {
    return `<div class="mb-3"><label class="form-label small">${escapeHtml(label)}</label><input type="${type}" name="${escapeHtml(name)}" class="form-control" value="${escapeHtml(value || '')}" ${attrs}></div>`;
}

function editTextarea(name, label, value, rows = 3, attrs = '') {
    return `<div class="mb-3"><label class="form-label small">${escapeHtml(label)}</label><textarea name="${escapeHtml(name)}" class="form-control" rows="${rows}" ${attrs}>${escapeHtml(value || '')}</textarea></div>`;
}

function editSelect(name, label, value, options, attrs = '') {
    const items = options.map((option) => {
        const selected = String(option.value ?? '') === String(value ?? '') ? ' selected' : '';
        return `<option value="${escapeHtml(option.value)}"${selected}>${escapeHtml(option.label)}</option>`;
    }).join('');

    return `<div class="mb-3"><label class="form-label small">${escapeHtml(label)}</label><select name="${escapeHtml(name)}" class="form-select" ${attrs}>${items}</select></div>`;
}

function buildEditFields(type, entry = {}) {
    if (type === 'complaint') {
        return `<input type="hidden" name="complaint_catalogue_id" value="${escapeHtml(entry.complaint_catalogue_id || '')}">
            ${editField('description', 'Complaint', entry.description, 'text', 'required')}
            <div class="row"><div class="col-md-4">${editField('duration', 'Duration', entry.duration)}</div><div class="col-md-4">
            ${editSelect('duration_unit', 'Duration Unit', entry.duration_unit, [
                { value: '', label: '-- Select --' }, { value: 'minutes', label: 'Minutes' }, { value: 'hours', label: 'Hours' }, { value: 'days', label: 'Days' }, { value: 'weeks', label: 'Weeks' }, { value: 'months', label: 'Months' }, { value: 'years', label: 'Years' },
            ])}</div><div class="col-md-4">
            ${editSelect('severity', 'Severity', entry.severity, [
                { value: '', label: '-- Select --' }, { value: 'mild', label: 'Mild' }, { value: 'moderate', label: 'Moderate' }, { value: 'severe', label: 'Severe' }, { value: 'critical', label: 'Critical' },
            ])}</div></div>${editTextarea('notes', 'Notes', entry.notes, 2)}`;
    }

    if (type === 'hopc') {
        return `${editTextarea('content', 'Narrative', entry.content, 4, 'required')}
            <div class="row"><div class="col-md-3">${editField('onset', 'Onset', entry.onset)}</div><div class="col-md-3">${editField('duration', 'Duration', entry.duration)}</div><div class="col-md-3">${editField('location', 'Location', entry.location)}</div><div class="col-md-3">${editField('severity', 'Severity', entry.severity)}</div></div>
            ${editField('associated_symptoms', 'Associated Symptoms', entry.associated_symptoms)}
            <div class="row"><div class="col-md-6">${editField('aggravating_factors', 'Aggravating Factors', entry.aggravating_factors)}</div><div class="col-md-6">${editField('relieving_factors', 'Relieving Factors', entry.relieving_factors)}</div></div>`;
    }

    if (type === 'examination') {
        return `${editTextarea('findings', 'Findings', entry.findings, 3, 'required')}
            <div class="row"><div class="col-md-6">${editTextarea('general_examination', 'General Examination', entry.general_examination, 2)}</div><div class="col-md-6">${editTextarea('systemic_examination', 'Systemic Examination', entry.systemic_examination, 2)}</div></div>
            <div class="row"><div class="col-md-6">${editTextarea('cardiovascular', 'Cardiovascular', entry.cardiovascular, 2)}</div><div class="col-md-6">${editTextarea('respiratory', 'Respiratory', entry.respiratory, 2)}</div></div>
            <div class="row"><div class="col-md-6">${editTextarea('gastrointestinal', 'Gastrointestinal', entry.gastrointestinal, 2)}</div><div class="col-md-6">${editTextarea('central_nervous_system', 'Central Nervous System', entry.central_nervous_system, 2)}</div></div>
            <div class="row"><div class="col-md-6">${editTextarea('specialty_examination', 'Specialty Examination', entry.specialty_examination, 2)}</div><div class="col-md-6">${editTextarea('local_examination', 'Local Examination', entry.local_examination, 2)}</div></div>
            ${editTextarea('notes', 'Notes', entry.notes, 2)}`;
    }

    if (type === 'diagnosis') {
        return `${editField('description', 'Description', entry.description, 'text', 'required')}
            <div class="row"><div class="col-md-6">${editField('icd_code', 'ICD-10 Code', entry.icd_code)}</div><div class="col-md-6">${editSelect('type', 'Type', entry.type, [{ value: 'provisional', label: 'Provisional' }, { value: 'final', label: 'Final' }])}</div></div>
            ${editField('notes', 'Notes', entry.notes)}`;
    }

    if (type === 'treatment') {
        return `${editSelect('type', 'Type', entry.type, [
            { value: 'medication', label: 'Medication' }, { value: 'procedure', label: 'Procedure' }, { value: 'referral', label: 'Referral' }, { value: 'advice', label: 'Advice' },
        ], 'required')}${editTextarea('description', 'Description', entry.description, 3, 'required')}`;
    }

    if (type === 'prescription') {
        return editTextarea('notes', 'Prescription Notes', entry.notes, 3);
    }

    if (type === 'lab-request') {
        return `${editSelect('urgency', 'Urgency', entry.urgency, [
            { value: 'routine', label: 'Routine' }, { value: 'urgent', label: 'Urgent' }, { value: 'emergency', label: 'Emergency' },
        ])}${editTextarea('clinical_info', 'Clinical Notes', entry.clinical_info, 3)}`;
    }

    if (type === 'procedure') {
        return `${editSelect('priority', 'Priority', entry.priority, [
            { value: 'routine', label: 'Routine' }, { value: 'urgent', label: 'Urgent' }, { value: 'emergency', label: 'Emergency' },
        ], 'required')}${editField('preferred_datetime', 'Preferred Date/Time', entry.preferred_datetime, 'datetime-local')}
            ${editTextarea('indication', 'Indication / Reason', entry.indication, 3, 'required')}${editTextarea('notes', 'Notes', entry.notes, 2)}`;
    }

    if (type === 'task') {
        const userOptions = [{ value: '', label: 'Unassigned' }].concat((state.config.taskAssignableUsers || []).map((user) => ({ value: user.id, label: user.name })));
        return `${editField('title', 'Task Title', entry.title, 'text', 'required')}${editTextarea('description', 'Description', entry.description, 2)}
            <div class="row"><div class="col-md-4">${editSelect('priority', 'Priority', entry.priority, [{ value: 'low', label: 'Low' }, { value: 'medium', label: 'Medium' }, { value: 'high', label: 'High' }])}</div><div class="col-md-4">
            ${editSelect('status', 'Status', entry.status, [{ value: 'pending', label: 'Pending' }, { value: 'in_progress', label: 'In Progress' }, { value: 'completed', label: 'Completed' }, { value: 'cancelled', label: 'Cancelled' }])}</div><div class="col-md-4">${editField('due_date', 'Due Date', entry.due_date, 'date')}</div></div>
            ${editSelect('assigned_to', 'Assign To', entry.assigned_to, userOptions)}`;
    }

    return '<p class="text-muted mb-0">This entry type cannot be edited here.</p>';
}

function sectionForEntryType(type) {
    return {
        complaint: 'complaints',
        hopc: 'hopc',
        examination: 'examination',
        diagnosis: 'diagnoses',
        treatment: 'treatments',
        prescription: 'prescriptions',
        'lab-request': 'investigations',
        procedure: 'procedures',
        task: 'tasks',
    }[type] || type;
}

const entries = {
    init() {},

    openEdit(button) {
        const modal = document.getElementById('editEntryModal');
        const form = document.getElementById('editEntryForm');
        const fields = document.getElementById('editEntryFields');
        const title = document.getElementById('editEntryTitle');
        const errors = document.getElementById('editEntryErrors');
        if (!modal || !form || !fields) {
            return;
        }

        let entry = {};
        try {
            entry = JSON.parse(button.dataset.entry || '{}');
        } catch (error) {}

        const type = button.dataset.entryType;
        form.action = button.dataset.url;
        form.dataset.entryType = type;
        title.textContent = `Edit ${type.replace('-', ' ').replace(/\b\w/g, (char) => char.toUpperCase())}`;
        fields.innerHTML = buildEditFields(type, entry);
        errors?.classList.add('d-none');
        if (errors) {
            errors.innerHTML = '';
        }

        window.bootstrap?.Modal.getOrCreateInstance(modal).show();
    },

    submitEdit(form) {
        const section = sectionForEntryType(form.dataset.entryType);
        const button = form.querySelector('[type="submit"]');
        const errors = document.getElementById('editEntryErrors');
        setBusy(button, true);

        if (errors) {
            errors.classList.add('d-none');
            errors.innerHTML = '';
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((payload) => { throw payload; });
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success) {
                    throw data;
                }

                window.bootstrap?.Modal.getInstance(document.getElementById('editEntryModal'))?.hide();
                Promise.all([sectionRefresh.refresh(section), sectionRefresh.summary()]).then(() => {
                    tabs.activate(`#${section}-section`);
                    toast('Updated successfully.');
                });
            })
            .catch((error) => {
                const message = normalizeErrors(error, 'Update failed.');
                if (errors) {
                    errors.classList.remove('d-none');
                    errors.innerHTML = escapeHtml(message).replace(/\n/g, '<br>');
                    return;
                }
                toast(message, 'danger');
            })
            .finally(() => setBusy(button, false));
    },

    remove(button) {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('_token', csrfToken());
        button.disabled = true;

        fetch(button.dataset.url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw data;
                }

                document.querySelector(button.dataset.target)?.remove();
                const badgeId = button.dataset.badge;
                if (badgeId) {
                    const badge = document.getElementById(badgeId);
                    if (badge) {
                        badge.textContent = Math.max(0, Number.parseInt(badge.textContent || 0, 10) - 1);
                    }
                    sectionRefresh.refresh(badgeId.replace('badge-', ''));
                }
                sectionRefresh.summary();
            })
            .catch((error) => {
                button.disabled = false;
                toast(normalizeErrors(error, t('deleteFailed', 'Delete failed.')), 'danger');
            });
    },
};

const diagnosis = {
    init() {},

    markFinal(button) {
        if (!window.confirm(t('markDiagnosisFinal', 'Mark this diagnosis as final?'))) {
            return;
        }

        const id = button.dataset.id;
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', csrfToken());
        formData.append('type', 'final');
        button.disabled = true;

        fetch(button.dataset.url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw data;
                }

                const badge = document.getElementById(`type-badge-${id}`);
                if (badge) {
                    badge.textContent = 'Final';
                    badge.className = 'badge bg-success ms-1 diagnosis-type-badge';
                }
                button.remove();
                sectionRefresh.summary();
                toast('Type set to Final.');
            })
            .catch(() => toast(t('updateTypeFailed', 'Could not update diagnosis type.'), 'danger'))
            .finally(() => {
                button.disabled = false;
            });
    },

    setPrimary(button) {
        const id = button.dataset.id;
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', csrfToken());
        button.disabled = true;

        fetch(button.dataset.url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw data;
                }

                document.querySelectorAll('.primary-indicator').forEach((badge) => badge.classList.add('d-none'));
                document.querySelectorAll('.set-primary-btn').forEach((candidate) => candidate.classList.remove('d-none'));
                document.querySelectorAll('#diagnoses-list .ehr-item').forEach((entry) => entry.classList.remove('is-primary'));
                document.getElementById(`primary-badge-${id}`)?.classList.remove('d-none');
                document.getElementById(`set-primary-${id}`)?.classList.add('d-none');
                document.getElementById(`diagnosis-${id}`)?.classList.add('is-primary');
                sectionRefresh.summary();
                toast('Primary diagnosis updated.');
            })
            .catch(() => toast(t('setPrimaryFailed', 'Could not set primary diagnosis.'), 'danger'))
            .finally(() => {
                button.disabled = false;
            });
    },
};

const prescriptions = {
    init(root = document) {
        if (window.jQuery) {
            window.jQuery(root).find('.drug-select').each((index, select) => prescriptions.initDrugSelect(select));
        }

        root.querySelectorAll('#prescriptionItems .prescription-item').forEach((row) => prescriptions.bindCalc(row));
        state.prescriptionIndex = document.querySelectorAll('#prescriptionItems .prescription-item').length || 1;
    },

    initDrugSelect(select) {
        if (!select || !window.jQuery?.fn?.select2) {
            return;
        }

        const selected = select.value;
        const $select = window.jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        select.value = selected;
        enhanceSelect(select, {
            placeholder: '-- Search drug --',
            allowClear: true,
            language: { noResults: () => t('noResultsFound', 'No results found') },
        });
    },

    syncDrugName(row) {
        const drug = row.querySelector('.drug-select');
        const hidden = row.querySelector('.drug-name-input');
        if (!drug || !hidden) {
            return;
        }

        const selected = drug.options[drug.selectedIndex];
        hidden.value = selected && selected.value ? (selected.getAttribute('data-name') || selected.textContent.trim()) : '';
    },

    parseMg(value) {
        if (!value) {
            return null;
        }
        const match = String(value).match(/([\d.]+)\s*(mg|mcg|g|ml|iu|units?)?/i);
        if (!match) {
            return null;
        }
        let amount = Number.parseFloat(match[1]);
        const unit = (match[2] || 'mg').toLowerCase();
        if (unit === 'g') {
            amount *= 1000;
        }
        if (unit === 'mcg') {
            amount /= 1000;
        }
        return Number.isNaN(amount) ? null : amount;
    },

    parseDoseUnits(value) {
        if (!value) {
            return null;
        }
        const text = String(value).toLowerCase().trim();
        const unitDose = text.match(/^(\d+(?:\.\d+)?)\s*(tab|tabs|tablet|tablets|cap|caps|capsule|capsules|amp|amps|ampoule|ampoules|vial|vials|drop|drops|puff|puffs|sachet|sachets|unit|units)\b/);
        if (unitDose) {
            const amount = Number.parseFloat(unitDose[1]);
            return Number.isNaN(amount) ? null : amount;
        }
        const plain = text.match(/^(\d+(?:\.\d+)?)$/);
        if (plain) {
            const amount = Number.parseFloat(plain[1]);
            return Number.isNaN(amount) ? null : amount;
        }
        return null;
    },

    parseDays(value) {
        if (!value) {
            return null;
        }
        const match = String(value).toLowerCase().trim().match(/^(\d+(?:\.\d+)?)\s*(day|days|week|weeks|month|months|wk|wks)?/);
        if (!match) {
            return null;
        }
        let days = Number.parseFloat(match[1]);
        const unit = match[2] || 'day';
        if (unit.startsWith('week') || unit === 'wk' || unit === 'wks') {
            days *= 7;
        }
        if (unit.startsWith('month')) {
            days *= 30;
        }
        return Number.isNaN(days) ? null : Math.round(days);
    },

    calcQty(row) {
        const drug = row.querySelector('.drug-select');
        const dosage = row.querySelector('[name$="[dosage]"]');
        const frequency = row.querySelector('[name$="[frequency]"]');
        const duration = row.querySelector('[name$="[duration]"]');
        const quantity = row.querySelector('[name$="[quantity]"]');
        if (!drug || !dosage || !frequency || !duration || !quantity) {
            return;
        }

        const selected = drug.options[drug.selectedIndex];
        const strength = selected?.getAttribute('data-strength');
        const days = prescriptions.parseDays(duration.value.trim());
        const freqMap = state.config.frequencyDoseMap || { OD: 1, BD: 2, TDS: 3, QDS: 4, STAT: 1, PRN: 1 };
        const frequencyValue = freqMap[frequency.value] || 1;
        if (!days) {
            return;
        }

        const doseUnits = prescriptions.parseDoseUnits(dosage.value.trim());
        let tabletsPerDose = doseUnits || 1;
        const doseMg = prescriptions.parseMg(dosage.value.trim());
        const strengthMg = prescriptions.parseMg(strength);
        if (!doseUnits && doseMg && strengthMg && strengthMg > 0) {
            tabletsPerDose = Math.ceil(doseMg / strengthMg);
        }

        let total = tabletsPerDose * frequencyValue * days;
        if (frequency.value === 'STAT') {
            total = tabletsPerDose;
        }
        total = Math.ceil(total);
        if (total > 0) {
            quantity.value = total;
            quantity.style.background = '#fffbe6';
            setTimeout(() => { quantity.style.background = ''; }, 1200);
        }
    },

    bindCalc(row) {
        if (!row || row.dataset.rxCalcBound === 'true') {
            return;
        }

        row.dataset.rxCalcBound = 'true';
        ['change', 'input'].forEach((eventName) => {
            on(row.querySelector('[name$="[dosage]"]'), eventName, () => prescriptions.calcQty(row));
            on(row.querySelector('[name$="[duration]"]'), eventName, () => prescriptions.calcQty(row));
        });
        on(row.querySelector('[name$="[frequency]"]'), 'change', () => prescriptions.calcQty(row));

        if (window.jQuery) {
            window.jQuery(row).find('.drug-select').off('.uhmsRxCalc').on('select2:select.uhmsRxCalc select2:clear.uhmsRxCalc change.uhmsRxCalc', () => {
                prescriptions.syncDrugName(row);
                prescriptions.calcQty(row);
            });
        }
    },

    addItem() {
        const container = document.getElementById('prescriptionItems');
        const source = container?.querySelector('.prescription-item');
        if (!container || !source) {
            return;
        }

        const clone = source.cloneNode(true);
        clone.dataset.rxCalcBound = '';
        clone.querySelectorAll('.select2-container').forEach((containerNode) => containerNode.remove());
        clone.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace(/items\[\d+\]/, `items[${state.prescriptionIndex}]`);
            if (input.tagName === 'INPUT') {
                input.value = input.type === 'number' ? '1' : '';
            }
            if (input.tagName === 'SELECT' && input.classList.contains('drug-select')) {
                input.value = '';
                input.classList.remove('select2-hidden-accessible');
                input.removeAttribute('data-select2-id');
                input.removeAttribute('aria-hidden');
                input.removeAttribute('tabindex');
            }
        });
        clone.querySelectorAll('option[data-select2-id]').forEach((option) => option.removeAttribute('data-select2-id'));
        clone.style.position = 'relative';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-xs btn-outline-danger position-absolute top-0 end-0 m-1';
        remove.setAttribute('data-consultation-action', 'remove-prescription-row');
        remove.innerHTML = '<i class="ti ti-x"></i>';
        clone.appendChild(remove);
        container.appendChild(clone);

        if (window.jQuery) {
            window.jQuery(clone).find('.drug-select').each((index, select) => prescriptions.initDrugSelect(select));
        }
        prescriptions.bindCalc(clone);
        state.prescriptionIndex += 1;
    },

    reindexRows() {
        document.querySelectorAll('#prescriptionItems .prescription-item').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
            prescriptions.syncDrugName(row);
        });
        state.prescriptionIndex = document.querySelectorAll('#prescriptionItems .prescription-item').length;
    },

    prepareSubmit() {
        document.querySelectorAll('#prescriptionItems .prescription-item').forEach((row) => {
            prescriptions.syncDrugName(row);
            prescriptions.calcQty(row);
        });
        prescriptions.reindexRows();
    },
};

const previousVisits = {
    preview(index) {
        const visit = (state.config.visitHistoryData || [])[index];
        if (!visit) {
            return;
        }

        let html = `<p class="mb-3"><span class="fw-bold fs-6">${escapeHtml(visit.visit_number)}</span> <span class="text-muted">${escapeHtml(visit.date)}</span>${visit.doctor ? ` &middot; Dr. ${escapeHtml(visit.doctor)}` : ''}</p>`;
        const sections = [
            ['complaints', 'Complaints', (item) => `<div class="ehr-item py-1">${escapeHtml(item)}</div>`],
            ['diagnoses', 'Diagnoses', (item) => `<div class="ehr-item py-1">${escapeHtml(item.description)} <span class="badge bg-${item.type === 'final' ? 'success' : 'warning'}">${capFirst(item.type)}</span>${item.is_primary ? ' <span class="badge bg-warning text-dark"><i class="ti ti-star-filled me-1"></i>Primary</span>' : ''}${item.icd_code ? ` <code class="ms-1">${escapeHtml(item.icd_code)}</code>` : ''}</div>`],
            ['investigations', 'Investigations', (item) => `<div class="ehr-item py-1"><span class="badge bg-dark">${escapeHtml(item.type)}</span>${item.description && item.description !== item.type ? ` ${escapeHtml(item.description)}` : ''} <span class="badge bg-${item.urgency === 'emergency' ? 'danger' : item.urgency === 'urgent' ? 'warning' : 'secondary'}">${capFirst(item.urgency || 'routine')}</span></div>`],
            ['treatments', 'Treatments', (item) => `<div class="ehr-item py-1"><span class="badge bg-${item.type === 'medication' ? 'primary' : item.type === 'procedure' ? 'info' : item.type === 'referral' ? 'warning' : 'secondary'}">${capFirst(item.type)}</span> ${escapeHtml(item.description)}</div>`],
        ];

        let renderedAny = false;
        sections.forEach(([key, label, render]) => {
            if (visit[key] && visit[key].length) {
                renderedAny = true;
                html += `<h6 class="fw-bold small text-muted border-bottom pb-1 mb-2 mt-3">${label}</h6>`;
                html += visit[key].map(render).join('');
            }
        });

        if (!renderedAny) {
            html = '<div class="text-center text-muted py-3">No clinical data recorded for this visit.</div>';
        }

        const target = document.getElementById('visitPreviewContent');
        if (target) {
            target.innerHTML = html;
        }
        const modal = document.getElementById('visitPreviewModal');
        if (modal && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    },
};

const patterns = {
    apply(button) {
        const patternId = button.dataset.patternId;
        const patternName = button.dataset.patternName;
        const available = (button.dataset.patternTypes || '').split(',').filter(Boolean);
        if (!window.confirm(`Apply pattern "${patternName}"?`)) {
            return;
        }

        const sectionInput = window.prompt('Sections to apply (comma separated). Leave as-is to apply all shown sections.', available.join(','));
        if (sectionInput === null) {
            return;
        }

        const selectedSections = sectionInput.split(',').map((section) => section.trim()).filter(Boolean);
        setBusy(button, true);

        fetch(`${state.config.routes.patternsBase}/${patternId}/apply`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                visit_id: state.config.visitId,
                consultation_route_id: routeContext.current(),
                sections: selectedSections,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw data;
                }

                const normalize = {
                    complaint: 'complaints',
                    diagnosis: 'diagnoses',
                    investigation: 'investigations',
                    treatment: 'treatments',
                    prescription: 'prescriptions',
                    procedure: 'procedures',
                    task: 'tasks',
                };
                const refreshSections = (selectedSections.length ? selectedSections : available)
                    .map((section) => normalize[section] || section)
                    .filter((section, index, all) => section && all.indexOf(section) === index);

                Promise.all(refreshSections.map((section) => sectionRefresh.refresh(section)).concat([sectionRefresh.summary()])).then(() => {
                    tabs.activate('#patterns-section');
                    toast('Pattern applied successfully.');
                });
            })
            .catch((error) => toast(normalizeErrors(error, 'Failed to apply pattern.'), 'danger'))
            .finally(() => setBusy(button, false));
    },

    search() {
        const input = document.getElementById('patternSearchInput');
        const results = document.getElementById('patternSearchResults');
        if (!input || !results) {
            return;
        }

        const query = input.value.trim();
        if (query.length < 3) {
            results.innerHTML = '<div class="alert alert-warning py-2">Enter at least 3 characters.</div>';
            results.style.display = 'block';
            return;
        }

        results.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm text-primary"></span></div>';
        results.style.display = 'block';
        fetch(`${state.config.routes.patternSuggest}?query=${encodeURIComponent(query)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.patterns && data.patterns.length) {
                    results.innerHTML = data.patterns.map((pattern) => {
                        const patternTypes = (pattern.items || []).map((item) => item.type).filter((value, index, array) => array.indexOf(value) === index).join(',');
                        return `<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">
                            <div><strong>${escapeHtml(pattern.name)}</strong> <small class="text-muted">(${pattern.items.length} items)</small></div>
                            <button type="button" class="btn btn-sm btn-success apply-pattern-btn" data-consultation-action="apply-pattern" data-pattern-id="${escapeHtml(pattern.id)}" data-pattern-name="${escapeHtml(pattern.name)}" data-pattern-types="${escapeHtml(patternTypes)}"><i class="ti ti-check me-1"></i>Apply</button>
                        </div>`;
                    }).join('');
                    return;
                }

                results.innerHTML = '<div class="alert alert-info py-2 mb-0">No patterns found.</div>';
            })
            .catch(() => {
                results.innerHTML = '<div class="alert alert-danger py-2 mb-0">Search failed.</div>';
            });
    },
};

const suggestions = {
    init() {
        suggestions.bindComplaint();
        suggestions.bind('diagnosis_description', 'diagnosisSuggestions', 'diagnosis');
    },

    syncComplaint(value) {
        const hidden = document.getElementById('complaintCatalogueIdInput');
        if (!hidden) {
            return;
        }

        const match = state.complaintSuggestionIndex[String(value || '').toLowerCase()];
        hidden.value = match ? match.id : '';
    },

    bindComplaint() {
        const input = document.getElementById('complaintDescInput');
        const menu = document.getElementById('complaintSuggestionMenu');
        const hidden = document.getElementById('complaintCatalogueIdInput');
        const url = state.config.routes?.suggest?.complaint;
        if (!input || !menu || !hidden || !url || input.dataset.suggestBound === 'true') {
            return;
        }

        input.dataset.suggestBound = 'true';

        const hide = () => {
            menu.classList.add('d-none');
            input.setAttribute('aria-expanded', 'false');
            state.complaintSuggestionActive = -1;
        };

        const markActive = () => {
            menu.querySelectorAll('[data-complaint-suggestion-index]').forEach((item) => {
                item.classList.toggle('active', Number(item.dataset.complaintSuggestionIndex) === state.complaintSuggestionActive);
            });
        };

        const choose = (item) => {
            if (!item) {
                return;
            }

            input.value = item.name || '';
            hidden.value = item.id || '';
            suggestions.syncComplaint(input.value);
            hide();
        };

        const render = (items, query) => {
            state.complaintSuggestions = Array.isArray(items) ? items : [];
            state.complaintSuggestionIndex = {};

            if (!state.complaintSuggestions.length) {
                menu.innerHTML = `<div class="list-group-item small text-muted">${escapeHtml(t('noResultsFound', 'No results found'))}. ${escapeHtml(query)} can still be saved as free text.</div>`;
                menu.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
                hidden.value = '';
                return;
            }

            menu.innerHTML = state.complaintSuggestions.map((item, index) => {
                state.complaintSuggestionIndex[String(item.name || '').toLowerCase()] = item;
                const meta = item.category ? `<small class="text-muted d-block">${escapeHtml(item.category)}</small>` : '';
                return `<button type="button" class="list-group-item list-group-item-action py-2" data-complaint-suggestion-index="${index}">
                    <span class="fw-semibold">${escapeHtml(item.name || '')}</span>${meta}
                </button>`;
            }).join('');
            menu.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');
            suggestions.syncComplaint(input.value);
        };

        on(input, 'input', () => {
            const query = input.value.trim();
            window.clearTimeout(state.timers.complaint);
            hidden.value = '';

            if (query.length < 2) {
                menu.innerHTML = '';
                hide();
                return;
            }

            state.timers.complaint = window.setTimeout(() => {
                fetch(`${url}?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                })
                    .then((response) => response.json())
                    .then((items) => render(items, query))
                    .catch(() => render([], query));
            }, 280);
        });

        on(input, 'keydown', (event) => {
            if (menu.classList.contains('d-none')) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                state.complaintSuggestionActive = Math.min(state.complaintSuggestions.length - 1, state.complaintSuggestionActive + 1);
                markActive();
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                state.complaintSuggestionActive = Math.max(0, state.complaintSuggestionActive - 1);
                markActive();
            }

            if (event.key === 'Enter' && state.complaintSuggestionActive >= 0) {
                event.preventDefault();
                choose(state.complaintSuggestions[state.complaintSuggestionActive]);
            }

            if (event.key === 'Escape') {
                hide();
            }
        });

        on(menu, 'mousedown', (event) => {
            const item = event.target.closest('[data-complaint-suggestion-index]');
            if (!item) {
                return;
            }
            event.preventDefault();
            choose(state.complaintSuggestions[Number(item.dataset.complaintSuggestionIndex)]);
        });

        on(input, 'blur', () => {
            suggestions.syncComplaint(input.value);
            window.setTimeout(hide, 140);
        });
    },

    bind(inputId, datalistId, type) {
        const input = document.getElementById(inputId);
        const datalist = document.getElementById(datalistId);
        const url = state.config.routes?.suggest?.[type];
        if (!input || !datalist || !url || input.dataset.suggestBound === 'true') {
            return;
        }

        input.dataset.suggestBound = 'true';
        on(input, 'input', () => {
            const query = input.value.trim();
            window.clearTimeout(state.timers[type]);
            if (query.length < 2) {
                datalist.innerHTML = '';
                return;
            }

            state.timers[type] = window.setTimeout(() => {
                fetch(`${url}?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                })
                    .then((response) => response.json())
                    .then((items) => {
                        if (type === 'complaint') {
                            state.complaintSuggestionIndex = {};
                            datalist.innerHTML = items.map((item) => {
                                state.complaintSuggestionIndex[String(item.name || '').toLowerCase()] = item;
                                const label = item.category ? item.category : 'Complaint catalogue';
                                return `<option value="${escapeHtml(item.name || '')}" label="${escapeHtml(label)}">`;
                            }).join('');
                            suggestions.syncComplaint(input.value);
                            return;
                        }

                        datalist.innerHTML = items.map((item) => `<option value="${escapeHtml(item)}">`).join('');
                    });
            }, 280);
        });

        if (type === 'complaint') {
            on(input, 'change', () => suggestions.syncComplaint(input.value));
            on(input, 'blur', () => suggestions.syncComplaint(input.value));
        }
    },
};

const icd = {
    init() {
        const $jq = window.jQuery;
        if (!$jq || !$jq('#icd_code_select').length || !$jq.fn.select2) {
            return;
        }

        const $select = $jq('#icd_code_select');
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        $select.off('.uhmsIcd').select2({
            placeholder: t('searchIcd10', 'Search ICD-10 by code or description'),
            allowClear: true,
            minimumInputLength: 2,
            minimumResultsForSearch: 0,
            ajax: {
                url: state.config.routes.icdSearch,
                dataType: 'json',
                delay: 300,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data.results }),
                cache: true,
            },
            templateResult(item) {
                if (item.loading) {
                    return item.text;
                }
                return $jq('<span>').html(`<strong>${escapeHtml(item.code)}</strong> - ${escapeHtml(item.description)}`);
            },
            templateSelection(item) {
                return item.text || item.code;
            },
        }).on('select2:select.uhmsIcd', (event) => {
            const data = event.params.data;
            $jq('#icd_code_id').val(data.id);
            $jq('#icd_code_manual').val(data.code);
            const description = $jq('#diagnosis_description');
            if (!description.val().trim()) {
                description.val(data.description);
            }
        }).on('select2:clear.uhmsIcd', () => {
            $jq('#icd_code_id').val('');
            $jq('#icd_code_manual').val('');
        });
    },
};

const hopcHydration = {
    init() {
        const select = document.getElementById('hopcComplaintSelect');
        if (!select || select.dataset.hopcHydrationBound === 'true') {
            return;
        }

        select.dataset.hopcHydrationBound = 'true';
        enhanceSelect(select, {
            placeholder: 'Link to complaint',
            allowClear: true,
            language: { noResults: () => t('noResultsFound', 'No results found') },
        });
        on(select, 'change', () => hopcHydration.apply(select));
    },

    apply(select) {
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            return;
        }

        const content = document.getElementById('hopcContentInput');
        const duration = document.getElementById('hopcDurationInput');
        const severity = document.getElementById('hopcSeverityInput');

        if (content && !content.value.trim() && option.dataset.description) {
            content.value = option.dataset.description;
        }
        if (duration && option.dataset.duration) {
            duration.value = option.dataset.duration;
        }
        if (severity && option.dataset.severity) {
            severity.value = option.dataset.severity;
        }
    },
};

const followUp = {
    init() {
        const department = document.getElementById('followUpDepartmentSelect');
        const service = document.getElementById('followUpServiceSelect');
        if (!department || !service || department.dataset.followUpBound === 'true') {
            return;
        }

        department.dataset.followUpBound = 'true';
        const sync = () => {
            const departmentId = department.value;
            Array.prototype.forEach.call(service.options, (option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                const matches = !departmentId || option.dataset.departmentId === departmentId;
                option.hidden = !matches;
                if (!matches && option.selected) {
                    service.value = '';
                }
            });
        };
        on(department, 'change', sync);
        sync();

        if (state.config.openFollowUpModalOnLoad) {
            const modal = document.getElementById('followUpAppointmentModal');
            if (modal && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        }
    },
};

function bindDelegatedEvents() {
    on(document, 'click', (event) => {
        const confirmButton = event.target.closest('[data-confirm]');
        if (confirmButton && !window.confirm(confirmButton.dataset.confirm)) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        const actionButton = event.target.closest('[data-consultation-action]');
        if (!actionButton) {
            return;
        }

        const action = actionButton.dataset.consultationAction;
        if (action === 'print') {
            window.print();
            return;
        }
        if (action === 'open-url' && actionButton.dataset.url) {
            window.location.href = actionButton.dataset.url;
            return;
        }
        if (action === 'toggle-sessions-drawer') {
            const drawer = document.getElementById('sessionsDrawer');
            const handle = document.getElementById('sessionsDrawerHandle');
            const collapsed = drawer?.classList.toggle('is-collapsed');
            if (handle) {
                handle.setAttribute('aria-expanded', String(!collapsed));
            }
            return;
        }
        if (action === 'open-send-session-modal') {
            sendSession.initPicker();
            return;
        }
        if (action === 'view-result') {
            modals.viewResult(actionButton);
            return;
        }
        if (action === 'edit-entry') {
            entries.openEdit(actionButton);
            return;
        }
        if (action === 'delete-entry') {
            entries.remove(actionButton);
            return;
        }
        if (action === 'mark-diagnosis-final') {
            diagnosis.markFinal(actionButton);
            return;
        }
        if (action === 'set-primary-diagnosis') {
            diagnosis.setPrimary(actionButton);
            return;
        }
        if (action === 'add-prescription-item') {
            prescriptions.addItem();
            return;
        }
        if (action === 'remove-prescription-row') {
            actionButton.closest('.prescription-item')?.remove();
            prescriptions.reindexRows();
            return;
        }
        if (action === 'preview-visit') {
            previousVisits.preview(Number(actionButton.dataset.visitIndex));
            return;
        }
        if (action === 'apply-pattern') {
            patterns.apply(actionButton);
            return;
        }
        if (action === 'search-patterns') {
            patterns.search();
            return;
        }
        if (action === 'add-free-text-item') {
            selectLoader.addFreeTextItem();
            return;
        }
        if (action === 'remove-row') {
            actionButton.closest('.input-group, [data-removable-row]')?.remove();
        }
    });

    on(document, 'change', (event) => {
        const control = event.target.closest('[data-consultation-action]');
        if (!control) {
            return;
        }

        const action = control.dataset.consultationAction;
        if (action === 'load-investigation-services') {
            selectLoader.loadInvestigation(control.value);
        }
        if (action === 'load-procedure-services') {
            selectLoader.loadProcedure(control.value);
        }
        if (action === 'load-lab-request-items') {
            selectLoader.loadLabItems(control.value);
        }
    });

    on(document, 'keypress', (event) => {
        if (event.target?.id === 'patternSearchInput' && event.key === 'Enter') {
            event.preventDefault();
            patterns.search();
        }
    });

    on(document, 'submit', (event) => {
        const form = event.target;
        if (!form) {
            return;
        }

        if (!routeContext.ensure(form)) {
            event.preventDefault();
            return;
        }

        if (form.id === 'editEntryForm') {
            event.preventDefault();
            entries.submitEdit(form);
            return;
        }

        if (form.dataset.consultationAjax === 'true') {
            event.preventDefault();
            ajaxForms.submit(form);
        }
    });
}

const modals = {
    viewResult(button) {
        const modal = document.getElementById('viewResultModal');
        const body = document.getElementById('viewResultBody');
        if (!modal || !body) {
            return;
        }

        body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>';
        window.bootstrap?.Modal.getOrCreateInstance(modal).show();
        fetch(button.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.text())
            .then((html) => {
                body.innerHTML = html;
            })
            .catch((error) => {
                body.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
            });
    },
};

function applyReadOnlyState() {
    if (state.config.canEdit) {
        return;
    }

    document.querySelectorAll('[data-ajax-form], [data-consultation-form]').forEach((form) => {
        form.querySelectorAll('input, select, textarea, button').forEach((element) => {
            element.disabled = true;
        });
        form.classList.add('opacity-50');
    });
    document.querySelectorAll('button[data-bs-target^="#add"], button[data-bs-target="#investigationModal"], button[data-bs-target="#sendSessionModal"], button[data-bs-target="#savePatternModal"]').forEach((button) => {
        button.disabled = true;
        button.classList.add('disabled');
    });
}

function rehydrate(root = document) {
    routeContext.syncAll(root);
    ajaxForms.init(root);
    diagnosis.init(root);
    entries.init(rootOrDocument(root));
    prescriptions.init(root);
    suggestions.init();
    icd.init();
    hopcHydration.init();
    followUp.init();
    enhanceSelect(document.getElementById('procedureServiceSelect'), {
        placeholder: t('searchProcedureService', 'Search procedure service'),
        language: { noResults: () => t('noResultsFound', 'No results found') },
    });
    applyReadOnlyState();
}

function init(root = document) {
    state.config = readConfig();
    destroy();
    state.abortController = new AbortController();

    ready(() => {
        tabs.init();
        tabs.bindSubmitPreservation();
        bindDelegatedEvents();
        modalHelper.init();
        sendSession.initPicker();
        rehydrate(root);
    });
}

function destroy() {
    if (state.abortController) {
        state.abortController.abort();
    }
    state.abortController = new AbortController();
}

window.UHMSConsultation = {
    init,
    destroy,
    routeContext,
    ajaxForms,
    modals: modalHelper,
    sectionRefresh,
    selectLoader,
    idempotency,
    toasts: { show: toast },
    prescriptions,
};

init();
