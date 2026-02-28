<?php

declare(strict_types=1);

/**
 * @var array $data
 */

echo '<style>' . file_get_contents(dirname(__DIR__) . '/assets/css/automation.css') . '</style>';

?>
<h1><?= htmlspecialchars($data['title']) ?></h1>

<div class="automation-two-col">

    <!-- ── Export panel ── -->
    <div class="automation-section">
        <h2 class="automation-section-title"><?= _('Export Templates') ?></h2>

        <form id="export-form">

            <div class="automation-form-row">
                <label for="export-templates"><?= _('Templates') ?></label>
                <div>
                    <select
                        id="export-templates"
                        name="export_templateids[]"
                        class="automation-select"
                        multiple
                        size="8"
                    >
                        <?php foreach ($data['templates'] as $tpl): ?>
                        <option value="<?= htmlspecialchars($tpl['templateid']) ?>">
                            <?= htmlspecialchars($tpl['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="automation-hint"><?= _('Hold Ctrl / Cmd to select multiple.') ?></div>
                </div>
            </div>

            <div class="automation-form-row">
                <label for="export_format"><?= _('Format') ?></label>
                <select name="export_format" id="export_format" class="automation-select">
                    <option value="json"><?= _('JSON (Zabbix 7.0+)') ?></option>
                    <option value="xml"><?= _('XML (Zabbix ≤6.4)') ?></option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="button" id="btn-export" class="automation-btn"><?= _('Export') ?></button>
            </div>

        </form>

        <div id="export-output" class="automation-results hidden"></div>
    </div>

    <!-- ── Import panel ── -->
    <div class="automation-section">
        <h2 class="automation-section-title"><?= _('Import Templates') ?></h2>

        <form id="import-form">

            <div class="automation-form-row">
                <label for="import_format"><?= _('Format') ?></label>
                <select name="import_format" id="import_format" class="automation-select">
                    <option value="json"><?= _('JSON') ?></option>
                    <option value="xml"><?= _('XML') ?></option>
                </select>
            </div>

            <div class="automation-form-row">
                <label for="import_content"><?= _('Content') ?></label>
                <textarea
                    name="import_content"
                    id="import_content"
                    class="automation-textarea"
                    rows="14"
                    spellcheck="false"
                    placeholder="<?= htmlspecialchars(_('Paste JSON or XML template export content here…')) ?>"
                ></textarea>
            </div>

            <div class="form-buttons">
                <button type="button" id="btn-import" class="automation-btn"><?= _('Import') ?></button>
            </div>

        </form>

        <div id="import-result" class="automation-results hidden"></div>
    </div>

</div>

<script>
<?= file_get_contents(__DIR__ . '/js/automation.templates.js') ?>
</script>
