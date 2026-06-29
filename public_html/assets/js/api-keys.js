function copyCredential(elementId, button, originalLabel) {
    navigator.clipboard.writeText(document.getElementById(elementId).textContent).then(function () {
        button.textContent = 'Copied!';
        setTimeout(function () { button.textContent = originalLabel; }, 2000);
    });
}

function apiKeyRenameStart(id, currentName) {
    document.getElementById('nameDisplay' + id).style.display = 'none';
    var form  = document.getElementById('renameForm' + id);
    var input = form.querySelector('input[name="name"]');
    input.value = currentName;
    form.style.display = 'flex';
    input.focus();
    input.select();
}

function apiKeyRenameCancel(id) {
    document.getElementById('renameForm' + id).style.display = 'none';
    document.getElementById('nameDisplay' + id).style.display = '';
}
