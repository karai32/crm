(function () {
    var card = document.getElementById('aiCompaniesTable');
    if (!card) {
        return;
    }

    var endpoint = card.dataset.endpoint;
    var csrfToken = card.dataset.csrfToken;

    card.addEventListener('click', function (event) {
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
})();
