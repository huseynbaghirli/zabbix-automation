<?php

declare(strict_types=1);

/**
 * @var array $data
 */

echo '<style>' . file_get_contents(dirname(__DIR__) . '/assets/css/automation.css') . '</style>';

$now  = date('Y-m-d\TH:i', time());
$plus = date('Y-m-d\TH:i', time() + 3600);

?>
<h1><?= htmlspecialchars($data['title']) ?></h1>

<div class="automation-two-col">

    <!-- ── Create form ── -->
    <div class="automation-section">
        <h2 class="automation-section-title"><?= _('Create Maintenance Window') ?></h2>

        <form id="maintenance-form">

            <div class="automation-form-row">
                <label for="maintenance_name" class="required"><?= _('Name') ?></label>
                <input
                    type="text"
                    name="maintenance_name"
                    id="maintenance_name"
                    class="automation-input"
                    placeholder="<?= htmlspecialchars(_('Maintenance window name')) ?>"
                >
            </div>

            <div class="automation-form-row">
                <label for="maintenance_description"><?= _('Description') ?></label>
                <textarea
                    name="maintenance_description"
                    id="maintenance_description"
                    class="automation-textarea"
                    rows="3"
                ></textarea>
            </div>

            <div class="automation-form-row">
                <label for="maintenance_type"><?= _('Type') ?></label>
                <select name="maintenance_type" id="maintenance_type" class="automation-select">
                    <option value="0"><?= _('With data collection') ?></option>
                    <option value="1"><?= _('No data collection') ?></option>
                </select>
            </div>

            <div class="automation-form-row">
                <label for="active_since" class="required"><?= _('Active since') ?></label>
                <input
                    type="datetime-local"
                    name="active_since"
                    id="active_since"
                    class="automation-input"
                    value="<?= htmlspecialchars($now) ?>"
                >
            </div>

            <div class="automation-form-row">
                <label for="active_till" class="required"><?= _('Active till') ?></label>
                <input
                    type="datetime-local"
                    name="active_till"
                    id="active_till"
                    class="automation-input"
                    value="<?= htmlspecialchars($plus) ?>"
                >
            </div>

            <div class="automation-form-row">
                <label for="host_ids"><?= _('Hosts') ?></label>
                <div>
                    <select
                        name="host_ids[]"
                        id="host_ids"
                        class="automation-select"
                        multiple
                        size="7"
                    >
                        <?php foreach ($data['hosts'] as $host): ?>
                        <option value="<?= htmlspecialchars($host['hostid']) ?>">
                            <?= htmlspecialchars($host['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="automation-hint"><?= _('Hold Ctrl / Cmd to select multiple.') ?></div>
                </div>
            </div>

            <div class="automation-form-row">
                <label for="group_ids"><?= _('Host Groups') ?></label>
                <div>
                    <select
                        name="group_ids[]"
                        id="group_ids"
                        class="automation-select"
                        multiple
                        size="7"
                    >
                        <?php foreach ($data['groups'] as $group): ?>
                        <option value="<?= htmlspecialchars($group['groupid']) ?>">
                            <?= htmlspecialchars($group['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="automation-hint"><?= _('Hold Ctrl / Cmd to select multiple.') ?></div>
                </div>
            </div>

            <div class="form-buttons">
                <button type="button" id="btn-create-maintenance" class="automation-btn">
                    <?= _('Create Maintenance') ?>
                </button>
            </div>

        </form>

        <div id="maintenance-result" class="automation-results hidden"></div>
    </div>

    <!-- ── Existing maintenances ── -->
    <div class="automation-section">
        <h2 class="automation-section-title"><?= _('Existing Maintenance Windows') ?></h2>

        <?php if ($data['maintenances']): ?>
        <table class="automation-table">
            <thead>
                <tr>
                    <th><?= _('Name') ?></th>
                    <th><?= _('Type') ?></th>
                    <th><?= _('Active since') ?></th>
                    <th><?= _('Active till') ?></th>
                    <th><?= _('Hosts') ?></th>
                    <th><?= _('Groups') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['maintenances'] as $m): ?>
                <?php
                    $type_html   = ((int) $m['maintenance_type'] === 0)
                        ? '<span class="status-green">' . _('With data') . '</span>'
                        : '<span class="status-grey">'  . _('No data')   . '</span>';
                    $host_names  = array_column($m['hosts'],  'name');
                    $group_names = array_column($m['groups'], 'name');
                ?>
                <tr>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><?= $type_html ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i', (int) $m['active_since'])) ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i', (int) $m['active_till']))  ?></td>
                    <td><?= $host_names  ? htmlspecialchars(implode(', ', $host_names))  : '—' ?></td>
                    <td><?= $group_names ? htmlspecialchars(implode(', ', $group_names)) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#888;font-style:italic"><?= _('No maintenance windows found.') ?></p>
        <?php endif; ?>
    </div>

</div>

<script>
<?= file_get_contents(__DIR__ . '/js/automation.maintenance.js') ?>
</script>
