<!-- Page header -->
<div class="page-header settings-header" style="margin-bottom:20px">
    <div>
        <h1>API Keys</h1>
        <span class="count-label"><?= count($apiKeys) ?> key<?= count($apiKeys) !== 1 ? 's' : '' ?></span>
    </div>
    <a href="<?= htmlspecialchars(Auth::url('/help/api'), ENT_QUOTES, 'UTF-8') ?>"
       style="font-size:13px;color:var(--color-text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:5px">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" style="width:15px;height:15px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
        </svg>
        API Reference
    </a>
</div>

<!-- New key reveal -->
<?php if (!empty($newKey)): ?>
<div style="display:flex;gap:14px;align-items:flex-start;background:#f0fdf4;border:1px solid #86efac;border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px">
    <div style="width:36px;height:36px;background:#dcfce7;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#16a34a">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" style="width:20px;height:20px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z"/>
        </svg>
    </div>
    <div style="flex:1;min-width:0">
        <div style="font-size:14px;font-weight:700;color:#166534;margin-bottom:3px">API key created — copy it now</div>
        <div style="font-size:12.5px;color:#15803d;margin-bottom:10px">This key will not be shown again. Store it somewhere safe.</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <code id="newKeyValue" style="background:#dcfce7;border:1px solid #86efac;padding:6px 12px;border-radius:6px;font-size:12.5px;font-family:var(--font-mono,monospace);color:#14532d;word-break:break-all"><?= htmlspecialchars($newKey, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewKey" style="flex-shrink:0;padding:6px 14px;border-radius:6px;border:1px solid #86efac;background:#dcfce7;color:#166534;font-size:12.5px;font-weight:600;cursor:pointer" onclick="
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

<!-- Card -->
<div style="background:var(--color-white);border:1px solid var(--color-neutral-200);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden">

    <!-- Create form -->
    <div style="padding:20px 22px;border-bottom:1px solid var(--color-neutral-100)">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-neutral);margin-bottom:14px">Generate a new key</div>
        <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/store'), ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
                <div class="field" style="flex:1;min-width:200px;margin:0">
                    <label for="keyName" style="font-size:12px;font-weight:600;color:var(--color-text-muted);margin-bottom:5px;display:block">
                        Key name <span style="color:var(--color-danger)">*</span>
                    </label>
                    <input id="keyName" type="text" name="name" placeholder="e.g. HubSpot integration" required style="margin:0">
                </div>
                <button class="btn btn-primary" type="submit" style="flex-shrink:0">Generate key</button>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:4px">
                <?php foreach (['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'] as $s): ?>
                <span class="api-scope"><?= $s ?></span>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:var(--color-text-muted);margin:5px 0 0">All new keys receive these scopes by default.</p>
        </form>
    </div>

    <!-- Keys list -->
    <?php if (!empty($apiKeys)): ?>
    <div style="padding:16px 22px 20px">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-neutral);margin-bottom:12px">Existing keys</div>
        <div style="overflow-x:auto;border:1px solid var(--color-neutral-200);border-radius:var(--radius-sm)">
            <table class="api-keys-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Prefix</th>
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
                        <td>
                            <code style="background:var(--color-neutral-100);padding:2px 6px;border-radius:3px;font-size:11.5px">
                                <?= htmlspecialchars($key['key_prefix'], ENT_QUOTES, 'UTF-8') ?>…
                            </code>
                        </td>
                        <td>
                            <?php if ((int) $key['is_active'] === 1): ?>
                                <span class="tag-badge" style="background:#f0fdf4;border-color:#86efac;color:#166534">Active</span>
                            <?php else: ?>
                                <span class="tag-badge" style="background:var(--color-neutral-100);border-color:var(--color-neutral-300);color:var(--color-neutral)">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--color-text-muted);font-size:12.5px">
                            <?= $key['last_used_at'] ? htmlspecialchars(date('d M Y', strtotime($key['last_used_at'])), ENT_QUOTES, 'UTF-8') : '—' ?>
                        </td>
                        <td style="color:var(--color-text-muted);font-size:12.5px">
                            <?= htmlspecialchars(date('d M Y', strtotime($key['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div class="action-links">
                                <?php if ((int) $key['is_active'] === 1): ?>
                                <a class="action-edit"
                                   href="<?= htmlspecialchars(Auth::url('/api-keys/revoke?id=' . $key['id']), ENT_QUOTES, 'UTF-8') ?>"
                                   onclick="return confirm('Revoke this key? Integrations using it will stop working.')">Revoke</a>
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
    </div>
    <?php else: ?>
    <div style="padding:24px 22px;color:var(--color-text-muted);font-size:13.5px">
        No keys yet. Generate one above to start using the API.
    </div>
    <?php endif; ?>

</div>
