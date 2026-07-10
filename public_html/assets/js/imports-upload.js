(function () {
    var select = document.getElementById('entity_type');
    var links = document.querySelectorAll('.import-template-link');
    if (!select || !links.length) { return; }
    select.addEventListener('change', function () {
        var entity = select.value === 'clients' ? 'clients' : 'contacts';
        links.forEach(function (link) {
            link.href = link.dataset.baseHref + entity + '-import-template.' + link.dataset.ext;
        });
    });
}());
