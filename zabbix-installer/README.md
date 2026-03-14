# zabbix-installer

Serves `install-zabbix.sh` as plain text via Apache `.htaccess`.
No Apache/Nginx config changes required.

## File structure

```
zabbix-installer/
├── .htaccess          ← forces text/plain for install-zabbix.sh
├── manifest.json
├── Module.php         ← required for Zabbix to recognise the module directory
├── install-zabbix.sh
└── README.md
```

## 1 — Deploy

```bash
git clone <repo> /tmp/zabbix-automation
cp -r /tmp/zabbix-automation/zabbix-installer \
      /usr/share/zabbix/ui/modules/zabbix-installer
chown -R www-data:www-data /usr/share/zabbix/ui/modules/zabbix-installer
```

## 2 — Enable module in Zabbix UI

**Administration → General → Modules → Scan directory → Enable "Zabbix Installer"**

(`Module.php` is required so Zabbix recognises the directory as a module.)

## 3 — Usage

```bash
# Basic install (agent2, Zabbix 7.4)
curl -s 'http://<zabbix-host>/zabbix/modules/zabbix-installer/install-zabbix.sh' | bash -s -- \
  --server-host '10.90.27.190' --hostname 'myserver'

# Install agentd instead of agent2
curl -s 'http://<zabbix-host>/zabbix/modules/zabbix-installer/install-zabbix.sh' | bash -s -- \
  --agent --server-host '10.90.27.190' --hostname 'myserver'

# Interactive: prompt for server and hostname
curl -s 'http://<zabbix-host>/zabbix/modules/zabbix-installer/install-zabbix.sh' | bash -s -- \
  --server-host-stdin --hostname-stdin

# Uninstall
curl -s 'http://<zabbix-host>/zabbix/modules/zabbix-installer/install-zabbix.sh' | bash -s -- \
  --uninstall
```

## 4 — Test

```bash
curl -s 'http://<zabbix-host>/zabbix/modules/zabbix-installer/install-zabbix.sh' | head -3
```

Expected output:
```
#!/bin/bash

# Zabbix Agent/Agent2 Install Script (PSK-free)
```

## Supported OS

| Family | Distributions |
|--------|--------------|
| Debian/Ubuntu | Ubuntu, Debian, Raspbian |
| RHEL | RHEL, CentOS, AlmaLinux, Rocky, Oracle, Amazon Linux |
| SLES | SLES, openSUSE Leap |

## Script flags

| Flag | Description |
|------|-------------|
| `--install` | Install Zabbix agent and configure (default) |
| `--reinstall` | Uninstall then install |
| `--uninstall` | Remove Zabbix from system |
| `--configure` | Only update config (agent already installed) |
| `--agent` | Use agentd |
| `--agent2` | Use agent2 (default) |
| `--version X.Y` | Zabbix version (default: 7.4) |
| `--server-host <ip>` | Zabbix server/proxy address |
| `--server-host-stdin` | Read server address from stdin |
| `--hostname <name>` | Hostname shown in Zabbix |
| `--hostname-stdin` | Read hostname from stdin |
| `--repo-url <url>` | Override repo URL |
