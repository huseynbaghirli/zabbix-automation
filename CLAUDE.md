# CLAUDE.md — Zabbix Automation

This file provides guidance for AI assistants (Claude Code and similar) working in this repository.

---

## Project Overview

**zabbix-automation** is a **Zabbix UI Module** (PHP + vanilla JS) that adds an **Automation**
section to the Zabbix left-side navigation menu. Current features:

- **Dashboard** — summary counters (hosts, host groups) and quick links
- **Bulk Host Manager** — create, update, or delete multiple Zabbix hosts at once via a
  table-based UI with per-row duplicate/increment controls

---

## Repository Structure

```
zabbix-automation/
├── CLAUDE.md                              # This file
│
└── zabbix-ui-module/
    ├── DEPLOY.md                          # Installation and deployment guide
    │
    └── zabbix_automation/                 # The Zabbix module — copy this to zabbix/modules/
        ├── manifest.json                  # Module metadata + route definitions
        ├── Module.php                     # Registers left-menu item + global CSS
        │
        ├── actions/
        │   ├── Dashboard.php              # Dashboard page controller
        │   ├── BulkHosts.php              # Bulk Host Manager form controller
        │   └── BulkHostsSubmit.php        # AJAX: creates / updates / deletes hosts
        │
        ├── views/
        │   ├── automation.dashboard.php   # Dashboard HTML template
        │   ├── automation.bulk.hosts.php  # Bulk Host Manager HTML template
        │   └── js/
        │       ├── automation.dashboard.js    # Inlined by dashboard view
        │       └── automation.bulk.hosts.js   # Inlined by bulk hosts view
        │
        └── assets/
            └── css/
                └── automation.css         # Module-scoped styles (loaded via Module.php)
```

> **Note:** JS files belong in `views/js/` and are inlined by their respective PHP view via
> `file_get_contents()`. Do **not** add runtime JS to `assets/` — that directory is only used
> for CSS registered through `Module::getStylesheets()`.

---

## Development Environment

### Prerequisites

- A running **Zabbix 6.0 LTS** or **7.0+** instance
- **PHP 8.0+** (the module runs inside the Zabbix web stack — no standalone runtime)
- Web-server write access to the Zabbix `modules/` directory
- A browser with developer tools for front-end debugging

### Setup

```bash
# Copy the module to your Zabbix UI
sudo cp -r zabbix-ui-module/zabbix_automation /usr/share/zabbix/modules/
sudo chown -R www-data:www-data /usr/share/zabbix/modules/zabbix_automation

# Enable the module in the Zabbix UI:
# Administration → General → Modules → Scan directory → Enable "Zabbix Automation"
```

For full installation steps, troubleshooting, and uninstall instructions, see
`zabbix-ui-module/DEPLOY.md`.

---

## Key Conventions

### PHP Style

- Follow **PSR-12** coding standards.
- Always add `declare(strict_types=1)` at the top of every PHP file.
- Use **type hints** for all method signatures.
- Keep controllers thin — all Zabbix API calls go through the built-in `API::*()` facade.
- Never hardcode Zabbix integer constants — use the named constants
  (`HOST_STATUS_MONITORED`, `INTERFACE_TYPE_AGENT`, etc.).

```php
// Good — idempotent host creation
$existing = API::Host()->get([
    'output' => ['hostid'],
    'filter' => ['host' => $host_name],
]);
if (!$existing) {
    API::Host()->create([...]);
}
```

### JavaScript Style

- Plain **vanilla ES2020+** — no build step, no bundler, no npm.
- Wrap all module-level code in an **IIFE** (`(function () { ... })()`) to avoid polluting the
  global scope.
- Use `'use strict'` at the top of every JS file.
- Prefer `const` / `let`; never use `var`.
- Use clear, descriptive names; avoid abbreviations that aren't self-evident.

### CSS

