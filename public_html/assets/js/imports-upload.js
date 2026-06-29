(function () {
    var link   = document.getElementById('import-template-link');
    var select = document.getElementById('entity_type');
    if (!link || !select) { return; }
    var base = link.dataset.baseHref;
    select.addEventListener('change', function () {
        link.href = base + (select.value === 'clients' ? 'clients' : 'contacts');
    });
}());
