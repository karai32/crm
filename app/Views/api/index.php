<!-- Page header -->
<div class="page-header settings-header" style="margin-bottom:20px">
    <div>
        <h1>API Credentials</h1>
        <span class="count-label"><?= count($apiKeys) ?> integration<?= count($apiKeys) !== 1 ? 's' : '' ?></span>
    </div>
    <a href="<?= htmlspecialchars(Auth::url('/help/api'), ENT_QUOTES, 'UTF-8') ?>"
       style="font-size:13px;color:var(--color-text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:5px">
        API Reference
    </a>
</div>

<?php if (!empty($newCredentials)): ?>
<?php
$clientId = (string) ($newCredentials['client_id'] ?? '');
$secret = (string) ($newCredentials['secret'] ?? '');
$basicAuth = $clientId . ':' . $secret;
?>
<div style="display:flex;gap:14px;align-items:flex-start;background:#f0fdf4;border:1px solid #86efac;border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px">
    <div style="flex:1;min-width:0">
        <div style="font-size:14px;font-weight:700;color:#166534;margin-bottom:3px">API credentials created - copy the secret now</div>
        <div style="font-size:12.5px;color:#15803d;margin-bottom:12px">The secret will not be shown again. Use the Basic Auth value in Formidable Forms.</div>
        <div style="display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:8px;align-items:center">
            <strong style="font-size:12px;color:#166534">Client ID</strong>
            <code style="background:#dcfce7;border:1px solid #86efac;padding:6px 12px;border-radius:6px;font-size:12.5px;color:#14532d;word-break:break-all"><?= htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?></code>
            <span></span>

            <strong style="font-size:12px;color:#166534">Secret</strong>
            <code id="newSecretValue" style="background:#dcfce7;border:1px solid #86efac;padding:6px 12px;border-radius:6px;font-size:12.5px;color:#14532d;word-break:break-all"><?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewSecret" style="padding:6px 14px;border-radius:6px;border:1px solid #86efac;background:#dcfce7;color:#166534;font-size:12.5px;font-weight:600;cursor:pointer"
                    onclick="copyCredential('newSecretValue', this, 'Copy secret')">Copy secret</button>

            <strong style="font-size:12px;color:#166534">Basic Auth</strong>
            <code id="newBasicAuthValue" style="background:#dcfce7;border:1px solid #86efac;padding:6px 12px;border-radius:6px;font-size:12.5px;color:#14532d;word-break:break-all"><?= htmlspecialchars($basicAuth, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewBasicAuth" style="padding:6px 14px;border-radius:6px;border:1px solid #86efac;background:#dcfce7;color:#166534;font-size:12.5px;font-weight:600;cursor:pointer"
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

<div style="background:var(--color-white);border:1px solid var(--color-neutral-200);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden">
    <div style="padding:20px 22px;border-bottom:1px solid var(--color-neutral-100)">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-neutral);margin-bottom:14px">Create API credentials</div>
        <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/store'), ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
                <div class="field" style="flex:1;min-width:200px;margin:0">
                    <label for="keyName" style="font-size:12px;font-weight:600;color:var(--color-text-muted);margin-bottom:5px;display:block">
                        Integration name <span style="color:var(--color-danger)">*</span>
                    </label>
                    <input id="keyName" type="text" name="name" placeholder="e.g. Website contact form" required style="margin:0">
                </div>
                <button class="btn btn-primary" type="submit" style="flex-shrink:0">Create credentials</button>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:4px">
                <?php foreach (['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'] as $scope): ?>
                <span class="api-scope"><?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:var(--color-text-muted);margin:5px 0 0">All new integrations receive these scopes by default.</p>
        </form>
    </div>

    <?php if (!empty($apiKeys)): ?>
    <div style="padding:16px 22px 20px">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-neutral);margin-bottom:12px">Existing integrations</div>
        <div style="overflow-x:auto;border:1px solid var(--color-neutral-200);border-radius:var(--radius-sm)">
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
                        <td style="font-weight:500"><?= htmlspecialchars($key['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code style="background:var(--color-neutral-100);padding:2px 6px;border-radius:3px;font-size:11.5px"><?= htmlspecialchars($key['client_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td>
                            <?php if ((int) $key['is_active'] === 1): ?>
                                <span class="tag-badge" style="background:#f0fdf4;border-color:#86efac;color:#166534">Active</span>
                            <?php else: ?>
                                <span class="tag-badge" style="background:var(--color-neutral-100);border-color:var(--color-neutral-300);color:var(--color-neutral)">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--color-text-muted);font-size:12.5px"><?= $key['last_used_at'] ? htmlspecialchars(date('d M Y', strtotime($key['last_used_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                        <td style="color:var(--color-text-muted);font-size:12.5px"><?= htmlspecialchars(date('d M Y', strtotime($key['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $fullScopes = ['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'];
                            $keyScopes = json_decode($key['scopes'] ?? '[]', true) ?: [];
                            $needsSync = (int) $key['is_active'] === 1 && count(array_diff($fullScopes, $keyScopes)) > 0;
                            ?>
                            <div class="action-links">
                                <?php if ($needsSync): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/sync-scopes'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                                    <button class="action-edit" type="submit" style="color:#d97706;background:none;border:0;padding:0;cursor:pointer"
                                            onclick="return confirm('Grant this integration all current scopes?')">Sync scopes</button>
                                </form>
                                <?php endif; ?>
                                <?php if ((int) $key['is_active'] === 1): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/revoke'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                                    <button class="action-edit" type="submit" style="background:none;border:0;padding:0;cursor:pointer"
                                            onclick="return confirm('Revoke these credentials? The integration will stop working.')">Revoke</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/delete'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $key['id'] ?>">
                                    <button class="action-delete" type="submit" style="background:none;border:0;padding:0;cursor:pointer"
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
    <div style="padding:24px 22px;color:var(--color-text-muted);font-size:13.5px">
        No API integrations yet. Create credentials above to start using the API.
    </div>
    <?php endif; ?>
</div>
