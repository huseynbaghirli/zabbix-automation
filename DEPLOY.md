# Deploy: Zabbix Automation UI Module

## What this does

Adds an **"Automation"** section to the Zabbix left-side navigation menu:

| Page               | URL action              | Description                                          |
|--------------------|-------------------------|------------------------------------------------------|
| Dashboard          | `automation.dashboard`  | Summary counters (hosts, host groups) + quick links  |
| Quick Add Hosts  | `automation.bulk.hosts` | Create / update / delete many hosts at once via a table UI |

---

## Requirements

- Zabbix **6.0 LTS** or **7.0+** (module system v2.0)
- PHP 8.0+
- Web server write access to copy files

---

## Installation (3 steps)

### 1 — Copy the module directory to your Zabbix UI

```bash
# Typical path on Debian/Ubuntu packages:
sudo cp -r zabbix_automation /usr/share/zabbix/modules/

# Or if you compiled from source / used RPM:
sudo cp -r zabbix_automation /var/www/html/zabbix/modules/

# Set ownership so the web server can read the files:
sudo chown -R www-data:www-data /usr/share/zabbix/modules/zabbix_automation
```

> **Find your actual path:**
> ```bash
> grep -r "modulesDir\|modules_dir" /etc/zabbix/web/ 2>/dev/null
> # or look at your zabbix.conf.php
> ```

### 2 — Scan & enable in the Zabbix UI

1. Log in to Zabbix as a Super Admin
2. Go to **Administration → General → Modules**
3. Click **"Scan directory"**
4. Find **"Zabbix Automation"** in the list (status: Disabled)
5. Click **"Disabled"** to toggle it to **"Enabled"**

### 3 — Verify

- Refresh the page
- You should see an **"Automation"** item in the left sidebar
- Click it to expand the sub-menu

---

## Directory layout (what you're deploying)

```
zabbix_automation/
├── manifest.json                  ← Module metadata + route definitions
├── Module.php                     ← Registers "Automation" left-menu item + CSS
│
├── actions/
│   ├── Dashboard.php              ← Dashboard page controller
│   ├── BulkHosts.php              ← Quick Add Hosts form controller
│   └── BulkHostsSubmit.php        ← AJAX: creates / updates / deletes hosts
│
├── views/
│   ├── automation.dashboard.php
│   ├── automation.bulk.hosts.php
│   └── js/
│       ├── automation.dashboard.js    ← Inlined by dashboard view
│       └── automation.bulk.hosts.js   ← Inlined by bulk hosts view
│
└── assets/
    └── css/
        └── automation.css         ← Module-scoped styles
```

---

## Uninstall

1. **Administration → General → Modules** → click **"Enabled"** to disable
2. Delete the module directory:
   ```bash
   sudo rm -rf /usr/share/zabbix/modules/zabbix_automation
   ```
3. Click **"Scan directory"** again — the module disappears from the list.

---

## Troubleshooting

| Symptom                            | Fix                                                                                     |
|------------------------------------|-----------------------------------------------------------------------------------------|
| Module doesn't appear after scan   | Check file ownership (`chown www-data`) and that `manifest.json` is valid JSON          |
| 500 error on page                  | Check PHP error log: `tail -f /var/log/apache2/error.log` or `/var/log/php*.log`        |
| "Action not found"                 | Make sure the module is **Enabled**, not just scanned                                   |
| CSS/JS not loading                 | Hard-refresh browser (Ctrl+Shift+R); check browser dev-tools Network tab                |
| API permission errors              | Ensure the logged-in Zabbix user has the correct role permissions                       |
