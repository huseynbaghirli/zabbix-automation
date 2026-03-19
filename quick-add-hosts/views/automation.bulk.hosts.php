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
<script type="application/json" id="js-zabbix-server"><?= json_encode($data['zabbix_server'] ?? '') ?></script>

<?php
$csrf_token = '';
if (class_exists('CCsrfTokenHelper')) {
    try { $csrf_token = CCsrfTokenHelper::get('automation.bulk.hosts.submit'); }
    catch (\Throwable $e) {}
}
?>
<input type="hidden" id="zbx-csrf-token" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<div class="automation-section">

    <!-- Zabbix server IP (used when no proxy is selected) -->
    <div class="zbx-server-row">
        <label for="zbx-server-ip"><?= _('Zabbix Server IP') ?></label>
        <input type="text" id="zbx-server-ip"
               class="automation-input-sm"
               style="width:200px"
               value="<?= htmlspecialchars($data['zabbix_server'] ?? '') ?>"
               placeholder="10.0.0.1">
        <span class="automation-hint"><?= _('Used as --server-host when no proxy is assigned to a row.') ?></span>
    </div>

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
        <button type="button" id="btn-gen-install" class="automation-btn automation-btn-secondary"><?= _('Generate Install Commands') ?></button>
    </div>

</div>

<!-- Bulk install commands modal -->
<div id="install-modal-overlay" class="install-modal-overlay">
    <div class="install-modal">
        <div class="install-modal-header">
            <span><?= _('Install Commands') ?></span>
            <button type="button" id="btn-install-modal-close" class="install-panel-close">&#10005;</button>
        </div>
        <div id="install-modal-body" class="install-modal-body"></div>
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

<!-- Install agent command panel -->
<div class="install-panel" id="install-panel">
    <div class="install-panel-header">
        <span><?= _('Install Zabbix Agent') ?></span>
        <button type="button" id="btn-install-close" class="install-panel-close">✕</button>
    </div>
    <div class="install-panel-body">
        <div class="install-panel-label"><?= _('Run on the target host as root:') ?></div>
        <div class="install-cmd-wrap">
            <code id="install-cmd-text" class="install-cmd"></code>
        </div>
        <button type="button" id="btn-install-copy" class="automation-btn" style="margin-top:10px">
            <?= _('Copy') ?>
        </button>
        <span id="install-copy-ok" class="install-copy-ok"></span>
    </div>
</div>

<script>
<?= file_get_contents(__DIR__ . '/js/automation.bulk.hosts.js') ?>
</script>
