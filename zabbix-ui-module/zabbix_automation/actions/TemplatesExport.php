<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
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
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $template_ids = $this->getInput('templateids', []);
        $format       = $this->getInput('format', 'json');

        $content = API::Configuration()->export([
            'format'  => $format,
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
