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
        // Fetch all host groups for the form dropdown
        $groups = API::HostGroup()->get([
            'output'     => ['groupid', 'name'],
            'sortfield'  => 'name',
            'sortorder'  => 'ASC',
        ]);

        // Fetch all templates for the form dropdown
        $templates = API::Template()->get([
            'output'    => ['templateid', 'host', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ]);

        $this->setResponse(new CControllerResponseData([
            'title'     => _('Bulk Host Manager'),
            'groups'    => $groups,
            'templates' => $templates,
            'user'      => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}
