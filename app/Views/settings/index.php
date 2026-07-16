<?php
$savedPerPage   = (int) ($prefs['per_page'] ?? 20);
$perPageOptions = [20, 50, 100, 200];
?>

<div class="page-header settings-header">
    <div>
        <h1><?= t('settings.title') ?></h1>
        <span class="count-label"><?= t('settings.subtitle') ?></span>
    </div>
</div>

<div class="settings-form-card">
    <form method="post" action="<?= htmlspecialchars(Auth::url('/settings/update'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="settings-form-body">
            <div class="field">
                <label for="per_page"><?= t('settings.per_page') ?></label>
                <select id="per_page" name="per_page" class="settings-select-sm">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $savedPerPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="field-hint"><?= t('settings.per_page_hint') ?></span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button type="submit" class="btn btn-primary"><?= t('settings.save') ?></button>
        </div>
    </form>
</div>

<?php if (Auth::isAdmin()): ?>

<div class="settings-form-card">
    <div class="settings-form-body">
        <div class="field">
            <label><?= t('settings.email_validation') ?></label>
            <span class="field-hint"><?= t('settings.email_val_hint') ?></span>
        </div>
        <div class="inspect-progress" id="inspectProgress">
            <div class="inspect-bar-track">
                <div class="inspect-bar-fill" id="inspectBarFill"></div>
            </div>
            <span class="inspect-status" id="inspectStatus"><?= t('settings.val_starting') ?></span>
        </div>
    </div>
    <div class="settings-form-actions">
        <button type="button" class="btn btn-primary" id="inspectBtn"
                data-url="<?= htmlspecialchars(Auth::url('/ajax/contacts/inspect-email-batch'), ENT_QUOTES, 'UTF-8') ?>"
                data-csrf-token="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-envelope-simple-open"></i>
            <?= t('settings.run_validation') ?>
        </button>
    </div>
</div>

<div class="settings-form-card">
    <div class="settings-form-body">
        <div class="field">
            <label><?= t('settings.weekly_report') ?></label>
            <span class="field-hint"><?= t('settings.weekly_report_hint') ?></span>
        </div>
        <?php if (($reportStatus ?? null) === 'sent'): ?>
            <div class="settings-notice settings-notice--success">
                <i class="ph ph-check-circle"></i>
                <?= htmlspecialchars(Lang::get('settings.report_sent', ['email' => Auth::user()['email']]), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php elseif (($reportStatus ?? null) === 'error'): ?>
            <div class="settings-notice settings-notice--error">
                <i class="ph ph-warning-circle"></i>
                <?= t('settings.report_error') ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="settings-form-actions">
        <form method="post" action="<?= htmlspecialchars(Auth::url('/settings/send-report'), ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-secondary">
                <i class="ph ph-paper-plane-tilt"></i>
                <?= t('settings.send_report') ?>
            </button>
        </form>
    </div>
</div>

<div class="settings-form-card">
    <div class="settings-form-body">
        <div class="field">
            <label><?= t('settings.language') ?></label>
            <span class="field-hint"><?= t('settings.language_hint') ?></span>
        </div>
        <div class="settings-lang-buttons">
            <?php foreach (['es' => 'Español', 'en' => 'English', 'ru' => 'Русский'] as $code => $label): ?>
                <form method="post" action="<?= htmlspecialchars(Auth::url('/lang/switch'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="lang" value="<?= $code ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars(Auth::url('/settings'), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn <?= Lang::locale() === $code ? 'btn-primary' : 'btn-outlined' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var btn      = document.getElementById('inspectBtn');
    var progress = document.getElementById('inspectProgress');
    var barFill  = document.getElementById('inspectBarFill');
    var status   = document.getElementById('inspectStatus');
    var url      = btn.dataset.url;
    var csrfToken = btn.dataset.csrfToken;
    var i18n     = <?= json_encode([
        'starting'     => Lang::get('settings.val_starting'),
        'running'      => Lang::get('settings.val_running'),
        'none'         => Lang::get('settings.val_none'),
        'processing'   => Lang::get('settings.val_processing'),
        'done'         => Lang::get('settings.val_done'),
        'server_error' => Lang::get('settings.val_server_error'),
        'error_reload' => Lang::get('settings.val_error_reload'),
        'retry'        => Lang::get('common.retry'),
        'run'          => Lang::get('settings.run_validation'),
    ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;

    var totalInitial   = null;
    var totalProcessed = 0;
    var running        = false;

    function setProgress(pct, text) {
        barFill.style.width = Math.min(100, pct) + '%';
        status.textContent  = text;
    }

    function runBatch() {
        var body = new URLSearchParams({ _csrf_token: csrfToken });
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
            .then(function (r) {
                if (!r.ok) {
                    return r.text().then(function (t) { throw new Error('HTTP ' + r.status + ': ' + t.slice(0, 120)); });
                }
                return r.json();
            })
            .then(function (data) {
                if (data.error) {
                    setProgress(0, i18n.server_error.replace(':error', data.error.slice(0, 120)));
                    finish(true);
                    return;
                }
                totalProcessed += data.processed;

                if (totalInitial === null) {
                    totalInitial = data.processed + data.remaining;
                }

                if (totalInitial === 0) {
                    setProgress(100, i18n.none);
                    finish(false);
                    return;
                }

                var pct  = Math.round((totalProcessed / totalInitial) * 100);
                setProgress(pct, i18n.processing.replace(':n', totalProcessed).replace(':total', totalInitial).replace(':pct', pct));

                if (data.done) {
                    setProgress(100, i18n.done.replace(':n', totalProcessed));
                    finish(false);
                } else {
                    setTimeout(runBatch, 150);
                }
            })
            .catch(function (err) {
                setProgress(0, i18n.error_reload.replace(':error', err.message));
                finish(true);
            });
    }

    function finish(isError) {
        running = false;
        btn.disabled = false;
        btn.textContent = isError ? i18n.retry : i18n.run;
        if (!isError) btn.insertAdjacentHTML('afterbegin', '<i class="ph ph-envelope-simple-open"></i> ');
    }

    btn.addEventListener('click', function () {
        if (running) { return; }
        running        = true;
        totalInitial   = null;
        totalProcessed = 0;
        btn.disabled   = true;
        btn.textContent = i18n.running;
        progress.classList.add('visible');
        setProgress(0, i18n.starting);
        runBatch();
    });
}());
</script>

<?php endif; ?>
