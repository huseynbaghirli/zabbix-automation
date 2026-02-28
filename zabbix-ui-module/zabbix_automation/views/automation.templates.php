<?php

declare(strict_types=1);

/**
 * @var array $data
 */

$this->includeJsFile('automation.templates.js');

echo '<link rel="stylesheet" href="modules/zabbix_automation/assets/css/automation.css">';

echo (new CTag('h1', true, $data['title']));

// ── Export panel ──────────────────────────────────────────────────────────────
$template_options = [];
foreach ($data['templates'] as $tpl) {
    $template_options[$tpl['templateid']] = $tpl['name'];
}

$export_select = (new CMultiSelect([
    'name'             => 'export_templateids[]',
    'object_name'      => 'templates',
    'data'             => [],
    'popup'            => [
        'parameters' => [
            'srctbl'  => 'templates',
            'srcfld1' => 'templateid',
            'dstfrm'  => 'export-form',
            'dstfld1' => 'export_templateids_',
        ],
    ],
]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

$format_select = (new CSelect('export_format'))
    ->addOptions(CSelect::createOptionsFromArray([
        'json' => _('JSON (Zabbix 7.0+)'),
        'xml'  => _('XML (Zabbix ≤6.4)'),
    ]));

$export_form = (new CForm('post', 'zabbix.php'))
    ->setAttribute('id', 'export-form')
    ->addItem([
        (new CFormGrid())
            ->addItem([
                new CLabel(_('Templates'), 'export_templateids__ms'),
                new CFormField($export_select),
            ])
            ->addItem([
                new CLabel(_('Format'), 'export_format'),
                new CFormField($format_select),
            ]),
        (new CDiv([
            (new CSubmit('export_btn', _('Export')))->setAttribute('id', 'btn-export'),
        ]))->addClass('form-buttons'),
    ]);

$export_output = (new CDiv())->setAttribute('id', 'export-output')->addClass('automation-results hidden');

// ── Import panel ──────────────────────────────────────────────────────────────
$import_textarea = (new CTextArea('import_content'))
    ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    ->setMaxlength(null)
    ->setAttribute('rows', 16)
    ->setAttribute('placeholder', _('Paste JSON or XML template export content here…'))
    ->setAttribute('spellcheck', 'false');

$import_format = (new CSelect('import_format'))
    ->addOptions(CSelect::createOptionsFromArray([
        'json' => _('JSON'),
        'xml'  => _('XML'),
    ]));

$import_form = (new CForm('post', 'zabbix.php'))
    ->setAttribute('id', 'import-form')
    ->addItem([
        (new CFormGrid())
            ->addItem([
                new CLabel(_('Format'), 'import_format'),
                new CFormField($import_format),
            ])
            ->addItem([
                new CLabel(_('Content')),
                new CFormField($import_textarea),
            ]),
        (new CDiv([
            (new CSubmit('import_btn', _('Import')))->setAttribute('id', 'btn-import'),
        ]))->addClass('form-buttons'),
    ]);

$import_result = (new CDiv())->setAttribute('id', 'import-result')->addClass('automation-results hidden');

// ── Layout ────────────────────────────────────────────────────────────────────
echo (new CDiv([
    (new CDiv([
        (new CTag('h2', true, _('Export Templates')))->addClass('automation-section-title'),
        $export_form,
        $export_output,
    ]))->addClass('automation-section'),
    (new CDiv([
        (new CTag('h2', true, _('Import Templates')))->addClass('automation-section-title'),
        $import_form,
        $import_result,
    ]))->addClass('automation-section'),
]))->addClass('automation-two-col');
