<!-- Page header -->
<div class="page-header settings-header api-header">
    <div>
        <h1><?= t('api.title') ?></h1>
        <span class="count-label"><?= (int) $total ?> integration<?= (int) $total !== 1 ? 's' : '' ?></span>
    </div>
    <div style="display:flex;gap:12px;align-items:center">
        <a href="<?= htmlspecialchars(Auth::url('/api-logs'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outlined btn-sm">
            <?= t('api.logs') ?>
        </a>
        <a href="<?= htmlspecialchars(Auth::url('/help/api'), ENT_QUOTES, 'UTF-8') ?>" class="api-back-link">
            <?= t('api.reference') ?>
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
        <div class="api-new-credentials-title"><?= t('api.credentials_created') ?></div>
        <div class="api-new-credentials-subtitle"><?= t('api.secret_not_shown') ?></div>
        <div class="api-new-credentials-grid">
            <strong class="api-new-credentials-label"><?= t('api.client_id') ?></strong>
            <code class="api-new-credentials-code"><?= htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?></code>
            <span></span>

            <strong class="api-new-credentials-label"><?= t('api.secret') ?></strong>
            <code id="newSecretValue" class="api-new-credentials-code"><?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewSecret" class="api-new-credentials-copy"
                    onclick="copyCredential('newSecretValue', this, '<?= htmlspecialchars(Lang::get('api.copy_secret'), ENT_QUOTES, 'UTF-8') ?>')"><?= t('api.copy_secret') ?></button>

            <strong class="api-new-credentials-label"><?= t('api.basic_auth') ?></strong>
            <code id="newBasicAuthValue" class="api-new-credentials-code"><?= htmlspecialchars($basicAuth, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" id="copyNewBasicAuth" class="api-new-credentials-copy"
                    onclick="copyCredential('newBasicAuthValue', this, '<?= htmlspecialchars(Lang::get('api.copy_basic_auth'), ENT_QUOTES, 'UTF-8') ?>')"><?= t('api.copy_basic_auth') ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="api-card">
    <div class="api-card-create">
        <div class="api-section-label"><?= t('api.create_credentials') ?></div>
        <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/store'), ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <div class="api-create-row">
                <div class="field api-name-field">
                    <label for="keyName">
                        <?= t('api.integration_name') ?> <span class="required-star">*</span>
                    </label>
                    <input id="keyName" type="text" name="name" placeholder="<?= t('api.integration_name_placeholder') ?>" required class="api-name-input">
                </div>
                <button class="btn btn-primary api-submit-btn" type="submit"><?= t('api.create_credentials_btn') ?></button>
            </div>
            <div class="api-scopes-row">
                <?php foreach (['contacts:write','contacts:read','clients:write','clients:read','sectors:write','sectors:read','tags:write','tags:read'] as $scope): ?>
                <span class="api-scope"><?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
            <p class="api-scopes-note"><?= t('api.default_scopes_note') ?></p>
        </form>
    </div>

    <?php if (!empty($apiKeys)): ?>
    <div class="api-card-list">
        <div class="api-section-label"><?= t('api.existing_integrations') ?></div>
        <div class="api-table-scroll">
            <table class="data-table api-keys-table">
                <thead>
                    <tr>
                        <?= thSort('name', t('common.name'), $sort, $dir, '/api-keys') ?>
                        <th><?= t('api.client_id') ?></th>
                        <?= thSort('is_active', t('common.status'), $sort, $dir, '/api-keys') ?>
                        <th><?= t('api.last_used') ?></th>
                        <th><?= t('common.created') ?></th>
                        <th class="col-actions"><?= t('common.actions') ?></th>
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
                                <button type="button" class="api-key-rename-btn" aria-label="<?= t('common.edit') ?>"
                                        onclick="apiKeyRenameStart(<?= $keyId ?>, <?= $keyNameJs ?>)">
                                    <i class="ph ph-pencil"></i>
                                </button>
                            </span>
                            <form class="api-key-inline-form" id="renameForm<?= $keyId ?>"
                                  method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/rename'), ENT_QUOTES, 'UTF-8') ?>"
                                  style="display:none">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= $keyId ?>">
                                <input type="text" name="name" class="api-key-inline-input" required>
                                <button type="submit" class="api-key-inline-save"><?= t('common.save') ?></button>
                                <button type="button" class="api-key-inline-cancel"
                                        onclick="apiKeyRenameCancel(<?= $keyId ?>)"><?= t('common.cancel') ?></button>
                            </form>
                        </td>
                        <td><code class="api-key-code"><?= htmlspecialchars($key['client_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="tag-badge api-badge-active"><?= t('common.active') ?></span>
                            <?php else: ?>
                                <span class="tag-badge api-badge-revoked"><?= t('api.disabled') ?></span>
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
                                    <button class="action-btn action-view" type="submit"
                                            onclick="return confirm('<?= htmlspecialchars(Lang::get('api.grant_scopes_confirm'), ENT_QUOTES, 'UTF-8') ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                        <span class="tooltip-text"><?= t('api.sync_scopes') ?></span>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($isActive): ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/revoke'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-warn" type="submit"
                                            onclick="return confirm('<?= htmlspecialchars(Lang::get('api.disable_confirm'), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="ph ph-cell-signal-slash"></i>
                                        <span class="tooltip-text"><?= t('api.disable') ?></span>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/enable'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-edit" type="submit">
                                        <i class="ph ph-cell-signal-full"></i>
                                        <span class="tooltip-text"><?= t('api.enable') ?></span>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?= htmlspecialchars(Auth::url('/api-keys/delete'), ENT_QUOTES, 'UTF-8') ?>" class="api-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $keyId ?>">
                                    <button class="action-btn action-delete" type="submit"
                                            onclick="return confirm('<?= htmlspecialchars(Lang::get('api.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="ph ph-trash"></i>
                                        <span class="tooltip-text"><?= t('common.delete') ?></span>
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
        <?= t('api.no_integrations') ?>
    </div>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/api-keys', ['sort' => $sort, 'dir' => $dir]); ?>
