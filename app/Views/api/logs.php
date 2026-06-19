<?php
$activeFilters = array_filter($filters, fn ($v) => $v !== '' && $v !== 0 && $v !== null);

$from = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
$to   = min($page * $perPage, $total);

$base = Auth::url('/api-logs');

function apiLogsPaginationRange(int $page, int $totalPages): array
{
    if ($totalPages <= 7) return range(1, $totalPages);
    $pages = [1];
    if ($page > 3) $pages[] = '...';
    for ($i = max(2, $page - 1); $i <= min($totalPages - 1, $page + 1); $i++) $pages[] = $i;
    if ($page < $totalPages - 2) $pages[] = '...';
    $pages[] = $totalPages;
    return $pages;
}

function apiStatusClass(int $code): string
{
    if ($code >= 200 && $code < 300) return 'api-status--ok';
    if ($code >= 400 && $code < 500) return 'api-status--warn';
    return 'api-status--err';
}

function apiMethodClass(string $m): string
{
    return 'api-method--' . strtolower($m);
}
?>

<!-- Header -->
<div class="page-header api-logs-header">
    <div>
        <h1>API Logs</h1>
        <span class="count-label"><?= number_format($total) ?> request<?= $total !== 1 ? 's' : '' ?></span>
    </div>
    <a class="btn btn-outlined btn-sm" href="<?= htmlspecialchars(Auth::url('/api-keys'), ENT_QUOTES, 'UTF-8') ?>">
        API Keys
    </a>
</div>

