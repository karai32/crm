(function () {
    var selectAll = document.querySelector('[data-list-select-all]');
    var checkboxName = selectAll ? selectAll.dataset.checkboxName : '';
    var checkboxSelector = checkboxName ? 'input[name="' + checkboxName + '"]' : '';
    var filterBarBtn  = document.getElementById('filterBarBtn');
    var filterPanel   = document.getElementById('filterPanel');
    var actionsBarBtn = document.getElementById('actionsBarBtn');
    var actionsPanel  = document.getElementById('actionsPanel');
    var actionsCount  = document.getElementById('actionsBarCount');
    var deleteCountEl = document.getElementById('deleteCountLabel');
    var hintEl        = document.getElementById('actionsSelectedHint');
    var deselectBtn   = document.getElementById('actionsDeselectBtn');
    var moreBtn       = document.querySelector('[data-list-more-toggle]');
    var moreExtra     = document.querySelector('[data-list-more-panel]');

    function checkedCount() {
        return checkboxSelector ? document.querySelectorAll(checkboxSelector + ':checked').length : 0;
    }

    function updateActions() {
        var count = checkedCount();
        var total = checkboxSelector ? document.querySelectorAll(checkboxSelector).length : 0;
        var i18n = window.I18N || {};

        if (actionsCount) {
            actionsCount.textContent = count;
            actionsCount.style.display = count > 0 ? '' : 'none';
        }
        if (deleteCountEl) {
            var deleteButton = deleteCountEl.closest('button');
            var deleteTemplate = deleteButton ? deleteButton.dataset.template : null;
            deleteCountEl.textContent = (deleteTemplate || i18n.delete_selected || ':n selected').replace(':n', count);
        }
        if (hintEl) {
            hintEl.textContent = (hintEl.dataset.template || i18n.n_selected || ':n selected').replace(':n', count);
        }
        if (actionsBarBtn) {
            actionsBarBtn.classList.toggle('actions-bar-btn--has-items', count > 0);
        }

        if (selectAll) {
            selectAll.indeterminate = count > 0 && count < total;
            selectAll.checked = total > 0 && count === total;
        }

        if (count === 0 && actionsPanel) {
            actionsPanel.classList.remove('open');
            if (actionsBarBtn) { actionsBarBtn.classList.remove('active'); }
        }
    }

    if (filterBarBtn && filterPanel) {
        filterBarBtn.addEventListener('click', function () {
            var open = filterPanel.classList.toggle('open');
            filterBarBtn.classList.toggle('active', open);
            if (open && actionsPanel) {
                actionsPanel.classList.remove('open');
                if (actionsBarBtn) { actionsBarBtn.classList.remove('active'); }
            }
        });
    }

    if (moreBtn && moreExtra) {
        moreBtn.addEventListener('click', function () {
            var open = moreExtra.classList.toggle('open');
            var i18n = window.I18N || {};
            var label = moreBtn.querySelector('.filter-toggle-label');
            moreBtn.classList.toggle('open', open);
            if (label) {
                label.textContent = open
                    ? (i18n.less_filters || 'Less filters')
                    : (i18n.more_filters || 'More filters');
            }
        });
    }

    if (actionsBarBtn && actionsPanel) {
        actionsBarBtn.addEventListener('click', function () {
            if (checkedCount() === 0) { return; }
            var open = actionsPanel.classList.toggle('open');
            actionsBarBtn.classList.toggle('active', open);
            if (open && filterPanel) {
                filterPanel.classList.remove('open');
                if (filterBarBtn) { filterBarBtn.classList.remove('active'); }
            }
        });
    }

    document.addEventListener('change', function (event) {
        if (checkboxName && event.target.name === checkboxName) { updateActions(); }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll(checkboxSelector).forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateActions();
        });
    }

    if (deselectBtn && checkboxSelector) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll(checkboxSelector).forEach(function (checkbox) {
                checkbox.checked = false;
            });
            updateActions();
        });
    }
}());
