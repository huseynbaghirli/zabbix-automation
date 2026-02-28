<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;

/**
 * Handles AJAX submission of the Bulk Host Manager form.
 *
 * POST params (multipart/form-data):
 *   action     - "create" | "update" | "delete"
 *   hosts_json - JSON-encoded array of host definition objects
 */
class BulkHostsSubmit extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'action'     => 'required|string|in create,update,delete',
            'hosts_json' => 'required|string',
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Invalid input'), 'messages' => []],
                ]),
            ]));
        }

        return $ret;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $action     = $this->getInput('action');
        $hosts_json = $this->getInput('hosts_json', '[]');

        $hosts = json_decode($hosts_json, true);

        if (!is_array($hosts)) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Invalid JSON in hosts_json'), 'messages' => []],
                ]),
            ]));
            return;
        }

        $result = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => []];

        foreach ($hosts as $host_data) {
            $host_name = $host_data['host'] ?? '';

            if ($host_name === '') {
                $result['errors'][] = _('Host name is required.');
                continue;
            }

            try {
                $existing = API::Host()->get([
                    'output' => ['hostid'],
                    'filter' => ['host' => $host_name],
                ]);

                switch ($action) {
                    case 'create':
                        if ($existing) {
                            $result['errors'][] = sprintf(_('Host "%s" already exists, skipped.'), $host_name);
                            break;
                        }

                        API::Host()->create([
                            'host'       => $host_name,
                            'name'       => $host_data['name'] ?? $host_name,
                            'interfaces' => [[
                                'type'  => 1,
                                'main'  => 1,
                                'useip' => 1,
                                'ip'    => $host_data['ip'] ?? '127.0.0.1',
                                'dns'   => '',
                                'port'  => $host_data['port'] ?? '10050',
                            ]],
                            'groups'    => array_map(
                                fn($gid) => ['groupid' => $gid],
                                (array) ($host_data['group_ids'] ?? [])
                            ),
                            'templates' => array_map(
                                fn($tid) => ['templateid' => $tid],
                                (array) ($host_data['template_ids'] ?? [])
                            ),
                        ]);
                        $result['created']++;
                        break;

                    case 'update':
                        if (!$existing) {
                            $result['errors'][] = sprintf(_('Host "%s" not found for update.'), $host_name);
                            break;
                        }

                        $upd = ['hostid' => $existing[0]['hostid']];

                        if (isset($host_data['name'])) {
                            $upd['name'] = $host_data['name'];
                        }
                        if (!empty($host_data['group_ids'])) {
                            $upd['groups'] = array_map(fn($g) => ['groupid' => $g], $host_data['group_ids']);
                        }
                        if (!empty($host_data['template_ids'])) {
                            $upd['templates'] = array_map(fn($t) => ['templateid' => $t], $host_data['template_ids']);
                        }

                        API::Host()->update($upd);
                        $result['updated']++;
                        break;

                    case 'delete':
                        if (!$existing) {
                            $result['errors'][] = sprintf(_('Host "%s" not found for deletion.'), $host_name);
                            break;
                        }

                        API::Host()->delete([$existing[0]['hostid']]);
                        $result['deleted']++;
                        break;
                }
            } catch (\Exception $e) {
                $result['errors'][] = sprintf(_('Error processing "%s": %s'), $host_name, $e->getMessage());
            }
        }

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode($result),
        ]));
    }
}
