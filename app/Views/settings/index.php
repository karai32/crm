<?php
$savedPerPage   = (int) ($prefs['per_page'] ?? 20);
$perPageOptions = [20, 50, 100, 200];
?>

<div class="page-header settings-header">
    <div>
        <h1>Settings</h1>
        <span class="count-label">Your personal preferences</span>
    </div>
</div>

<div class="settings-form-card">
    <form method="post" action="<?= htmlspecialchars(Auth::url('/settings/update'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="settings-form-body">
            <div class="field">
                <label for="per_page">Records per page</label>
                <select id="per_page" name="per_page" class="settings-select-sm">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $savedPerPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="field-hint">Number of rows displayed per page in all tables.</span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>
</div>

<div class="settings-form-card">
    <div class="settings-form-body">
        <div class="field">
            <label>Email validation</label>
            <span class="field-hint">
                Checks all contacts that haven't been validated yet — detects corporate vs. consumer email and validates the domain MX record. Safe to run on large databases: processed in batches of 50.
            </span>
        </div>
        <div class="inspect-progress" id="inspectProgress">
            <div class="inspect-bar-track">
                <div class="inspect-bar-fill" id="inspectBarFill"></div>
            </div>
            <span class="inspect-status" id="inspectStatus">Starting…</span>
        </div>
    </div>
    <div class="settings-form-actions">
        <button type="button" class="btn btn-primary" id="inspectBtn"
                data-url="<?= htmlspecialchars(Auth::url('/ajax/contacts/inspect-email-batch'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-envelope-simple-open"></i>
            Run email validation
        </button>
    </div>
</div>

<script>
(function () {
    var btn      = document.getElementById('inspectBtn');
    var progress = document.getElementById('inspectProgress');
    var barFill  = document.getElementById('inspectBarFill');
    var status   = document.getElementById('inspectStatus');
    var url      = btn.dataset.url;

    var totalInitial   = null;
    var totalProcessed = 0;
    var running        = false;

    function setProgress(pct, text) {
        barFill.style.width = Math.min(100, pct) + '%';
        status.textContent  = text;
    }

    function runBatch() {
        fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok) {
                    return r.text().then(function (t) { throw new Error('HTTP ' + r.status + ': ' + t.slice(0, 120)); });
                }
                return r.json();
            })
            .then(function (data) {
                if (data.error) {
                    setProgress(0, '✗ Server error: ' + data.error.slice(0, 120));
                    finish(true);
                    return;
                }
                totalProcessed += data.processed;

                if (totalInitial === null) {
                    totalInitial = data.processed + data.remaining;
                }

                if (totalInitial === 0) {
                    setProgress(100, 'No contacts need validation.');
                    finish(false);
                    return;
                }

                var pct  = Math.round((totalProcessed / totalInitial) * 100);
                var left = data.remaining;
                setProgress(pct, 'Processing… ' + totalProcessed + ' / ' + totalInitial + ' contacts (' + pct + '%)');

                if (data.done) {
                    setProgress(100, '✓ Done — ' + totalProcessed + ' contacts validated.');
                    finish(false);
                } else {
                    setTimeout(runBatch, 150);
                }
            })
            .catch(function (err) {
                setProgress(0, '✗ Error: ' + err.message + '. Reload and try again.');
                finish(true);
            });
    }

    function finish(isError) {
        running = false;
        btn.disabled = false;
        btn.textContent = isError ? 'Retry' : 'Run email validation';
        if (!isError) btn.insertAdjacentHTML('afterbegin', '<i class="ph ph-envelope-simple-open"></i> ');
    }

    btn.addEventListener('click', function () {
        if (running) { return; }
        running        = true;
        totalInitial   = null;
        totalProcessed = 0;
        btn.disabled   = true;
        btn.textContent = 'Running…';
        progress.classList.add('visible');
        setProgress(0, 'Starting…');
        runBatch();
    });
}());
</script>