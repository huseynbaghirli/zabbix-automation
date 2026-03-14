# zabbix-installer

Zabbix 7.x frontend module that serves `install-zabbix.sh` publicly via
`zabbix.php?action=zabbix-installer.script` — no authentication required.

## Why two steps are needed

Zabbix validates the user session in `zabbix.php` **before** the module
controller is loaded.  `checkPermissions(): true` in the controller only
controls access for already-authenticated users; it cannot bypass the
pre-controller session check.

The reliable solution is an **Nginx intercept**: Nginx rewrites
`?action=zabbix-installer.script` to the static `.sh` file before PHP-FPM
is involved, so no Zabbix auth check ever runs.

## File structure

```
/usr/share/zabbix/ui/modules/zabbix-installer/
├── manifest.json            ← registers action zabbix-installer.script
├── Module.php
├── install-zabbix.sh        ← the bash installer
├── actions/
│   └── InstallScript.php    ← serves script for authenticated browser access
└── nginx-public-script.conf ← Nginx snippet for public (no-auth) curl access
```

## 1 — Deploy the module

```bash
git clone <repo> /tmp/zabbix-automation
cp -r /tmp/zabbix-automation/zabbix-installer \
      /usr/share/zabbix/ui/modules/zabbix-installer
chown -R www-data:www-data /usr/share/zabbix/ui/modules/zabbix-installer
```

## 2 — Enable in Zabbix UI

**Administration → General → Modules → Enable "Zabbix Installer"**

## 3 — Apply the Nginx intercept (required for public curl access)

The file `nginx-public-script.conf` contains a ready-to-use snippet.
Add its two `location` blocks into your Zabbix `server {}` block,
**before** the existing PHP `location` block:

```bash
# Typical Zabbix Nginx config location:
nano /etc/nginx/conf.d/zabbix.conf

# Paste the contents of nginx-public-script.conf inside server { }
# then verify and reload:
nginx -t && systemctl reload nginx
```

> **Path note** — the snippet assumes Zabbix is served under `/zabbix/`.
> If your install is at the root (`/`), change:
> - `location = /zabbix/zabbix.php` → `location = /zabbix.php`
> - `rewrite ^ /zabbix/modules/...` → `rewrite ^ /modules/...`
> - `location = /zabbix/modules/...` → `location = /modules/...`

### Nginx snippet (for reference)

```nginx
location = /zabbix/zabbix.php {
    if ($arg_action = "zabbix-installer.script") {
        rewrite ^ /zabbix/modules/zabbix-installer/install-zabbix.sh last;
    }
    fastcgi_pass   unix:/run/php/php8.3-fpm.sock;
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        fastcgi_params;
}

location = /zabbix/modules/zabbix-installer/install-zabbix.sh {
    default_type "text/plain; charset=utf-8";
    add_header Cache-Control no-store;
    root /usr/share/zabbix/ui;
}
```

## 4 — Test

```bash
# Should print the first 3 lines of install-zabbix.sh (shebang + comment)
curl -s 'http://10.120.51.100/zabbix/zabbix.php?action=zabbix-installer.script' | head -3
```

Expected output:
```
#!/bin/bash

# Zabbix Agent/Agent2 Install Script (PSK-free)
```

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
