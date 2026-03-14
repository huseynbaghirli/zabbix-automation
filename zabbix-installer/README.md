# zabbix-installer

Zabbix 7.x frontend module that serves `install-zabbix.sh` via Zabbix action routing — no authentication required.

## File structure

```
/usr/share/zabbix/modules/zabbix-installer/
├── manifest.json
├── Module.php
├── install-zabbix.sh
└── actions/
    └── InstallScript.php
```

## Module install

```bash
git clone <repo> /tmp/zabbix-automation
cp -r /tmp/zabbix-automation/zabbix-installer /usr/share/zabbix/modules/zabbix-installer
chown -R www-data:www-data /usr/share/zabbix/modules/zabbix-installer
```

## Enable in Zabbix UI

Administration → General → Modules → Enable **Zabbix Installer**

## Usage examples

```bash
# Basic install (agent2, Zabbix 7.4)
curl -s 'http://<zabbix-host>/zabbix/zabbix.php?action=zabbix-installer.script' | bash -s -- \
  --server-host '10.90.27.190' --hostname 'myserver'

# Install agentd instead of agent2
curl -s 'http://<zabbix-host>/zabbix/zabbix.php?action=zabbix-installer.script' | bash -s -- \
  --agent --server-host '10.90.27.190' --hostname 'myserver'

# Interactive: prompt for server and hostname
curl -s 'http://<zabbix-host>/zabbix/zabbix.php?action=zabbix-installer.script' | bash -s -- \
  --server-host-stdin --hostname-stdin

# Uninstall
curl -s 'http://<zabbix-host>/zabbix/zabbix.php?action=zabbix-installer.script' | bash -s -- \
  --uninstall

# Reinstall with specific Zabbix version
curl -s 'http://<zabbix-host>/zabbix/zabbix.php?action=zabbix-installer.script' | bash -s -- \
  --reinstall --version 7.2 --server-host '10.90.27.190' --hostname 'myserver'
```

## Test

```bash
curl -s 'http://10.120.51.100/zabbix/zabbix.php?action=zabbix-installer.script' | head -5
# Expected: first 5 lines of install-zabbix.sh (shebang + comments)
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
