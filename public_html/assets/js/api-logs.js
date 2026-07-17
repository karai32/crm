(function () {
    document.querySelectorAll('.api-log-expand-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var detailId = btn.closest('tr').dataset.detail;
            var detailRow = document.getElementById(detailId);
            var open = detailRow.hidden;
            detailRow.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('is-open', open);
        });
    });
})();
