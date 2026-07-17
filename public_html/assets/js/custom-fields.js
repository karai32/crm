(function () {
    var typeSelect = document.getElementById('field_type');
    var optionsField = document.getElementById('options-field');
    if (!typeSelect || !optionsField) {
        return;
    }
    function toggle() {
        optionsField.style.display = typeSelect.value === 'select' ? '' : 'none';
    }
    typeSelect.addEventListener('change', toggle);
    toggle();
})();
