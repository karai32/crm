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

    var autoBtn = document.getElementById('aiAutoBtn');
    var autoRunning = false;
    var autoCancelled = false;
    var requestTimestamps = [];
    var lastRequestAt = 0;
    var AUTO_MIN_GAP_MS = 5000;
    var AUTO_MAX_PER_WINDOW = 12;
    var AUTO_WINDOW_MS = 60000;

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

        processRow(btn.dataset.contactId);
    });

    if (autoBtn) {
        autoBtn.addEventListener('click', function () {
            if (autoRunning) {
                autoCancelled = true;
                return;
            }
            startAuto();
        });
    }

    function processRow(contactId) {
        var btn = card.querySelector('.ai-process-btn[data-contact-id="' + contactId + '"]');
        var input = card.querySelector('.ai-company-input[data-contact-id="' + contactId + '"]');
        if (!btn || btn.disabled) {
            return Promise.resolve();
        }
        var icon = btn.querySelector('i');

        btn.disabled = true;
        icon.className = 'ph ph-circle-notch ai-spin';

        var body = new URLSearchParams({
            contact_id: contactId,
            _csrf_token: csrfToken,
        });

        return fetch(endpoint, {
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
    }

    function waitForAutoSlot() {
        return new Promise(function (resolve) {
            (function attempt() {
                var now = Date.now();
                requestTimestamps = requestTimestamps.filter(function (t) {
                    return now - t < AUTO_WINDOW_MS;
                });

                var wait = lastRequestAt ? Math.max(0, AUTO_MIN_GAP_MS - (now - lastRequestAt)) : 0;
                if (requestTimestamps.length >= AUTO_MAX_PER_WINDOW) {
                    wait = Math.max(wait, AUTO_WINDOW_MS - (now - requestTimestamps[0]));
                }

                if (wait <= 0) {
                    resolve();
                } else {
                    setTimeout(attempt, wait);
                }
            })();
        });
    }

    function startAuto() {
        var ids = Array.prototype.map.call(
            card.querySelectorAll('tbody tr[data-row-id]'),
            function (row) {
                return row.dataset.rowId;
            }
        );

        if (!ids.length) {
            return;
        }

        autoRunning = true;
        autoCancelled = false;
        requestTimestamps = [];
        lastRequestAt = 0;
        setAutoBtnState(true);

        autoRunNext(ids, 0);
    }

    function autoRunNext(ids, index) {
        if (autoCancelled || index >= ids.length) {
            autoRunning = false;
            setAutoBtnState(false);
            return;
        }

        var contactId = ids[index];
        var row = card.querySelector('tr[data-row-id="' + contactId + '"]');
        var input = card.querySelector('.ai-company-input[data-contact-id="' + contactId + '"]');

        var step;
        if (!row || !input || input.value.trim() !== '') {
            step = Promise.resolve();
        } else {
            step = waitForAutoSlot().then(function () {
                if (autoCancelled) {
                    return;
                }
                lastRequestAt = Date.now();
                requestTimestamps.push(lastRequestAt);
                return processRow(contactId);
            });
        }

        step.then(function () {
            autoRunNext(ids, index + 1);
        });
    }

    function setAutoBtnState(running) {
        if (!autoBtn) {
            return;
        }
        autoBtn.classList.toggle('btn-primary', !running);
        autoBtn.classList.toggle('btn-danger', running);
        autoBtn.querySelector('.ai-auto-label').textContent = running
            ? autoBtn.dataset.labelStop
            : autoBtn.dataset.labelStart;
        autoBtn.querySelector('i').className = running ? 'ph ph-stop' : 'ph ph-play';
    }

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
