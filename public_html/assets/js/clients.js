(function () {
    var relatedToggle = document.getElementById('relatedContactsToggle');
    var relatedBody   = document.getElementById('relatedContactsBody');
    if (!relatedToggle || !relatedBody) { return; }

    relatedToggle.addEventListener('click', function () {
        if (relatedToggle.classList.contains('open')) {
            relatedBody.style.height = relatedBody.scrollHeight + 'px';
            relatedBody.offsetHeight;
            relatedBody.style.height = '0';
            relatedToggle.classList.remove('open');
            relatedToggle.setAttribute('aria-expanded', 'false');
            return;
        }

        relatedBody.style.height = relatedBody.scrollHeight + 'px';
        relatedToggle.classList.add('open');
        relatedToggle.setAttribute('aria-expanded', 'true');
        relatedBody.addEventListener('transitionend', function onEnd() {
            relatedBody.style.height = 'auto';
            relatedBody.removeEventListener('transitionend', onEnd);
        });
    });

    relatedToggle.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            relatedToggle.click();
        }
    });
}());
