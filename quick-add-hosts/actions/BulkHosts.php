<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;

class BulkHosts extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $groups = API::HostGroup()->get([
            'output'    => ['groupid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ]);

        $templates = API::Template()->get([
            'output'    => ['templateid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ]);

        $proxies = [];
        try {
            // Zabbix 7.x: proxy has a direct 'address' field (passive proxy = filled, active = empty).
            $proxies = API::Proxy()->get([
                'output'    => ['proxyid', 'name', 'address', 'operating_mode'],
                'sortfield' => 'name',
                'sortorder' => 'ASC',
            ]);
        } catch (\Throwable $e) {
            try {
                // Zabbix 6.x: passive proxy address lives in its interface record.
                $raw = API::Proxy()->get([
                    'output'          => ['proxyid', 'name'],
                    'selectInterface' => ['ip', 'dns', 'useip'],
                    'sortfield'       => 'name',
                    'sortorder'       => 'ASC',
                ]);
                foreach ($raw as &$p) {
                    $iface       = $p['interface'] ?? [];
                    $p['address'] = ($iface['useip'] ?? '1') == '1'
                        ? ($iface['ip']  ?? '')
                        : ($iface['dns'] ?? '');
                }
                $proxies = $raw;
            } catch (\Throwable $e2) {
                // Proxy API not available — leave empty.
            }
        }

        // Zabbix server address for agent config.
        // ZBX_SERVER constant is often 'localhost'/'127.0.0.1' when the server
        // runs on the same host as the frontend — useless for remote agents.
        // Fall back to the web server's own address in that case.
        $zbx_const = defined('ZBX_SERVER') ? ZBX_SERVER : '';
        $local      = ['', 'localhost', '127.0.0.1', '::1'];
        if (!in_array($zbx_const, $local, true)) {
            $zabbix_server = $zbx_const;
        } else {
            // Use SERVER_ADDR (real IP) or strip port from HTTP_HOST
            $zabbix_server = $_SERVER['SERVER_ADDR']
                ?? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '')
                ?? '';
        }

        $this->setResponse(new CControllerResponseData([
            'title'         => _('Quick Add Hosts'),
            'groups'        => $groups,
            'templates'     => $templates,
            'proxies'       => $proxies,
            'zabbix_server' => $zabbix_server,
            'user'          => ['debug_mode' => $this->getDebugMode()],
        ]));
    }
}
