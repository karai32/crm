(function () {
    document.querySelectorAll('[data-custom-mapping-select]').forEach(function (select) {
        var targetId = select.getAttribute('data-custom-target');
        function toggle() {
            var cell = document.getElementById(targetId);
            if (!cell) {
                return;
            }
            if (select.value === '__custom') {
                cell.classList.add('visible');
            } else {
                cell.classList.remove('visible');
            }
        }
        select.addEventListener('change', toggle);
        toggle();
    });
})();
