<?php

declare(strict_types=1);

namespace Modules\ZabbixAutomation;

use APP;
use CMenu;
use CMenuItem;
use Zabbix\Core\CModule;

class Module extends CModule {

    public function getStylesheets(): array {
        return ['assets/css/automation.css'];
    }

    public function init(): void {
        $menu = APP::Component()->get('menu.main');

        $menu->add(
            (new CMenuItem(_('Automation')))
                ->setIcon('zi-alert-with-content')
                ->setSubMenu(new CMenu([
                    (new CMenuItem(_('Dashboard')))
                        ->setAction('automation.dashboard'),
                    (new CMenuItem(_('Quick Add Hosts')))
                        ->setAction('automation.bulk.hosts'),
                ]))
        );
    }
}
