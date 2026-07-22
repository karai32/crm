(function () {
    var card = document.getElementById('aiClientsTable');
    if (!card) {
        return;
    }

    var endpoint = card.dataset.endpoint;
    var applyEndpoint = card.dataset.applyEndpoint;
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
            removeRow(skipBtn.dataset.clientId);
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

        processRow(btn.dataset.clientId);
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

    card.addEventListener('input', function (event) {
        if (event.target.closest('.ai-company-input')) {
            event.target.classList.remove('is-invalid', 'is-saved');
        }
    });

    function processRow(clientId) {
        var btn = card.querySelector('.ai-process-btn[data-client-id="' + clientId + '"]');
        var websiteInput = card.querySelector('.ai-client-website[data-client-id="' + clientId + '"]');
        var sectorInput = card.querySelector('.ai-client-sector[data-client-id="' + clientId + '"]');
        if (!btn || btn.disabled) {
            return Promise.resolve();
        }
        var icon = btn.querySelector('i');

        btn.disabled = true;
        icon.className = 'ph ph-circle-notch ai-spin';

        var body = new URLSearchParams({
            client_id: clientId,
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
                if (websiteInput && data.website) {
                    websiteInput.value = data.website;
                }
                if (sectorInput && data.sector) {
                    sectorInput.value = data.sector;
                }
                logResult(clientId, data);
            })
            .catch(function (error) {
                if (websiteInput) {
                    websiteInput.placeholder = error.message;
                }
                console.error('[AI clients] #' + clientId + ' request failed:', error.message);
            })
            .finally(function () {
                btn.disabled = false;
                icon.className = 'ph ph-sparkle';
            });
    }

    function logResult(clientId, data) {
        var row = card.querySelector('tr[data-row-id="' + clientId + '"]');
        var nameEl = row && row.querySelector('.col-name');
        var name = nameEl ? nameEl.textContent.trim() : '';
        var found = !!(data.website || data.sector);

        console.log(
            '[AI clients] #' + clientId + ' ' + name + ' — ' + (found ? 'found' : 'nothing found'),
            { website: data.website || null, sector: data.sector || null, answer: data.answer || null }
        );
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

        var clientId = ids[index];
        var row = card.querySelector('tr[data-row-id="' + clientId + '"]');

        var step = !row
            ? Promise.resolve()
            : waitForAutoSlot().then(function () {
                if (autoCancelled) {
                    return;
                }
                lastRequestAt = Date.now();
                requestTimestamps.push(lastRequestAt);
                return processRow(clientId);
            });

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

    function handleApply(btn) {
        var clientId = btn.dataset.clientId;
        var websiteInput = card.querySelector('.ai-client-website[data-client-id="' + clientId + '"]');
        var sectorInput = card.querySelector('.ai-client-sector[data-client-id="' + clientId + '"]');
        if (!websiteInput || !sectorInput) {
            return;
        }

        var website = websiteInput.value.trim();
        var sector = sectorInput.value.trim();
        var icon = btn.querySelector('i');

        websiteInput.classList.remove('is-invalid', 'is-saved');
        sectorInput.classList.remove('is-invalid', 'is-saved');

        if (website === '' && sector === '') {
            websiteInput.classList.add('is-invalid');
            sectorInput.classList.add('is-invalid');
            websiteInput.placeholder = emptyError;
            return;
        }

        btn.disabled = true;
        icon.className = 'ph ph-circle-notch ai-spin';

        var body = new URLSearchParams({
            client_id: clientId,
            website: website,
            sector: sector,
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
                websiteInput.classList.add('is-saved');
                sectorInput.classList.add('is-saved');
                setTimeout(function () {
                    removeRow(clientId);
                }, 500);
            })
            .catch(function (error) {
                websiteInput.classList.add('is-invalid');
                websiteInput.placeholder = error.message;
            })
            .finally(function () {
                btn.disabled = false;
                icon.className = 'ph ph-check';
            });
    }

    function parseCount(text) {
        return Math.max(0, parseInt(text.replace(/\D/g, ''), 10) || 0);
    }

    function formatCount(n) {
        return String(Math.max(0, n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function removeRow(clientId) {
        var row = card.querySelector('tr[data-row-id="' + clientId + '"]');
        if (!row) {
            return;
        }

        row.parentNode.removeChild(row);

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
