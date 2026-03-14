<?php

/**
 * Serves install-zabbix.sh without requiring a Zabbix session.
 *
 * Access: http://<zabbix-host>/modules/zabbix-installer/install.php
 *
 * Example:
 *   bash <(curl -fsSL http://10.0.0.1/modules/zabbix-installer/install.php) \
 *       --server-host '10.0.0.1' --hostname 'myserver'
 */

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$script = __DIR__ . '/install-zabbix.sh';

if (!is_file($script)) {
    http_response_code(404);
    echo "# Error: install-zabbix.sh not found\n";
    exit;
}

readfile($script);
