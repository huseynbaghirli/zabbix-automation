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
            $proxies = API::Proxy()->get([
                'output'    => ['proxyid', 'name', 'address'],
                'sortfield' => 'name',
                'sortorder' => 'ASC',
            ]);
        } catch (\Throwable $e) {
            // Proxy API not available — leave empty.
        }

        // Zabbix server address for agent config (frontend config constant).
        $zabbix_server = defined('ZBX_SERVER') ? ZBX_SERVER : '';

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
