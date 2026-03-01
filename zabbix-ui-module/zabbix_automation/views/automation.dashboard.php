<?php

declare(strict_types=1);

/**
 * @var array $data
 */

echo '<style>' . file_get_contents(dirname(__DIR__) . '/assets/css/automation.css') . '</style>';

?>
<h1><?= htmlspecialchars($data['title']) ?></h1>

<div class="automation-dashboard">

    <!-- ── Summary cards ── -->
    <div class="automation-cards">
        <?php
        $stats = [
            [_('Hosts'),       $data['hosts_count']],
            [_('Host Groups'), $data['hostgroups_count']],
        ];
        foreach ($stats as [$label, $value]):
        ?>
        <div class="automation-card">
            <div class="automation-card-value"><?= (int) $value ?></div>
            <div class="automation-card-label"><?= htmlspecialchars($label) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Quick links ── -->
    <div class="automation-section">
        <h2 class="automation-section-title"><?= _('Quick Links') ?></h2>
        <div class="automation-quick-links">

            <div class="automation-quick-link-item">
                <a href="zabbix.php?action=automation.bulk.hosts" class="automation-link-title"><?= _('Bulk Host Manager') ?></a>
                <div class="automation-link-desc"><?= _('Create, update, or delete multiple hosts at once.') ?></div>
            </div>

        </div>
    </div>

</div>
