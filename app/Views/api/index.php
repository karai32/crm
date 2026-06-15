<!-- Page header -->
<div class="page-header settings-header api-header">
    <div>
        <h1>API Credentials</h1>
        <span class="count-label"><?= count($apiKeys) ?> integration<?= count($apiKeys) !== 1 ? 's' : '' ?></span>
    </div>
    <a href="<?= htmlspecialchars(Auth::url('/help/api'), ENT_QUOTES, 'UTF-8') ?>" class="api-back-link">
        API Reference
    </a>
</div>

<?php if (!empty($newCredentials)): ?>
<?php
$clientId = (string) ($newCredentials['client_id'] ?? '');
$secret = (string) ($newCredentials['secret'] ?? '');
$basicAuth = $clientId . ':' . $secret;
?>
<div class="api-new-credentials">
    <div class="api-new-credentials-body">
        <div class="api-new-credentials-title">API credentials created - copy the secret now</div>
        <div class="api-new-credentials-subtitle">The secret will not be shown again. Use the Basic Auth value in Formidable Forms.</div>
        <div class="api-new-credentials-grid">
            <strong class="api-new-credentials-label">Client ID</strong>
            <code class="api-new-credentials-code"><?= htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?></code>
            <span></span>

            <strong class="api-new-credentials-label">Secret</strong>
            <code id="newSecretValue" class="api-new-credentials-code"><?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewSecret" class="api-new-credentials-copy"
                    onclick="copyCredential('newSecretValue', this, 'Copy secret')">Copy secret</button>

            <strong class="api-new-credentials-label">Basic Auth</strong>
            <code id="newBasicAuthValue" class="api-new-credentials-code"><?= htmlspecialchars($basicAuth, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewBasicAuth" class="api-new-credentials-copy"
                    onclick="copyCredential('newBasicAuthValue', this, 'Copy Basic Auth')">Copy Basic Auth</button>
        </div>
    </div>
</div>
<script>
function copyCredential(elementId, button, originalLabel) {
    navigator.clipboard.writeText(document.getElementById(elementId).textContent).then(function () {
        button.textContent = 'Copied!';
        setTimeout(function () { button.textContent = originalLabel; }, 2000);
    });
}
</script>
<?php endif; ?>

<div class="api-card">
    <div class="api-card-create">
        <div class="api-section-label">Create API credentials</div>
        <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/store'), ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <div class="api-create-row">
                <div class="field api-name-field">
                    <label for="keyName">
                        Integration name <span class="required-star">*</span>
                    </label>
                    <input id="keyName" type="text" name="name" placeholder="e.g. Website contact form" required class="api-name-input">
                </div>
                <button class="btn btn-primary api-submit-btn" type="submit">Create credentials</button>
            </div>
            <div class="api-scopes-row">
                <?php foreach (['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'] as $scope): ?>
                <span class="api-scope"><?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
            <p class="api-scopes-note">All new integrations receive these scopes by default.</p>
        </form>
    </div>

    <?php if (!empty($apiKeys)): ?>
    <div class="api-card-list">
        <div class="api-section-label">Existing integrations</div>
        <div class="api-table-scroll">
            <table class="api-keys-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Client ID</th>
                        <th>Status</th>
                        <th>Last used</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                    <tr>
                        <td class="col-key-name"><?= htmlspecialchars($key['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code class="api-key-code"><?= htmlspecialchars($key['client_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td>
                            <?php if ((int) $key['is_active'] === 1): ?>
                                <span class="tag-badge api-badge-active">Active</span>
                            <?php else: ?>
                                <span class="tag-badge api-badge-revoked">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-date-muted"><?= $key['last_used_at'] ? htmlspecialchars(date('d M Y', strtotime($key['last_used_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                        <td class="col-date-muted"><?= htmlspecialchars(date('d M Y', strtotime($key['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $fullScopes = ['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'];
                            $keyScopes = json_decode($key['scopes'] ?? '[]', true) ?: [];
                            $needsSync = (int) $key['is_active'] === 1 && count(array_diff($fullScopes, $keyScopes)) > 0;
                            ?>
                            <div class="action-links">
                                <?php if ($needsSync): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/sync-scopes'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                                    <button class="action-edit api-btn-sync" type="submit"
                                            onclick="return confirm('Grant this integration all current scopes?')">Sync scopes</button>
                                </form>
                                <?php endif; ?>
                                <?php if ((int) $key['is_active'] === 1): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/revoke'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                                    <button class="action-edit api-btn-action" type="submit"
                                            onclick="return confirm('Revoke these credentials? The integration will stop working.')">Revoke</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/delete'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                                    <button class="action-delete api-btn-action" type="submit"
                                            onclick="return confirm('Permanently delete these API credentials?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="api-empty-state">
        No API integrations yet. Create credentials above to start using the API.
    </div>
    <?php endif; ?>
</div>
