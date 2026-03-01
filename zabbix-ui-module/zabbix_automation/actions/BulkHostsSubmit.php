<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;

/**
 * Handles AJAX submission of the Bulk Host Manager form.
 *
 * POST fields (multipart/form-data):
 *   _csrf_token  - Zabbix CSRF token (generated with action name as context)
 *   host_action  - "create" | "update" | "delete"
 *   hosts_json   - JSON array of host definition objects
 *
 * NOTE: field is named 'host_action', not 'action', to avoid colliding with
 * Zabbix's own 'action' routing parameter in $_REQUEST.
 */
class BulkHostsSubmit extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'host_action' => 'required|string|in create,update,delete',
            'hosts_json'  => 'required|string',
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Invalid input'), 'messages' => []],
                ], JSON_THROW_ON_ERROR),
            ]));
        }

        return $ret;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $action     = $this->getInput('host_action');
        $hosts_json = $this->getInput('hosts_json', '[]');
        $hosts      = json_decode($hosts_json, true);

        if (!is_array($hosts)) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Invalid JSON in hosts_json'), 'messages' => []],
                ], JSON_THROW_ON_ERROR),
            ]));
            return;
        }

        $result = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => []];

        foreach ($hosts as $host_data) {
            $host_name = trim((string) ($host_data['host'] ?? ''));

            if ($host_name === '') {
                $result['errors'][] = _('Host name is required.');
                continue;
            }

            try {
                $existing = API::Host()->get([
                    'output'      => ['hostid'],
                    'filter'      => ['host' => $host_name],
                    'searchLimit' => 1,
                ]);

                switch ($action) {
                    case 'create':
                        if ($existing) {
                            $result['errors'][] = sprintf(
                                _('Host "%s" already exists — skipped.'), $host_name
                            );
                            break;
                        }

                        // Build groups array (at least one is required by the Zabbix API)
                        $group_ids = array_filter(
                            array_map('intval', (array) ($host_data['group_ids'] ?? []))
                        );

                        if (!$group_ids) {
                            $result['errors'][] = sprintf(
                                _('Host "%s" skipped: at least one host group is required.'), $host_name
                            );
                            break;
                        }

                        $create = [
                            'host'       => $host_name,
                            'name'       => $host_name,          // visible name = technical name
                            'status'     => HOST_STATUS_MONITORED,
                            'groups'     => array_values(array_map(
                                fn($gid) => ['groupid' => (string) $gid],
                                $group_ids
                            )),
                            'interfaces' => [[
                                'type'  => INTERFACE_TYPE_AGENT,
                                'main'  => INTERFACE_PRIMARY,
                                'useip' => INTERFACE_USE_IP,
                                'ip'    => (string) ($host_data['ip']   ?? '127.0.0.1'),
                                'dns'   => '',
                                'port'  => (string) ($host_data['port'] ?? '10050'),
                            ]],
                        ];

                        // Templates (optional)
                        $template_ids = array_filter(
                            array_map('intval', (array) ($host_data['template_ids'] ?? []))
                        );
                        if ($template_ids) {
                            $create['templates'] = array_values(array_map(
                                fn($tid) => ['templateid' => (string) $tid],
                                $template_ids
                            ));
                        }

                        // Proxy (Zabbix 7.x uses proxyid)
                        $proxy_id = (int) ($host_data['proxy_id'] ?? 0);
                        if ($proxy_id > 0) {
                            $create['monitored_by'] = ZBX_MONITORED_BY_PROXY;
                            $create['proxyid']      = (string) $proxy_id;
                        }

                        // Tags
                        if (!empty($host_data['tags']) && is_array($host_data['tags'])) {
                            $tags = array_values(array_filter(
                                $host_data['tags'],
                                fn($t) => isset($t['tag']) && $t['tag'] !== ''
                            ));
                            if ($tags) {
                                $create['tags'] = $tags;
                            }
                        }

                        API::Host()->create($create);
                        $result['created']++;
                        break;

                    case 'update':
                        if (!$existing) {
                            $result['errors'][] = sprintf(
                                _('Host "%s" not found for update.'), $host_name
                            );
                            break;
                        }

                        $upd = ['hostid' => $existing[0]['hostid']];

                        $group_ids = array_filter(
                            array_map('intval', (array) ($host_data['group_ids'] ?? []))
                        );
                        if ($group_ids) {
                            $upd['groups'] = array_values(array_map(
                                fn($g) => ['groupid' => (string) $g],
                                $group_ids
                            ));
                        }

                        $template_ids = array_filter(
                            array_map('intval', (array) ($host_data['template_ids'] ?? []))
                        );
                        if ($template_ids) {
                            $upd['templates'] = array_values(array_map(
                                fn($t) => ['templateid' => (string) $t],
                                $template_ids
                            ));
                        }

                        $proxy_id = (int) ($host_data['proxy_id'] ?? 0);
                        if ($proxy_id > 0) {
                            $upd['monitored_by'] = ZBX_MONITORED_BY_PROXY;
                            $upd['proxyid']      = (string) $proxy_id;
                        }

                        if (!empty($host_data['tags']) && is_array($host_data['tags'])) {
                            $upd['tags'] = array_values(array_filter(
                                $host_data['tags'],
                                fn($t) => isset($t['tag']) && $t['tag'] !== ''
                            ));
                        }

                        API::Host()->update($upd);
                        $result['updated']++;
                        break;

                    case 'delete':
                        if (!$existing) {
                            $result['errors'][] = sprintf(
                                _('Host "%s" not found for deletion.'), $host_name
                            );
                            break;
                        }

                        API::Host()->delete([$existing[0]['hostid']]);
                        $result['deleted']++;
                        break;
                }
            } catch (\Exception $e) {
                $result['errors'][] = sprintf(
                    _('Error on "%s": %s'), $host_name, $e->getMessage()
                );
            }
        }

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode($result, JSON_THROW_ON_ERROR),
        ]));
    }
}
