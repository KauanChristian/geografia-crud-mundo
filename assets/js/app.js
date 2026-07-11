(() => {
    'use strict';

    const normalize = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('pt-BR');

    const menuToggle = document.querySelector('[data-menu-toggle]');
    const navigation = document.querySelector('[data-primary-navigation]');

    if (menuToggle && navigation) {
        menuToggle.addEventListener('click', () => {
            const isOpen = navigation.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });
    }

    document.querySelectorAll('[data-dismiss-flash]').forEach((button) => {
        button.addEventListener('click', () => {
            const flash = button.closest('.flash');
            if (flash) {
                flash.remove();
            }
        });
    });

    document.querySelectorAll('[data-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const name = form.dataset.recordName || 'este registro';
            if (!window.confirm(`Deseja realmente excluir "${name}"? Esta ação não poderá ser desfeita.`)) {
                event.preventDefault();
            }
        });
    });

    const localSearch = document.querySelector('[data-table-search]');
    const filterRows = [...document.querySelectorAll('[data-filter-row]')];
    const recordCount = document.querySelector('[data-record-count]');
    const filterEmpty = document.querySelector('[data-empty-filter]');

    if (localSearch && filterRows.length) {
        localSearch.addEventListener('input', () => {
            const needle = normalize(localSearch.value.trim());
            let visible = 0;

            filterRows.forEach((row) => {
                const matches = normalize(row.textContent).includes(needle);
                row.hidden = !matches;
                if (matches) {
                    visible += 1;
                }
            });

            if (recordCount) {
                recordCount.textContent = String(visible);
            }
            if (filterEmpty) {
                filterEmpty.hidden = visible !== 0;
            }
        });
    }

    const birthInput = document.querySelector('[data-birth-date]');
    const ageOutput = document.querySelector('[data-age-output]');

    const updateAge = () => {
        if (!birthInput || !ageOutput) {
            return;
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(birthInput.value)) {
            ageOutput.value = '';
            return;
        }

        const [year, month, day] = birthInput.value.split('-').map(Number);
        const today = new Date();
        let age = today.getFullYear() - year;
        const hasHadBirthday = today.getMonth() + 1 > month || (today.getMonth() + 1 === month && today.getDate() >= day);

        if (!hasHadBirthday) {
            age -= 1;
        }

        ageOutput.value = age >= 0 ? String(age) : '';
    };

    if (birthInput) {
        birthInput.addEventListener('change', updateAge);
        birthInput.addEventListener('input', updateAge);
        updateAge();
    }

    const governorType = document.querySelector('[data-governor-type]');
    const governorTargets = [...document.querySelectorAll('[data-governor-target]')];

    const updateGovernorTarget = () => {
        if (!governorType) {
            return;
        }

        governorTargets.forEach((container) => {
            const active = container.dataset.governorTarget === governorType.value;
            const select = container.querySelector('select');
            container.hidden = !active;

            if (select) {
                select.disabled = !active;
                select.required = active;
            }
        });
    };

    if (governorType) {
        governorType.addEventListener('change', updateGovernorTarget);
        updateGovernorTarget();
    }

    const setFieldValidity = (field, message) => {
        field.setCustomValidity(message || '');
        const wrapper = field.closest('.field');
        if (wrapper) {
            wrapper.classList.toggle('has-error', Boolean(message));
        }
    };

    const validateCustomFields = (form) => {
        let firstInvalid = null;
        const today = new Date().toISOString().slice(0, 10);

        form.querySelectorAll('[data-positive-number]').forEach((field) => {
            const number = Number(field.value);
            const message = field.value !== '' && (!Number.isFinite(number) || number < 0)
                ? 'Informe um número igual ou maior que zero.'
                : '';
            setFieldValidity(field, message);
            if (message && !firstInvalid) firstInvalid = field;
        });

        form.querySelectorAll('[data-not-future]').forEach((field) => {
            const message = field.value && field.value > today ? 'Esta data não pode estar no futuro.' : '';
            setFieldValidity(field, message);
            if (message && !firstInvalid) firstInvalid = field;
        });

        const mandateStart = form.querySelector('[data-mandate-start]');
        const mandateEnd = form.querySelector('[data-mandate-end]');
        if (mandateStart && mandateEnd) {
            const message = mandateStart.value && mandateEnd.value && mandateEnd.value < mandateStart.value
                ? 'O fim do mandato não pode ser anterior ao início.'
                : '';
            setFieldValidity(mandateEnd, message);
            if (message && !firstInvalid) firstInvalid = mandateEnd;
        }

        return firstInvalid;
    };

    document.querySelectorAll('[data-validate-form]').forEach((form) => {
        form.querySelectorAll('input, select').forEach((field) => {
            field.addEventListener('input', () => validateCustomFields(form));
            field.addEventListener('change', () => validateCustomFields(form));
        });

        form.addEventListener('submit', (event) => {
            const customInvalid = validateCustomFields(form);
            if (customInvalid) {
                event.preventDefault();
                customInvalid.reportValidity();
                customInvalid.focus();
            }
        });
    });

    const errorSummary = document.querySelector('[data-error-summary]');
    if (errorSummary) {
        errorSummary.focus();
    }

    const globalSearch = document.querySelector('[data-global-search]');
    const searchInput = document.querySelector('[data-global-search-input]');
    const resultsBox = document.querySelector('[data-search-results]');
    let debounceTimer = null;
    let requestController = null;
    let activeResult = -1;

    const resultLinks = () => resultsBox ? [...resultsBox.querySelectorAll('.search-result-link')] : [];

    const setActiveResult = (index) => {
        const links = resultLinks();
        if (!links.length) return;
        activeResult = (index + links.length) % links.length;
        links.forEach((link, linkIndex) => link.classList.toggle('is-active', linkIndex === activeResult));
        links[activeResult].scrollIntoView({ block: 'nearest' });
    };

    const hideResults = () => {
        if (resultsBox) {
            resultsBox.hidden = true;
            resultsBox.replaceChildren();
        }
        activeResult = -1;
    };

    const createSearchGroup = (title, items) => {
        const group = document.createElement('section');
        group.className = 'search-result-group';
        const heading = document.createElement('h3');
        heading.textContent = title;
        group.append(heading);

        items.forEach((item) => {
            const link = document.createElement('a');
            link.className = 'search-result-link';
            link.href = item.url;
            const name = document.createElement('strong');
            name.textContent = item.nome;
            const context = document.createElement('span');
            context.textContent = item.contexto;
            link.append(name, context);
            group.append(link);
        });

        return group;
    };

    const renderSearchResults = (data) => {
        if (!resultsBox) return;
        resultsBox.replaceChildren();
        const countries = Array.isArray(data.paises) ? data.paises : [];
        const cities = Array.isArray(data.cidades) ? data.cidades : [];

        if (!countries.length && !cities.length) {
            const empty = document.createElement('span');
            empty.className = 'search-empty';
            empty.textContent = 'Nenhum país ou cidade encontrado.';
            resultsBox.append(empty);
        } else {
            if (countries.length) resultsBox.append(createSearchGroup('Países', countries));
            if (cities.length) resultsBox.append(createSearchGroup('Cidades', cities));
        }

        resultsBox.hidden = false;
        activeResult = -1;
    };

    if (globalSearch && searchInput && resultsBox) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();
            window.clearTimeout(debounceTimer);

            if (query.length < 2) {
                if (requestController) requestController.abort();
                hideResults();
                return;
            }

            debounceTimer = window.setTimeout(async () => {
                if (requestController) requestController.abort();
                requestController = new AbortController();
                const endpoint = new URL(globalSearch.action, window.location.origin);
                endpoint.searchParams.set('q', query);
                endpoint.searchParams.set('formato', 'json');

                try {
                    const response = await fetch(endpoint.toString(), { signal: requestController.signal });
                    if (!response.ok) throw new Error('Busca indisponível');
                    renderSearchResults(await response.json());
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        hideResults();
                    }
                }
            }, 280);
        });

        searchInput.addEventListener('keydown', (event) => {
            const links = resultLinks();
            if (event.key === 'ArrowDown' && links.length) {
                event.preventDefault();
                setActiveResult(activeResult + 1);
            } else if (event.key === 'ArrowUp' && links.length) {
                event.preventDefault();
                setActiveResult(activeResult - 1);
            } else if (event.key === 'Enter' && activeResult >= 0 && links[activeResult]) {
                event.preventDefault();
                links[activeResult].click();
            } else if (event.key === 'Escape') {
                hideResults();
                searchInput.blur();
            }
        });

        document.addEventListener('click', (event) => {
            if (!globalSearch.contains(event.target)) hideResults();
        });
    }
})();