<!-- Filters -->
<div class="api-logs-filter-card">
    <form method="get" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>">
        <div class="api-logs-filter-row">

            <div class="field api-logs-field">
                <label for="lf_key">Integration</label>
                <select id="lf_key" name="key_id">
                    <option value="">All integrations</option>
                    <?php foreach ($apiKeys as $key): ?>
                    <option value="<?= (int) $key['id'] ?>" <?= ((int) ($filters['key_id'] ?? 0) === (int) $key['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($key['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field api-logs-field api-logs-field--sm">
                <label for="lf_method">Method</label>
                <select id="lf_method" name="method">
                    <option value="">All</option>
                    <?php foreach (['GET', 'POST', 'PATCH', 'DELETE'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($filters['method'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field api-logs-field api-logs-field--sm">
                <label for="lf_status">Result</label>
                <select id="lf_status" name="status">
                    <option value="">All</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success (2xx)</option>
                    <option value="error"   <?= ($filters['status'] ?? '') === 'error'   ? 'selected' : '' ?>>Error (4xx/5xx)</option>
                </select>
            </div>

            <div class="field api-logs-field">
                <label for="lf_path">Path contains</label>
                <input id="lf_path" type="text" name="path" placeholder="/api/v1/contacts"
                       value="<?= htmlspecialchars($filters['path'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="field api-logs-field api-logs-field--sm">
                <label for="lf_date_from">From</label>
                <input id="lf_date_from" type="date" name="date_from"
                       value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="field api-logs-field api-logs-field--sm">
                <label for="lf_date_to">To</label>
                <input id="lf_date_to" type="date" name="date_to"
                       value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="api-logs-filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outlined" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>">Reset</a>
            </div>

        </div>
    </form>
</div>

<!-- Table -->
<div class="api-logs-card">
    <?php if (empty($logs)): ?>
        <div class="api-logs-empty">No log entries found.</div>
    <?php else: ?>
    <div class="api-logs-table-wrap">
        <table class="data-table api-logs-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Time</th>
                    <th>Integration</th>
                    <th>Origin</th>
                    <th>IP</th>
                    <th>Method</th>
                    <th>Path</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th class="col-num">Items</th>
                    <th class="col-num">ms</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $i => $log):
                    $hasBody  = !empty($log['request_body']) || !empty($log['response_body']);
                    $detailId = 'log-detail-' . $i;
                    $isError  = (int) $log['response_status'] >= 400;
                ?>
                <tr class="api-log-main-row <?= $isError ? 'api-log-row--error' : '' ?>" <?= $hasBody ? 'data-detail="' . $detailId . '"' : '' ?>>
                    <td class="col-log-expand">
                        <?php if ($hasBody): ?>
                        <button class="api-log-expand-btn" aria-expanded="false" aria-controls="<?= $detailId ?>">
                            <svg viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td class="col-log-time" title="<?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(date('d M H:i:s', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="col-log-key">
                        <?php if (!empty($log['key_name'])): ?>
                            <span class="api-log-key-name"><?= htmlspecialchars($log['key_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="col-log-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-log-origin col-log-muted" title="<?= htmlspecialchars($log['origin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($log['origin'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="col-log-ip col-log-muted">
                        <?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <span class="api-method <?= apiMethodClass($log['method']) ?>">
                            <?= htmlspecialchars($log['method'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="col-log-path">
                        <code class="api-log-path"><?= htmlspecialchars($log['path'], ENT_QUOTES, 'UTF-8') ?></code>
                    </td>
                    <td>
                        <span class="api-status-badge <?= apiStatusClass((int) $log['response_status']) ?>">
                            <?= (int) $log['response_status'] ?>
                        </span>
                    </td>
                    <td class="col-log-error">
                        <?php if (!empty($log['error_code'])): ?>
                            <code class="api-log-error-code"><?= htmlspecialchars($log['error_code'], ENT_QUOTES, 'UTF-8') ?></code>
                        <?php else: ?>
                            <span class="col-log-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-num col-log-muted">
                        <?= $log['items_count'] !== null ? (int) $log['items_count'] : '—' ?>
                    </td>
                    <td class="col-num col-log-muted">
                        <?= $log['duration_ms'] !== null ? (int) $log['duration_ms'] : '—' ?>
                    </td>
                </tr>
                <?php if ($hasBody): ?>
                <tr class="api-log-detail-row" id="<?= $detailId ?>" hidden>
                    <td colspan="11" class="api-log-detail-cell">
                        <div class="api-log-detail-grid">
                            <div class="api-log-detail-pane">
                                <div class="api-log-detail-label">Request body</div>
                                <pre class="api-log-detail-pre"><?= !empty($log['request_body']) ? htmlspecialchars($log['request_body'], ENT_QUOTES, 'UTF-8') : '<span class="col-log-muted">empty</span>' ?></pre>
                            </div>
                            <div class="api-log-detail-pane">
                                <div class="api-log-detail-label">Response body</div>
                                <pre class="api-log-detail-pre"><?= !empty($log['response_body']) ? htmlspecialchars($log['response_body'], ENT_QUOTES, 'UTF-8') : '<span class="col-log-muted">empty</span>' ?></pre>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="api-logs-pagination">
        <span>Showing <?= $from ?>–<?= $to ?> of <?= number_format($total) ?></span>
        <div class="pagination-pages">
            <?php if ($page > 1): ?>
                <a class="page-btn" href="<?= htmlspecialchars($base . '?' . http_build_query(array_merge($activeFilters, ['page' => $page - 1])), ENT_QUOTES, 'UTF-8') ?>">&#8249;</a>
            <?php else: ?>
                <span class="page-btn disabled">&#8249;</span>
            <?php endif; ?>

            <?php foreach (apiLogsPaginationRange($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                    <span class="page-ellipsis">...</span>
                <?php else: ?>
                    <a class="page-btn <?= $p === $page ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($base . '?' . http_build_query(array_merge($activeFilters, ['page' => $p])), ENT_QUOTES, 'UTF-8') ?>">
                        <?= $p ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-btn" href="<?= htmlspecialchars($base . '?' . http_build_query(array_merge($activeFilters, ['page' => $page + 1])), ENT_QUOTES, 'UTF-8') ?>">&#8250;</a>
            <?php else: ?>
                <span class="page-btn disabled">&#8250;</span>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
(function () {
    document.querySelectorAll('.api-log-expand-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var detailId  = btn.closest('tr').dataset.detail;
            var detailRow = document.getElementById(detailId);
            var open      = detailRow.hidden;
            detailRow.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('is-open', open);
        });
    });
})();
</script>
