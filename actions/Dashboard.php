<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;

class Dashboard extends CController {

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
        $hosts_count      = API::Host()->get(['countOutput' => true]);
        $hostgroups_count = API::HostGroup()->get(['countOutput' => true]);

        $this->setResponse(new CControllerResponseData([
            'title'            => _('Automation Dashboard'),
            'hosts_count'      => $hosts_count,
            'hostgroups_count' => $hostgroups_count,
            'user'             => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}
