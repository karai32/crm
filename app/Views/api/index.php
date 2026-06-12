<div class="page-header settings-header">
    <div>
        <h1>API Keys</h1>
        <span class="count-label"><?= count($apiKeys) ?> key<?= count($apiKeys) !== 1 ? 's' : '' ?></span>
    </div>
</div>

<?php if (!empty($newKey)): ?>
<div class="api-key-reveal">
    <div class="api-key-reveal-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z"/>
        </svg>
    </div>
    <div class="api-key-reveal-body">
        <div class="api-key-reveal-title">API key created — copy it now</div>
        <div class="api-key-reveal-hint">This key will not be shown again. Store it somewhere safe.</div>
        <div class="api-key-reveal-row">
            <code class="api-key-reveal-value" id="newKeyValue"><?= htmlspecialchars($newKey, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" class="api-key-copy-btn" id="copyNewKey" onclick="
                navigator.clipboard.writeText(document.getElementById('newKeyValue').textContent).then(function(){
                    var btn = document.getElementById('copyNewKey');
                    btn.textContent = 'Copied!';
                    setTimeout(function(){ btn.textContent = 'Copy'; }, 2000);
                });
            ">Copy</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="settings-form-card" style="margin-bottom:24px">
    <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="settings-form-body">
            <div class="field">
                <label for="keyName">Key name <span style="color:var(--color-danger)">*</span></label>
                <input id="keyName" type="text" name="name" placeholder="e.g. HubSpot integration" required autofocus style="max-width:360px">
                <span style="font-size:12px;color:var(--color-text-muted);margin-top:4px;display:block">A label to identify where this key is used.</span>
            </div>
            <div class="field">
                <label>Scopes</label>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    <?php foreach (['contacts:write', 'contacts:read', 'clients:write', 'clients:read', 'sectors:write', 'sectors:read', 'tags:write', 'tags:read'] as $scopeLabel): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--color-neutral-50);border:1px solid var(--color-neutral-200);border-radius:var(--radius-sm)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" style="width:14px;height:14px;color:var(--color-secondary);flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        <span style="font-size:13px;font-weight:500"><?= $scopeLabel ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <span style="font-size:12px;color:var(--color-text-muted);margin-top:4px;display:block">More scopes will be available as the API expands.</span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Generate key</button>
        </div>
    </form>
</div>

<?php if (empty($apiKeys)): ?>
    <p style="padding:24px 16px;color:var(--color-text-muted);font-size:14px;">No API keys yet.</p>
<?php else: ?>
<div class="settings-table-card">
    <table class="settings-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Key prefix</th>
                <th>Scopes</th>
                <th>Status</th>
                <th>Last used</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apiKeys as $key):
                $scopes = json_decode($key['scopes'] ?? '[]', true) ?? [];
            ?>
            <tr>
                <td class="col-name"><?= htmlspecialchars($key['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <code style="font-size:12.5px;background:var(--color-neutral-100);padding:2px 7px;border-radius:4px;letter-spacing:.03em">
                        <?= htmlspecialchars($key['key_prefix'], ENT_QUOTES, 'UTF-8') ?>…
                    </code>
                </td>
                <td>
                    <?php foreach ($scopes as $scope): ?>
                        <span class="tag-badge" style="background:var(--color-secondary-bg);border-color:#6ee7b7;color:#065f46;font-size:11px">
                            <?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php if ((int) $key['is_active'] === 1): ?>
                        <span class="tag-badge" style="background:#f0fdf4;border-color:#86efac;color:#166534">Active</span>
                    <?php else: ?>
                        <span class="tag-badge" style="background:var(--color-neutral-100);border-color:var(--color-neutral-300);color:var(--color-neutral)">Revoked</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--color-text-muted);font-size:13px">
                    <?= $key['last_used_at'] ? htmlspecialchars(date('d M Y, H:i', strtotime($key['last_used_at'])), ENT_QUOTES, 'UTF-8') : '—' ?>
                </td>
                <td style="color:var(--color-text-muted);font-size:13px">
                    <?= htmlspecialchars(date('d M Y', strtotime($key['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                    <div class="action-links">
                        <?php if ((int) $key['is_active'] === 1): ?>
                        <a class="action-edit"
                           href="<?= htmlspecialchars(Auth::url('/api-keys/revoke?id=' . $key['id']), ENT_QUOTES, 'UTF-8') ?>"
                           onclick="return confirm('Revoke this API key? Any integrations using it will stop working.')">Revoke</a>
                        <?php endif; ?>
                        <a class="action-delete"
                           href="<?= htmlspecialchars(Auth::url('/api-keys/delete?id=' . $key['id']), ENT_QUOTES, 'UTF-8') ?>"
                           onclick="return confirm('Permanently delete this API key?')">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
