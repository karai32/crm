(function () {
    var textInput = document.getElementById('color');
    var picker = document.getElementById('color-picker');
    if (!textInput || !picker) {
        return;
    }
    var hexRe = /^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/;
    textInput.addEventListener('input', function () {
        if (hexRe.test(textInput.value.trim())) {
            picker.value = textInput.value.trim();
        }
    });
    picker.addEventListener('input', function () {
        textInput.value = picker.value;
    });
})();
