<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use CController;
use CControllerResponseData;

class Maintenance extends CController {

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
        // Existing maintenance windows
        $maintenances = API::Maintenance()->get([
            'output'             => ['maintenanceid', 'name', 'maintenance_type', 'active_since', 'active_till', 'description'],
            'selectGroups'       => ['groupid', 'name'],
            'selectHosts'        => ['hostid', 'name'],
            'selectTimeperiods'  => 'extend',
            'sortfield'          => 'name',
            'sortorder'          => 'ASC',
        ]);

        // Host groups for the form
        $groups = API::HostGroup()->get([
            'output'    => ['groupid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ]);

        // Hosts for the form
        $hosts = API::Host()->get([
            'output'    => ['hostid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ]);

        $this->setResponse(new CControllerResponseData([
            'title'        => _('Maintenance Manager'),
            'maintenances' => $maintenances,
            'groups'       => $groups,
            'hosts'        => $hosts,
            'user'         => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}
