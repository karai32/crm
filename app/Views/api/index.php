<!-- Page header -->
<div class="page-header settings-header api-header">
    <div>
        <h1>API Credentials</h1>
        <span class="count-label"><?= count($apiKeys) ?> integration<?= count($apiKeys) !== 1 ? 's' : '' ?></span>
    </div>
    <div style="display:flex;gap:12px;align-items:center">
        <a href="<?= htmlspecialchars(Auth::url('/api-logs'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outlined btn-sm">
            View logs
        </a>
        <a href="<?= htmlspecialchars(Auth::url('/help/api'), ENT_QUOTES, 'UTF-8') ?>" class="api-back-link">
            API Reference
        </a>
    </div>
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
            <table class="data-table api-keys-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Client ID</th>
                        <th>Status</th>
                        <th>Last used</th>
                        <th>Created</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                    <?php
                    $keyId     = (int) $key['id'];
                    $isActive  = (int) $key['is_active'] === 1;
                    $keyName   = htmlspecialchars($key['name'], ENT_QUOTES, 'UTF-8');
                    $keyNameJs = htmlspecialchars(json_encode($key['name']), ENT_QUOTES, 'UTF-8');
                    $fullScopes = ['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'];
                    $keyScopes  = json_decode($key['scopes'] ?? '[]', true) ?: [];
                    $needsSync  = $isActive && count(array_diff($fullScopes, $keyScopes)) > 0;
                    ?>
                    <tr>
                        <td class="col-key-name">
                            <span class="api-key-name-display" id="nameDisplay<?= $keyId ?>">
                                <?= $keyName ?>
                                <button type="button" class="api-key-rename-btn" aria-label="Rename"
                                        onclick="apiKeyRenameStart(<?= $keyId ?>, <?= $keyNameJs ?>)">
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                </button>
                            </span>
                            <form class="api-key-inline-form" id="renameForm<?= $keyId ?>"
                                  method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/rename'), ENT_QUOTES, 'UTF-8') ?>"
                                  style="display:none">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= $keyId ?>">
                                <input type="text" name="name" class="api-key-inline-input" required>
                                <button type="submit" class="api-key-inline-save">Save</button>
                                <button type="button" class="api-key-inline-cancel"
                                        onclick="apiKeyRenameCancel(<?= $keyId ?>)">Cancel</button>
                            </form>
                        </td>
                        <td><code class="api-key-code"><?= htmlspecialchars($key['client_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="tag-badge api-badge-active">Active</span>
                            <?php else: ?>
                                <span class="tag-badge api-badge-revoked">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-date-muted"><?= $key['last_used_at'] ? htmlspecialchars(date('d M Y', strtotime($key['last_used_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                        <td class="col-date-muted"><?= htmlspecialchars(date('d M Y', strtotime($key['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-actions">
                            <div class="action-links">
                                <?php if ($needsSync): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/sync-scopes'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-view" type="submit" title="Sync scopes"
                                            onclick="return confirm('Grant this integration all current scopes?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($isActive): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/revoke'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-warn" type="submit" title="Disable"
                                            onclick="return confirm('Disable this integration? It can be re-enabled later.')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/enable'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-edit" type="submit" title="Enable">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/delete'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-delete" type="submit" title="Delete"
                                            onclick="return confirm('Permanently delete these API credentials?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
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
<script>
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
</script>
