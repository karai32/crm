<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Tags</h1>
        <span class="count-label"><?= count($tags) ?> tags</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/tags/create'), ENT_QUOTES, 'UTF-8') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create tag
        </a>
    </div>
</div>

<div class="settings-search-card">
    <input type="text"
           class="settings-live-search"
           placeholder="Search tags..."
           autocomplete="off"
           data-settings-search="tags"
           data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
           data-search-target="tagsSearchResults"
           data-edit-url="<?= htmlspecialchars(Auth::url('/tags/edit?id='), ENT_QUOTES, 'UTF-8') ?>"
           data-delete-url="<?= htmlspecialchars(Auth::url('/tags/delete?id='), ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="settings-table-card">
    <?php if (empty($tags)): ?>
        <p style="padding:24px 16px;color:var(--color-text-muted);font-size:14px;">No tags found.</p>
    <?php else: ?>
    <table class="settings-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Color</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="tagsSearchResults">
            <?php foreach ($tags as $tag): ?>
                <tr>
                    <td class="col-name">
                        <?php if (!empty($tag['color'])): ?>
                            <span class="tag-badge" style="background:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>22;border-color:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>44;color:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </td>
                    <td class="col-slug"><?= htmlspecialchars($tag['slug'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (!empty($tag['color'])): ?>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--color-neutral)">
                                <span class="color-dot" style="background:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>"></span>
                                <code style="font-size:12px"><?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?></code>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--color-neutral);font-size:13px">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-links">
                            <a class="action-edit" href="<?= htmlspecialchars(Auth::url('/tags/edit?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                            <a class="action-delete"
                               href="<?= htmlspecialchars(Auth::url('/tags/delete?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Delete this tag? Existing contact and client links will be removed.')">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

