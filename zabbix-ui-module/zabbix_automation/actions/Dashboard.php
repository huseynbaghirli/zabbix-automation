<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;
use CRoleHelper;

class Dashboard extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->checkAccess(CRoleHelper::UI_DEFAULT_ACCESS);
    }

    protected function doAction(): void {
        // Gather summary stats via Zabbix API
        $hosts_count = API::Host()->get(['countOutput' => true]);
        $templates_count = API::Template()->get(['countOutput' => true]);
        $hostgroups_count = API::HostGroup()->get(['countOutput' => true]);
        $maintenance_count = API::Maintenance()->get(['countOutput' => true]);

        $this->setResponse(new CControllerResponseData([
            'title'             => _('Automation Dashboard'),
            'hosts_count'       => $hosts_count,
            'templates_count'   => $templates_count,
            'hostgroups_count'  => $hostgroups_count,
            'maintenance_count' => $maintenance_count,
            'user'              => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}
