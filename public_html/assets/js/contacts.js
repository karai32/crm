(function () {
    var geminiCompanyTest = document.getElementById('geminiCompanyTest');
    if (!geminiCompanyTest) { return; }

    geminiCompanyTest.addEventListener('click', function () {
        var originalHtml = geminiCompanyTest.innerHTML;
        var body = new URLSearchParams({
            contact_id: geminiCompanyTest.dataset.contactId,
            _csrf_token: geminiCompanyTest.dataset.csrfToken
        });

        geminiCompanyTest.disabled = true;
        geminiCompanyTest.textContent = 'Gemini...';

        fetch(geminiCompanyTest.dataset.endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.error || 'Gemini request failed');
                    }
                    return data;
                });
            })
            .then(function (data) { console.log('[Gemini company test]', data); })
            .catch(function (error) { console.error('[Gemini company test]', error); })
            .finally(function () {
                geminiCompanyTest.disabled = false;
                geminiCompanyTest.innerHTML = originalHtml;
            });
    });
}());
