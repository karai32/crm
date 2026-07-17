(function () {
    var drop = document.getElementById('importFileDrop');
    if (!drop) {
        return;
    }

    var input = drop.querySelector('input[type="file"]');
    var text = drop.querySelector('.import-file-drop-text');
    var placeholder = text.textContent;

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        text.textContent = file ? file.name : placeholder;
        drop.classList.toggle('has-file', !!file);
    });

    ['dragenter', 'dragover'].forEach(function (evt) {
        input.addEventListener(evt, function () {
            drop.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        input.addEventListener(evt, function () {
            drop.classList.remove('dragover');
        });
    });
})();
