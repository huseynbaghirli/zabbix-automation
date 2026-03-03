<?php

declare(strict_types=1);

namespace Modules\UserAlertOverview;

use APP;
use CMenu;
use CMenuItem;
use Zabbix\Core\CModule;

class Module extends CModule {

    public function getStylesheets(): array {
        return ['assets/css/useralerts.css'];
    }

    public function init(): void {
        $menu = APP::Component()->get('menu.main');

        $menu->add(
            (new CMenuItem(_('Automation')))
                ->setIcon('zi-alert-with-content')
                ->setSubMenu(new CMenu([
                    (new CMenuItem(_('User Alert Overview')))
                        ->setAction('useralerts.overview'),
                ]))
        );
    }
}
