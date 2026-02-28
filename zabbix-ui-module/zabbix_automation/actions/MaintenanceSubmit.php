<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;

/**
 * Creates a maintenance window for selected hosts or host groups.
 *
 * POST params:
 *   name         - maintenance name
 *   description  - optional description
 *   active_since - Unix timestamp (start)
 *   active_till  - Unix timestamp (end)
 *   host_ids[]   - optional list of host IDs
 *   group_ids[]  - optional list of host group IDs
 *   type         - 0 = with data collection, 1 = no data collection
 */
class MaintenanceSubmit extends CController {

    protected function checkInput(): bool {
        $fields = [
            'name'         => 'required|string|not_empty',
            'description'  => 'string',
            'active_since' => 'required|int32',
            'active_till'  => 'required|int32',
            'host_ids'     => 'array_id',
            'group_ids'    => 'array_id',
            'type'         => 'int32|in 0,1',
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
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $name         = $this->getInput('name');
        $description  = $this->getInput('description', '');
        $active_since = (int)$this->getInput('active_since');
        $active_till  = (int)$this->getInput('active_till');
        $host_ids     = $this->getInput('host_ids', []);
        $group_ids    = $this->getInput('group_ids', []);
        $type         = (int)$this->getInput('type', 0);

        if (empty($host_ids) && empty($group_ids)) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Validation error'), 'messages' => [_('Select at least one host or host group.')]]
                ])
            ]));
            return;
        }

        $maintenance = [
            'name'         => $name,
            'description'  => $description,
            'active_since' => $active_since,
            'active_till'  => $active_till,
            'maintenance_type' => $type,
            'timeperiods'  => [[
                'timeperiod_type' => 0, // one-time
                'start_date'      => $active_since,
                'period'          => $active_till - $active_since,
            ]],
        ];

        if ($host_ids) {
            $maintenance['hostids'] = $host_ids;
        }
        if ($group_ids) {
            $maintenance['groupids'] = $group_ids;
        }

        try {
            $result = API::Maintenance()->create($maintenance);

            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'maintenanceid' => $result['maintenanceids'][0]
                ])
            ]));
        }
        catch (\Exception $e) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'error' => ['title' => _('Failed to create maintenance'), 'messages' => [$e->getMessage()]]
                ])
            ]));
        }
    }
}
