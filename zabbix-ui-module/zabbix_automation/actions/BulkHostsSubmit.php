<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use CController;
use CControllerResponseData;

/**
 * Handles AJAX submission of the Bulk Host Manager form.
 * Accepts a JSON array of host definitions and creates/updates them idempotently.
 *
 * POST body (application/json):
 * {
 *   "action": "create" | "update" | "delete",
 *   "hosts": [
 *     {
 *       "host":        "server01",
 *       "name":        "Server 01 (visible)",
 *       "ip":          "192.168.1.10",
 *       "port":        "10050",
 *       "group_ids":   [2, 4],
 *       "template_ids": [10001]
 *     },
 *     ...
 *   ]
 * }
 */
class BulkHostsSubmit extends CController {

    protected function checkInput(): bool {
        $fields = [
            'action' => 'required|string|in create,update,delete',
            'hosts'  => 'required|array',
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
        $action = $this->getInput('action');
        $hosts  = $this->getInput('hosts', []);

        $result   = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => []];

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
                            // Already exists — skip (idempotent)
                            $result['errors'][] = sprintf(_('Host "%s" already exists, skipped.'), $host_name);
                            break;
                        }

                        $interfaces = [[
                            'type'  => 1, // Zabbix agent
                            'main'  => 1,
                            'useip' => 1,
                            'ip'    => $host_data['ip'] ?? '127.0.0.1',
                            'dns'   => '',
                            'port'  => $host_data['port'] ?? '10050',
                        ]];

                        $groups = array_map(
                            fn($gid) => ['groupid' => $gid],
                            (array)($host_data['group_ids'] ?? [])
                        );

                        $templates = array_map(
                            fn($tid) => ['templateid' => $tid],
                            (array)($host_data['template_ids'] ?? [])
                        );

                        API::Host()->create([
                            'host'        => $host_name,
                            'name'        => $host_data['name'] ?? $host_name,
                            'interfaces'  => $interfaces,
                            'groups'      => $groups,
                            'templates'   => $templates,
                        ]);

                        $result['created']++;
                        break;

                    case 'update':
                        if (!$existing) {
                            $result['errors'][] = sprintf(_('Host "%s" not found for update.'), $host_name);
                            break;
                        }

                        $update_data = ['hostid' => $existing[0]['hostid']];

                        if (isset($host_data['name'])) {
                            $update_data['name'] = $host_data['name'];
                        }
                        if (!empty($host_data['group_ids'])) {
                            $update_data['groups'] = array_map(
                                fn($gid) => ['groupid' => $gid],
                                $host_data['group_ids']
                            );
                        }
                        if (!empty($host_data['template_ids'])) {
                            $update_data['templates'] = array_map(
                                fn($tid) => ['templateid' => $tid],
                                $host_data['template_ids']
                            );
                        }

                        API::Host()->update($update_data);
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
            }
            catch (\Exception $e) {
                $result['errors'][] = sprintf(_('Error processing "%s": %s'), $host_name, $e->getMessage());
            }
        }

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode($result)
        ]));
    }
}
