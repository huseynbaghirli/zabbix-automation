<?php

declare(strict_types=1);

/**
 * @var array $data
 */

echo '<style>' . file_get_contents(dirname(__DIR__) . '/assets/css/automation.css') . '</style>';

$placeholder = json_encode([[
    'host'         => 'server01',
    'name'         => 'Server 01',
    'ip'           => '192.168.1.10',
    'port'         => '10050',
    'group_ids'    => [2],
    'template_ids' => [10001],
]], JSON_PRETTY_PRINT);

?>
<h1><?= htmlspecialchars($data['title']) ?></h1>

<p class="automation-description">
    <?= _('Paste host definitions below (one JSON object per line or a JSON array). Choose an action and click Execute.') ?>
</p>

<div class="automation-two-col">

    <!-- ── Main: form ── -->
    <div class="automation-section automation-col-main">
        <h2 class="automation-section-title"><?= _('Host Definitions') ?></h2>

        <form id="automation-bulk-form">

            <div class="automation-form-row">
                <label for="bulk_action"><?= _('Action') ?></label>
                <select name="bulk_action" id="bulk_action" class="automation-select">
                    <option value="create"><?= _('Create hosts') ?></option>
                    <option value="update"><?= _('Update hosts') ?></option>
                    <option value="delete"><?= _('Delete hosts') ?></option>
                </select>
            </div>

            <div class="automation-form-row">
                <label><?= _('Host definitions (JSON)') ?></label>
                <textarea
                    name="hosts_json"
                    id="hosts_json"
                    class="automation-textarea"
                    rows="18"
                    spellcheck="false"
                    placeholder="<?= htmlspecialchars($placeholder) ?>"
                ></textarea>
            </div>

            <div class="form-buttons">
                <button type="button" id="btn-bulk-execute" class="automation-btn"><?= _('Execute') ?></button>
            </div>

        </form>

        <div id="bulk-results" class="automation-results hidden"></div>
    </div>

    <!-- ── Side: reference lists ── -->
    <div class="automation-section automation-col-side">

        <div class="automation-reference-list">
            <strong><?= _('Available Host Groups (click ID to copy):') ?></strong>
            <div class="automation-reference-items">
                <?php if ($data['groups']): ?>
                    <?php foreach ($data['groups'] as $group): ?>
                    <div class="automation-ref-item">
                        <code><?= htmlspecialchars($group['groupid']) ?></code>
                        <?= htmlspecialchars($group['name']) ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="automation-ref-item" style="color:#888"><?= _('No groups found') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="automation-reference-list">
            <strong><?= _('Available Templates (click ID to copy):') ?></strong>
            <div class="automation-reference-items">
                <?php if ($data['templates']): ?>
                    <?php foreach ($data['templates'] as $tpl): ?>
                    <div class="automation-ref-item">
                        <code><?= htmlspecialchars($tpl['templateid']) ?></code>
                        <?= htmlspecialchars($tpl['name']) ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="automation-ref-item" style="color:#888"><?= _('No templates found') ?></div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<script>
<?= file_get_contents(__DIR__ . '/js/automation.bulk.hosts.js') ?>
</script>
