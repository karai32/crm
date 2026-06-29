(function () {
    var filterBarBtn  = document.getElementById('filterBarBtn');
    var filterPanel   = document.getElementById('filterPanel');
    var actionsBarBtn = document.getElementById('actionsBarBtn');
    var actionsPanel  = document.getElementById('actionsPanel');
    var actionsCount  = document.getElementById('actionsBarCount');
    var deleteCountEl = document.getElementById('deleteCountLabel');
    var hintEl        = document.getElementById('actionsSelectedHint');
    var deselectBtn   = document.getElementById('actionsDeselectBtn');
    var selectAll     = document.getElementById('contactsSelectAll');
    var moreBtn       = document.getElementById('filterToggleBtn');
    var moreExtra     = document.getElementById('filterExtra');

    if (filterBarBtn && filterPanel) {
        filterBarBtn.addEventListener('click', function () {
            var open = filterPanel.classList.toggle('open');
            filterBarBtn.classList.toggle('active', open);
            if (open && actionsPanel) {
                actionsPanel.classList.remove('open');
                if (actionsBarBtn) actionsBarBtn.classList.remove('active');
            }
        });
    }

    if (moreBtn && moreExtra) {
        moreBtn.addEventListener('click', function () {
            var open = moreExtra.classList.toggle('open');
            moreBtn.classList.toggle('open', open);
            moreBtn.querySelector('.filter-toggle-label').textContent = open ? 'Less filters' : 'More filters';
        });
    }

    if (actionsBarBtn && actionsPanel) {
        actionsBarBtn.addEventListener('click', function () {
            if (getCheckedCount() === 0) { return; }
            var open = actionsPanel.classList.toggle('open');
            actionsBarBtn.classList.toggle('active', open);
            if (open && filterPanel) {
                filterPanel.classList.remove('open');
                if (filterBarBtn) filterBarBtn.classList.remove('active');
            }
        });
    }

    function getCheckedCount() {
        return document.querySelectorAll('input[name="contact_ids[]"]:checked').length;
    }

    function updateActions() {
        var n     = getCheckedCount();
        var total = document.querySelectorAll('input[name="contact_ids[]"]').length;

        if (actionsCount)  { actionsCount.textContent = n; actionsCount.style.display = n > 0 ? '' : 'none'; }
        if (deleteCountEl) { deleteCountEl.textContent = n; }
        if (hintEl)        { hintEl.textContent = n + ' selected'; }
        if (actionsBarBtn) { actionsBarBtn.classList.toggle('actions-bar-btn--has-items', n > 0); }

        if (selectAll) {
            selectAll.indeterminate = n > 0 && n < total;
            selectAll.checked = total > 0 && n === total;
        }

        if (n === 0 && actionsPanel) {
            actionsPanel.classList.remove('open');
            if (actionsBarBtn) actionsBarBtn.classList.remove('active');
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target.name === 'contact_ids[]') { updateActions(); }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="contact_ids[]"]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateActions();
        });
    }

    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('input[name="contact_ids[]"]').forEach(function (cb) { cb.checked = false; });
            if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
            updateActions();
        });
    }
}());
