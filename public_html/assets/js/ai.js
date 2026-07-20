(function () {
    var card = document.getElementById('aiCompaniesTable');
    if (!card) {
        return;
    }

    var endpoint = card.dataset.endpoint;
    var applyEndpoint = card.dataset.applyEndpoint;
    var csrfToken = card.dataset.csrfToken;
    var emptyError = card.dataset.emptyError;

    card.addEventListener('click', function (event) {
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
})();
