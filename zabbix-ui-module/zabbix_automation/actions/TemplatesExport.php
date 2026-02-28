<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use CController;
use CControllerResponseData;

/**
 * Exports selected templates as JSON (Zabbix 7.0+ format) or XML (≤6.4).
 *
 * POST params:
 *   templateids[] - array of template IDs to export
 *   format        - "json" (default) or "xml"
 */
class TemplatesExport extends CController {

    protected function checkInput(): bool {
        $fields = [
            'templateids' => 'required|array',
            'format'      => 'string|in json,xml',
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(
                new CControllerResponseData(['main_block' => json_encode([
                    'error' => ['title' => _('Invalid input'), 'messages' => []]
                ])])
            );
        }

        return $ret;
    }

    protected function checkPermissions(): bool {
        return $this->checkAccess(CRoleHelper::UI_DEFAULT_ACCESS);
    }

    protected function doAction(): void {
        $template_ids = $this->getInput('templateids', []);
        $format       = $this->getInput('format', 'json');

        $export_format = ($format === 'xml')
            ? CExportWriterXml::FORMAT
            : CExportWriterJSON::FORMAT;

        $content = API::Configuration()->export([
            'format'  => $export_format,
            'options' => [
                'templates' => $template_ids,
            ],
        ]);

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'content' => $content,
                'format'  => $format,
            ])
        ]));
    }
}
