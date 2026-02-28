# Deploy: Zabbix Automation UI Module

## What this does

Adds an **"Automation"** section to the Zabbix left-side navigation menu with four tools:

| Page | URL action | Description |
|------|-----------|-------------|
| Dashboard | `automation.dashboard` | Summary counters (hosts, templates, groups, maintenances) + quick links |
| Bulk Host Manager | `automation.bulk.hosts` | Create / update / delete many hosts at once via JSON input |
| Template Sync | `automation.templates` | Export templates as JSON or XML; import from JSON/XML content |
| Maintenance Manager | `automation.maintenance` | Schedule maintenance windows for hosts or host groups |

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
├── manifest.json               ← Module metadata + route definitions
├── Module.php                  ← Registers the "Automation" left-menu item
│
├── actions/
│   ├── Dashboard.php           ← Stats page controller
│   ├── BulkHosts.php           ← Bulk host form controller
│   ├── BulkHostsSubmit.php     ← AJAX: creates/updates/deletes hosts
│   ├── Templates.php           ← Template sync page controller
│   ├── TemplatesExport.php     ← AJAX: exports templates to JSON/XML
│   ├── TemplatesImport.php     ← AJAX: imports templates from JSON/XML
│   ├── Maintenance.php         ← Maintenance manager page controller
│   └── MaintenanceSubmit.php   ← AJAX: creates a maintenance window
│
├── views/
│   ├── automation.dashboard.php
│   ├── automation.bulk.hosts.php
│   ├── automation.templates.php
│   └── automation.maintenance.php
│
└── assets/
    ├── css/
    │   └── automation.css      ← Module-scoped styles
    └── js/
        ├── automation.dashboard.js
        ├── automation.bulk.hosts.js
        ├── automation.templates.js
        └── automation.maintenance.js
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

| Symptom | Fix |
|---------|-----|
| Module doesn't appear after scan | Check file ownership (`chown www-data`) and that `manifest.json` is valid JSON |
| 500 error on page | Check PHP error log: `tail -f /var/log/apache2/error.log` or `/var/log/php*.log` |
| "Action not found" | Make sure module is **Enabled**, not just scanned |
| CSS/JS not loading | Hard-refresh browser (Ctrl+Shift+R); check browser dev-tools Network tab |
| API permission errors | Ensure the logged-in Zabbix user has correct role permissions |
