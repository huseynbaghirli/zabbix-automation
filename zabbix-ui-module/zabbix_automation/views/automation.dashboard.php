<?php

declare(strict_types=1);

/**
 * @var array $data
 */

$this->includeJsFile('automation.dashboard.js');

$page_title = new CTag('h1', true, $data['title']);

$widget = (new CDiv())
    ->addClass('automation-dashboard');

// ── Summary Cards ──────────────────────────────────────────────────────────
$cards = (new CDiv())->addClass('automation-cards');

$stat_items = [
    ['label' => _('Hosts'),             'value' => $data['hosts_count'],       'icon' => 'zi-host'],
    ['label' => _('Templates'),         'value' => $data['templates_count'],   'icon' => 'zi-template'],
    ['label' => _('Host Groups'),       'value' => $data['hostgroups_count'],  'icon' => 'zi-folder'],
    ['label' => _('Maintenances'),      'value' => $data['maintenance_count'], 'icon' => 'zi-maintenance'],
];

foreach ($stat_items as $stat) {
    $card = (new CDiv([
        (new CDiv())->addClass('automation-card-icon ' . $stat['icon']),
        (new CDiv($stat['value']))->addClass('automation-card-value'),
        (new CDiv($stat['label']))->addClass('automation-card-label'),
    ]))->addClass('automation-card');
    $cards->addItem($card);
}

$widget->addItem($cards);

// ── Quick Links ─────────────────────────────────────────────────────────────
$quick_links = (new CDiv())->addClass('automation-quick-links');

$links = [
    ['label' => _('Bulk Host Manager'),  'action' => 'automation.bulk.hosts',  'desc' => _('Create, update, or delete multiple hosts at once.')],
    ['label' => _('Template Sync'),      'action' => 'automation.templates',   'desc' => _('Export or import Zabbix templates as JSON/XML.')],
    ['label' => _('Maintenance Manager'),'action' => 'automation.maintenance', 'desc' => _('Schedule maintenance windows for hosts or host groups.')],
];

foreach ($links as $link) {
    $quick_links->addItem(
        (new CDiv([
            (new CLink($link['label'], (new CUrl('zabbix.php'))->setArgument('action', $link['action'])))->addClass('automation-link-title'),
            (new CDiv($link['desc']))->addClass('automation-link-desc'),
        ]))->addClass('automation-quick-link-item')
    );
}

$widget->addItem(
    (new CDiv([
        (new CTag('h2', true, _('Quick Links')))->addClass('automation-section-title'),
        $quick_links,
    ]))->addClass('automation-section')
);

echo $page_title;
echo $widget;
