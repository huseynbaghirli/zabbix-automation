<?php

declare(strict_types=1);

/**
 * @var array $data
 */

echo '<style>' . file_get_contents(dirname(__DIR__) . '/assets/css/automation.css') . '</style>';

?>
<h1><?= htmlspecialchars($data['title']) ?></h1>

<!-- JSON data for JS -->
<script type="application/json" id="js-groups"><?= json_encode(array_values($data['groups']),    JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="js-templates"><?= json_encode(array_values($data['templates']), JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="js-proxies"><?= json_encode(array_values($data['proxies']),    JSON_UNESCAPED_UNICODE) ?></script>

<?php
// Embed CSRF token for AJAX request (needed when opcache holds an old
// version of BulkHostsSubmit that still runs CSRF validation).
// Zabbix 7.x validates _csrf_token against CCsrfTokenHelper::get('zabbix.php').
$csrf_token = '';
if (class_exists('CCsrfTokenHelper')) {
    foreach (['zabbix.php', '', 'automation.bulk.hosts.submit'] as $_ctx) {
        try {
            $csrf_token = CCsrfTokenHelper::get($_ctx);
            if ($csrf_token !== '') break;
        } catch (\Throwable $e) {}
    }
}
?>
<input type="hidden" id="zbx-csrf-token" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<div class="automation-section">

    <!-- Table -->
    <div class="bulk-table-wrapper">
        <table class="bulk-host-table" id="hosts-table">
            <thead>
                <tr>
                    <th class="col-hostname"><?= _('Hostname') ?> <span style="color:#d40000">*</span></th>
                    <th class="col-groups"><?= _('Host Groups') ?></th>
                    <th class="col-templates"><?= _('Templates') ?></th>
                    <th class="col-ip"><?= _('IP Address') ?></th>
                    <th class="col-port"><?= _('Port') ?></th>
                    <th class="col-proxy"><?= _('Proxy') ?></th>
                    <th class="col-tags"><?= _('Tags (key : value)') ?></th>
                    <th class="col-actions"></th>
                </tr>
            </thead>
            <tbody id="hosts-tbody">
                <!-- rows injected by JS -->
            </tbody>
        </table>
    </div>

    <button type="button" id="btn-add-row" class="btn-add-row">+ <?= _('Add Row') ?></button>

    <div id="bulk-results" class="automation-results hidden"></div>

    <div class="form-buttons">
        <button type="button" id="btn-add-hosts" class="automation-btn"><?= _('Add Hosts') ?></button>
    </div>

</div>

<!-- Shared floating selector panel -->
<div class="selector-panel" id="selector-panel">
    <div class="selector-panel-search">
        <input type="text" id="selector-search" placeholder="<?= htmlspecialchars(_('Search…')) ?>">
    </div>
    <div class="selector-panel-list" id="selector-list"></div>
    <div class="selector-panel-footer">
        <button type="button" id="btn-selector-apply" class="automation-btn"><?= _('Apply') ?></button>
        <button type="button" id="btn-selector-cancel" class="btn-selector-cancel"><?= _('Cancel') ?></button>
    </div>
</div>

<script>
<?= file_get_contents(__DIR__ . '/js/automation.bulk.hosts.js') ?>
</script>
