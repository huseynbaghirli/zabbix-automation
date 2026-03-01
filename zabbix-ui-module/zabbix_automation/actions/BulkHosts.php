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

        // Zabbix 7.0+ uses API::Proxy(); older versions use API::Host() with proxy filter.
        $proxies = [];
        try {
            $proxies = API::Proxy()->get([
                'output'    => ['proxyid', 'name'],
                'sortfield' => 'name',
                'sortorder' => 'ASC',
            ]);
        } catch (\Throwable $e) {
            // Proxy API not available — leave empty.
        }

        $this->setResponse(new CControllerResponseData([
            'title'     => _('Bulk Host Manager'),
            'groups'    => $groups,
            'templates' => $templates,
            'proxies'   => $proxies,
            'user'      => ['debug_mode' => $this->getDebugMode()],
        ]));
    }
}
