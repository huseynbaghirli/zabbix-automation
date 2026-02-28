<?php

declare(strict_types=1);

/**
 * @var array $data
 */

echo (new CTag('h1', true, $data['title']));

// ── Description ──────────────────────────────────────────────────────────────
echo (new CDiv([
    _('Paste host definitions below (one JSON object per line or a JSON array). Choose an action and click Execute.'),
]))->addClass('automation-description');

// ── Action selector ──────────────────────────────────────────────────────────
$action_select = (new CSelect('bulk_action'))
    ->addOptions(CSelect::createOptionsFromArray([
        'create' => _('Create hosts'),
        'update' => _('Update hosts'),
        'delete' => _('Delete hosts'),
    ]))
    ->setFocused();

// ── Group / Template helpers ──────────────────────────────────────────────────
$group_list = (new CDiv())->addClass('automation-reference-list');
$group_list->addItem((new CTag('strong', true, _('Available Host Groups (copy IDs):'))));
$group_items = (new CDiv())->addClass('automation-reference-items');
foreach ($data['groups'] as $group) {
    $group_items->addItem(
        (new CDiv(
            (new CTag('code', true, $group['groupid']))->setAttribute('title', $group['name'])
            . ' ' . htmlspecialchars($group['name'])
        ))->addClass('automation-ref-item')
    );
}
$group_list->addItem($group_items);

$template_list = (new CDiv())->addClass('automation-reference-list');
$template_list->addItem((new CTag('strong', true, _('Available Templates (copy IDs):'))));
$tpl_items = (new CDiv())->addClass('automation-reference-items');
foreach ($data['templates'] as $tpl) {
    $tpl_items->addItem(
        (new CDiv(
            (new CTag('code', true, $tpl['templateid']))->setAttribute('title', $tpl['name'])
            . ' ' . htmlspecialchars($tpl['name'])
        ))->addClass('automation-ref-item')
    );
}
$template_list->addItem($tpl_items);

// ── JSON input textarea ───────────────────────────────────────────────────────
$placeholder = json_encode([
    [
        'host'         => 'server01',
        'name'         => 'Server 01',
        'ip'           => '192.168.1.10',
        'port'         => '10050',
        'group_ids'    => [2],
        'template_ids' => [10001],
    ],
], JSON_PRETTY_PRINT);

$textarea = (new CTextArea('hosts_json'))
    ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    ->setMaxlength(null)
    ->setAttribute('rows', 20)
    ->setAttribute('placeholder', $placeholder)
    ->setAttribute('spellcheck', 'false');

// ── Form ──────────────────────────────────────────────────────────────────────
$form = (new CForm('post', 'zabbix.php'))
    ->setAttribute('id', 'automation-bulk-form')
    ->addItem([
        (new CFormGrid())
            ->addItem([
                new CLabel(_('Action'), 'bulk_action'),
                new CFormField($action_select),
            ])
            ->addItem([
                new CLabel(_('Host definitions (JSON)')),
                new CFormField($textarea),
            ]),
        (new CDiv([
            (new CSubmit('execute', _('Execute')))
                ->setAttribute('id', 'btn-bulk-execute'),
        ]))->addClass('form-buttons'),
    ]);

// ── Results area ──────────────────────────────────────────────────────────────
$results = (new CDiv())->setAttribute('id', 'bulk-results')->addClass('automation-results hidden');

// ── Layout ────────────────────────────────────────────────────────────────────
echo (new CDiv([
    (new CDiv([
        (new CTag('h2', true, _('Host Definitions')))->addClass('automation-section-title'),
        $form,
        $results,
    ]))->addClass('automation-section automation-col-main'),
    (new CDiv([
        $group_list,
        $template_list,
    ]))->addClass('automation-section automation-col-side'),
]))->addClass('automation-two-col');

echo '<script>' . file_get_contents(__DIR__ . '/js/automation.bulk.hosts.js') . '</script>';
