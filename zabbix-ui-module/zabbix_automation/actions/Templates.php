<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation\Actions;

use API;
use CController;
use CControllerResponseData;
use CRoleHelper;

class Templates extends CController {

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
        $templates = API::Template()->get([
            'output'     => ['templateid', 'host', 'name', 'description'],
            'sortfield'  => 'name',
            'sortorder'  => 'ASC',
        ]);

        $template_groups = API::TemplateGroup()->get([
            'output'    => ['groupid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ]);

        $this->setResponse(new CControllerResponseData([
            'title'           => _('Template Sync'),
            'templates'       => $templates,
            'template_groups' => $template_groups,
            'user'            => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}
