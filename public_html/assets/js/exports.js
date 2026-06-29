function selectFormat(fmt, el) {
    document.getElementById('formatInput').value = fmt;
    document.getElementById('selectedFormat').textContent = fmt.toUpperCase();
    document.querySelectorAll('.format-option').forEach(function (o) { o.classList.remove('selected'); });
    el.classList.add('selected');
}

function toggleGroup(btn, check) {
    var section = btn.closest('.export-section');
    section.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = check; });
    updateCount();
}

function updateCount() {
    var count = document.querySelectorAll('input[name="fields[]"]:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

document.querySelectorAll('input[name="fields[]"]').forEach(function (cb) {
    cb.addEventListener('change', updateCount);
});

updateCount();
