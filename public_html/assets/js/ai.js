(function () {
    var card = document.getElementById('aiCompaniesTable');
    if (!card) {
        return;
    }

    var endpoint = card.dataset.endpoint;
    var applyEndpoint = card.dataset.applyEndpoint;
    var skipEndpoint = card.dataset.skipEndpoint;
    var csrfToken = card.dataset.csrfToken;
    var emptyError = card.dataset.emptyError;
    var emptyMessage = card.dataset.emptyMessage;

    card.addEventListener('click', function (event) {
        var skipBtn = event.target.closest('.ai-skip-btn');
        if (skipBtn && !skipBtn.disabled) {
            handleSkip(skipBtn);
            return;
        }

        var applyBtn = event.target.closest('.ai-apply-btn');
        if (applyBtn && !applyBtn.disabled) {
            handleApply(applyBtn);
            return;
        }

        var btn = event.target.closest('.ai-process-btn');
        if (!btn || btn.disabled) {
            return;
        }

        var contactId = btn.dataset.contactId;
        var input = card.querySelector('.ai-company-input[data-contact-id="' + contactId + '"]');
        var icon = btn.querySelector('i');

        btn.disabled = true;
        icon.className = 'ph ph-circle-notch ai-spin';

        var body = new URLSearchParams({
            contact_id: contactId,
            _csrf_token: csrfToken,
        });

        fetch(endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.error || 'AI request failed');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (input) {
                    input.value = (data.answer || '').trim();
                }
            })
            .catch(function (error) {
                if (input) {
                    input.value = '';
                    input.placeholder = error.message;
                }
            })
            .finally(function () {
                btn.disabled = false;
                icon.className = 'ph ph-sparkle';
            });
    });

    card.addEventListener('input', function (event) {
        if (event.target.closest('.ai-company-input')) {
            event.target.classList.remove('is-invalid', 'is-saved');
        }
    });

    function handleApply(btn) {
        var contactId = btn.dataset.contactId;
        var input = card.querySelector('.ai-company-input[data-contact-id="' + contactId + '"]');
        if (!input) {
            return;
        }

        var company = input.value.trim();
        var icon = btn.querySelector('i');

        input.classList.remove('is-invalid', 'is-saved');

        if (company === '') {
            input.classList.add('is-invalid');
            input.placeholder = emptyError;
            return;
        }

        btn.disabled = true;
        icon.className = 'ph ph-circle-notch ai-spin';

        var body = new URLSearchParams({
            contact_id: contactId,
            company: company,
            _csrf_token: csrfToken,
        });

        fetch(applyEndpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.error || 'Save failed');
                    }
                    return data;
                });
            })
            .then(function () {
                input.classList.add('is-saved');
                setTimeout(function () {
                    removeRow(contactId);
                }, 500);
            })
            .catch(function (error) {
                input.classList.add('is-invalid');
                input.placeholder = error.message;
            })
            .finally(function () {
                btn.disabled = false;
                icon.className = 'ph ph-check';
            });
    }

    function handleSkip(btn) {
        var contactId = btn.dataset.contactId;
        var icon = btn.querySelector('i');

        btn.disabled = true;
        icon.className = 'ph ph-circle-notch ai-spin';

        var body = new URLSearchParams({
            contact_id: contactId,
            _csrf_token: csrfToken,
        });

        fetch(skipEndpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.error || 'Request failed');
                    }
                    return data;
                });
            })
            .then(function () {
                removeRow(contactId);
            })
            .catch(function (error) {
                btn.disabled = false;
                icon.className = 'ph ph-x';
                var input = card.querySelector('.ai-company-input[data-contact-id="' + contactId + '"]');
                if (input) {
                    input.classList.add('is-invalid');
                    input.placeholder = error.message;
                }
            });
    }

    function parseCount(text) {
        return Math.max(0, parseInt(text.replace(/\D/g, ''), 10) || 0);
    }

    function formatCount(n) {
        return String(Math.max(0, n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function removeRow(contactId) {
        var row = card.querySelector('tr[data-row-id="' + contactId + '"]');
        if (!row) {
            return;
        }

        var domain = row.dataset.rowDomain;
        row.parentNode.removeChild(row);

        var statValues = document.querySelectorAll('.ai-stat-value');
        if (statValues[0]) {
            statValues[0].textContent = formatCount(parseCount(statValues[0].textContent) - 1);
        }
        if (statValues[1] && domain && !card.querySelector('tr[data-row-domain="' + domain + '"]')) {
            statValues[1].textContent = formatCount(parseCount(statValues[1].textContent) - 1);
        }

        var tbody = card.querySelector('tbody');
        if (tbody && tbody.children.length === 0) {
            var table = card.querySelector('table');
            var empty = document.createElement('p');
            empty.className = 'table-empty-state';
            empty.textContent = emptyMessage;
            table.parentNode.replaceChild(empty, table);
        }
    }
})();
