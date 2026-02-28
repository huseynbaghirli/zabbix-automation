<?php

declare(strict_types=1);

/**
 * @var array $data
 */

$this->includeJsFile('automation.maintenance.js');

echo '<link rel="stylesheet" href="modules/zabbix_automation/assets/css/automation.css">';

echo (new CTag('h1', true, $data['title']));

// ── Create Maintenance Form ───────────────────────────────────────────────────
$now = time();

$hosts_multiselect = (new CMultiSelect([
    'name'        => 'host_ids[]',
    'object_name' => 'hosts',
    'data'        => [],
    'popup'       => [
        'parameters' => [
            'srctbl'  => 'hosts',
            'srcfld1' => 'hostid',
            'dstfrm'  => 'maintenance-form',
            'dstfld1' => 'host_ids_',
        ],
    ],
]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

$groups_multiselect = (new CMultiSelect([
    'name'        => 'group_ids[]',
    'object_name' => 'hostgroups',
    'data'        => [],
    'popup'       => [
        'parameters' => [
            'srctbl'  => 'host_groups',
            'srcfld1' => 'groupid',
            'dstfrm'  => 'maintenance-form',
            'dstfld1' => 'group_ids_',
        ],
    ],
]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

$type_select = (new CSelect('maintenance_type'))
    ->addOptions(CSelect::createOptionsFromArray([
        0 => _('With data collection'),
        1 => _('No data collection'),
    ]));

$form = (new CForm('post', 'zabbix.php'))
    ->setAttribute('id', 'maintenance-form')
    ->addItem([
        (new CFormGrid())
            ->addItem([
                (new CLabel(_('Name'), 'maintenance_name'))->setAsteriskMark(),
                new CFormField(
                    (new CTextBox('maintenance_name'))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                ),
            ])
            ->addItem([
                new CLabel(_('Description'), 'maintenance_description'),
                new CFormField(
                    (new CTextArea('maintenance_description'))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)->setAttribute('rows', 3)
                ),
            ])
            ->addItem([
                new CLabel(_('Type'), 'maintenance_type'),
                new CFormField($type_select),
            ])
            ->addItem([
                (new CLabel(_('Active since'), 'active_since'))->setAsteriskMark(),
                new CFormField(
                    (new CDateSelector('active_since', date(ZBX_DATE_TIME, $now)))
                        ->setDateFormat(ZBX_DATE_TIME)
                        ->setAttribute('placeholder', ZBX_DATE_TIME)
                ),
            ])
            ->addItem([
                (new CLabel(_('Active till'), 'active_till'))->setAsteriskMark(),
                new CFormField(
                    (new CDateSelector('active_till', date(ZBX_DATE_TIME, $now + 3600)))
                        ->setDateFormat(ZBX_DATE_TIME)
                        ->setAttribute('placeholder', ZBX_DATE_TIME)
                ),
            ])
            ->addItem([
                new CLabel(_('Hosts'), 'host_ids__ms'),
                new CFormField($hosts_multiselect),
            ])
            ->addItem([
                new CLabel(_('Host Groups'), 'group_ids__ms'),
                new CFormField($groups_multiselect),
            ]),
        (new CDiv([
            (new CSubmit('create_maintenance', _('Create Maintenance')))->setAttribute('id', 'btn-create-maintenance'),
        ]))->addClass('form-buttons'),
    ]);

$create_result = (new CDiv())->setAttribute('id', 'maintenance-result')->addClass('automation-results hidden');

// ── Existing Maintenances Table ───────────────────────────────────────────────
$table = (new CTableInfo())
    ->setHeader([
        _('Name'),
        _('Type'),
        _('Active since'),
        _('Active till'),
        _('Hosts'),
        _('Groups'),
    ]);

foreach ($data['maintenances'] as $m) {
    $type_label = ((int)$m['maintenance_type'] === 0)
        ? (new CSpan(_('With data')))->addClass('status-green')
        : (new CSpan(_('No data')))->addClass('status-grey');

    $host_names  = array_column($m['hosts'], 'name');
    $group_names = array_column($m['groups'], 'name');

    $table->addRow([
        $m['name'],
        $type_label,
        date(ZBX_DATE_TIME, (int)$m['active_since']),
        date(ZBX_DATE_TIME, (int)$m['active_till']),
        $host_names  ? implode(', ', $host_names)  : '—',
        $group_names ? implode(', ', $group_names) : '—',
    ]);
}

// ── Layout ────────────────────────────────────────────────────────────────────
echo (new CDiv([
    (new CDiv([
        (new CTag('h2', true, _('Create Maintenance Window')))->addClass('automation-section-title'),
        $form,
        $create_result,
    ]))->addClass('automation-section'),
    (new CDiv([
        (new CTag('h2', true, _('Existing Maintenance Windows')))->addClass('automation-section-title'),
        $table,
    ]))->addClass('automation-section'),
]));
