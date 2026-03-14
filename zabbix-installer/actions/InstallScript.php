<?php

declare(strict_types=1);

namespace Modules\ZabbixInstaller\Actions;

use CController;

class InstallScript extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $script = __DIR__ . '/../install-zabbix.sh';

        if (!is_file($script)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
            echo "# Error: install-zabbix.sh not found\n";
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        readfile($script);
        exit;
    }
}