- One stylesheet: `assets/css/automation.css`. It is loaded on every Zabbix page while the
  module is enabled, so scope **all** selectors to `.automation-*` or module-specific class
  names to avoid conflicts with Zabbix core styles.
- Use CSS variables (`var(--color-bg, #fff)`) for colours wherever Zabbix provides them.

### Zabbix API

- All API calls go through `API::Host()`, `API::HostGroup()`, `API::Template()`, etc.
- Handle errors explicitly — wrap API calls in `try/catch` and return meaningful error messages.
- Prefer **idempotent** operations: check if a resource exists before creating it.

### Secrets and Security

- **Never hardcode credentials** in any file.
- Always use `htmlspecialchars()` when echoing user-controlled data in PHP views.
- Validate POST data with Zabbix's `validateInput()` before using it.
- Use HTTPS for all Zabbix API connections; only disable SSL verification in local dev.

---

## Testing

Automated tests are not yet set up. When adding them:

- Use **PHPUnit** for PHP unit tests.
- Mock `API::*()` calls — never hit a live Zabbix instance in unit tests.
- Integration tests should run against a **staging** Zabbix instance, never production.
- Test file names: `test_<class_name>.php` under `tests/`.

---

## Linting and Formatting

```bash
# PHP code style (PSR-12)
phpcs --standard=PSR12 zabbix-ui-module/

# PHP static analysis
phpstan analyse zabbix-ui-module/

# JavaScript (optional, if eslint is available)
eslint zabbix-ui-module/zabbix_automation/views/js/
```

---

## Git Workflow

### Branch Naming

| Branch type | Pattern                        | Example                           |
|-------------|--------------------------------|-----------------------------------|
| Feature     | `feature/<short-description>`  | `feature/template-sync`           |
| Bug fix     | `fix/<short-description>`      | `fix/csrf-token-refresh`          |
| Chore/Infra | `chore/<short-description>`    | `chore/update-deploy-docs`        |
| AI-assisted | `claude/<session-description>` | `claude/add-bulk-host-manager`    |

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <short summary>

[optional body]
```

Types: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`

Examples:
```
feat(bulk-hosts): add ⧉+1 button to increment hostname and IP together
fix(bulk-hosts): prevent IP overflow beyond 255
docs(deploy): add troubleshooting table
chore: remove unused assets/js dead code
```

### Pull Requests

- Keep PRs focused and small (one logical change per PR).
- Include a description of what changed and why.
- All CI checks must pass before merging.
- Squash-merge into `main` to keep history clean.

---

## CI/CD

> To be defined as the project matures. Recommended pipeline steps:

1. **PHP lint** — `phpcs --standard=PSR12`
2. **PHP static analysis** — `phpstan analyse`
3. **Unit tests** — `phpunit tests/unit/`
4. **Integration tests** — against a staging Zabbix instance (manual gate)
5. **Deploy** — triggered by merge to `main` or a tagged release

---

## AI Assistant Guidelines

When working in this repository, AI assistants should:

1. **Read before editing** — always read the relevant files before making changes.
2. **Use Zabbix API facades** — all API calls go through `API::Host()->get(...)` etc.
3. **Keep operations idempotent** — actions should be safe to run multiple times.
4. **Never commit secrets** — refuse to include credentials, tokens, or passwords in any file.
5. **Follow existing style** — match the formatting, naming, and structure of adjacent code.
6. **JS belongs in `views/js/`** — do not add runtime JS to `assets/`; it is not loaded from there.
7. **Sanitise output** — always use `htmlspecialchars()` when echoing user data in PHP.
8. **Prefer small, focused changes** — avoid large refactors unless explicitly requested.
9. **Ask before destructive actions** — deleting hosts or Zabbix objects is irreversible.
10. **Update this file** — if the project structure changes significantly, update `CLAUDE.md`.

---

## Useful References

- [Zabbix Module Development](https://www.zabbix.com/documentation/current/en/manual/modules)
- [Zabbix API Documentation](https://www.zabbix.com/documentation/current/en/manual/api)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
