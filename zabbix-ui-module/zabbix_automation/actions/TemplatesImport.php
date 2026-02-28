<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;
use CRoleHelper;

/**
 * Imports templates from uploaded JSON or XML content.
 *
 * POST params:
 *   content - raw JSON or XML string
 *   format  - "json" or "xml"
 *   rules   - import rules object (optional, uses safe defaults)
 */
class TemplatesImport extends CController {

    protected function checkInput(): bool {
        $fields = [
            'content' => 'required|string',
            'format'  => 'required|string|in json,xml',
            'rules'   => 'array',
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
        $content = $this->getInput('content');
        $format  = $this->getInput('format', 'json');

        // Safe default import rules: create new, update existing
        $default_rules = [
            'templates'          => ['createMissing' => true, 'updateExisting' => true],
            'items'              => ['createMissing' => true, 'updateExisting' => true, 'deleteMissing' => false],
            'triggers'           => ['createMissing' => true, 'updateExisting' => true, 'deleteMissing' => false],
            'graphs'             => ['createMissing' => true, 'updateExisting' => true, 'deleteMissing' => false],
            'discoveryRules'     => ['createMissing' => true, 'updateExisting' => true, 'deleteMissing' => false],
            'httptests'          => ['createMissing' => true, 'updateExisting' => true, 'deleteMissing' => false],
            'valueMaps'          => ['createMissing' => true, 'updateExisting' => true],
        ];

        $rules = $this->getInput('rules', $default_rules);

        try {
            $result = API::Configuration()->import([
                'format'  => $format,
                'source'  => $content,
                'rules'   => $rules,
            ]);

            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['success' => $result])
            ]));
        }
        catch (\Exception $e) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Import failed'), 'messages' => [$e->getMessage()]]
                ])
            ]));
        }
    }
}
