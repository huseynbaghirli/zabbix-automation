<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
readfile(__DIR__ . '/install-zabbix-agent.sh');
