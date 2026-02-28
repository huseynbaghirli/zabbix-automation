# CLAUDE.md — Zabbix Automation

This file provides guidance for AI assistants (Claude Code and similar) working in this repository.

---

## Project Overview

**zabbix-automation** is a collection of scripts, templates, and tooling for automating Zabbix monitoring infrastructure. Typical tasks include:

- Provisioning hosts, host groups, templates, and items via the Zabbix API
- Exporting/importing Zabbix configuration as code (templates, dashboards, maps)
- Bulk operations (mass host creation, trigger updates, maintenance windows)
- Integration with CI/CD pipelines and infrastructure-as-code workflows (Ansible, Terraform, etc.)

---

## Repository Structure (Planned)

```
zabbix-automation/
├── CLAUDE.md              # This file
├── README.md              # Human-facing project documentation
├── .env.example           # Required environment variables (never commit .env)
├── requirements.txt       # Python dependencies (if applicable)
├── pyproject.toml         # Python project config / linting config
│
├── scripts/               # Standalone automation scripts
│   ├── hosts/             # Host creation, update, and deletion
│   ├── templates/         # Template import/export/sync
│   ├── maintenance/       # Maintenance window management
│   └── reports/           # Reporting and alerting scripts
│
├── templates/             # Zabbix XML/JSON templates (exported configs)
│   ├── hosts/
│   └── templates/
│
├── ansible/               # Ansible roles/playbooks (if used)
├── terraform/             # Terraform modules (if used)
│
├── lib/                   # Shared Python modules / helper libraries
│   ├── zabbix_client.py   # Wrapper around the Zabbix API
│   └── utils.py           # Common utilities
│
└── tests/                 # Unit and integration tests
    ├── unit/
    └── integration/
```

> **Note:** As the project evolves, update this section to reflect the actual directory layout.

---

## Development Environment

### Prerequisites

- Python 3.10+ (primary scripting language)
- Access to a Zabbix server (6.0 LTS or 7.0+ recommended)
- `pip` or `pipenv` / `poetry` for dependency management

### Setup

```bash
# Clone the repo
git clone <repo-url>
cd zabbix-automation

# Create a virtual environment
python -m venv .venv
source .venv/bin/activate      # Linux/macOS
# .venv\Scripts\activate       # Windows

# Install dependencies
pip install -r requirements.txt

# Copy and fill in environment variables
cp .env.example .env
# Edit .env with your Zabbix URL, username, and password/API token
```

### Environment Variables

| Variable              | Description                                      | Required |
|-----------------------|--------------------------------------------------|----------|
| `ZABBIX_URL`          | Full URL to Zabbix frontend (e.g., `https://zabbix.example.com`) | Yes |
| `ZABBIX_USER`         | Zabbix API username                              | Yes* |
| `ZABBIX_PASSWORD`     | Zabbix API password                              | Yes* |
| `ZABBIX_API_TOKEN`    | Zabbix API token (preferred over user/password)  | Yes* |
| `ZABBIX_VERIFY_SSL`   | `true`/`false` — whether to verify TLS certs    | No (default: `true`) |

*Either `ZABBIX_API_TOKEN` or `ZABBIX_USER`+`ZABBIX_PASSWORD` must be set.

**Never commit `.env` files or credentials to the repository.**

---

## Key Conventions

### Python Style

- Follow **PEP 8** and enforce with `ruff` or `flake8`.
- Format code with **`black`** (line length: 100).
- Use **type hints** for all function signatures.
- Prefer explicit over implicit — do not rely on magic or globals.
- Keep scripts self-contained where practical; extract shared logic into `lib/`.

```python
# Good
def create_host(client: ZabbixClient, host_name: str, group_ids: list[int]) -> dict:
    ...

# Bad
def create_host(client, host_name, group_ids):
    ...
```

### Zabbix API

- Always use the shared `ZabbixClient` wrapper (`lib/zabbix_client.py`) — never instantiate raw HTTP calls inline.
- Handle API errors explicitly; do not silently swallow exceptions.
- Prefer **idempotent** operations: check if a resource exists before creating it.
- Use Zabbix API token authentication (`ZABBIX_API_TOKEN`) in all environments when available.

```python
# Idempotent host creation pattern
existing = client.host.get(filter={"host": host_name})
if not existing:
    client.host.create(...)
```

### Configuration as Code

- Store all Zabbix templates and exported configurations as **version-controlled JSON or XML** under `templates/`.
- Use Zabbix's native export/import format (XML for ≤6.4, JSON for 7.0+).
- Do not hardcode host IDs, group IDs, or template IDs — resolve them dynamically via the API or via config files.

### Secrets and Security

- **Never hardcode credentials** in scripts, templates, or tests.
- Use environment variables (via `.env` locally, secrets manager in CI/CD).
- Validate and sanitize any external input before passing it to API calls.
- Use HTTPS for all Zabbix API connections; only disable SSL verification in local/dev environments.

### Error Handling

- Log errors with sufficient context (host name, operation, API response).
- Exit with a non-zero status code on failure so CI/CD pipelines can detect errors.
- Use structured logging (`logging` module, not bare `print`).

```python
import logging
logger = logging.getLogger(__name__)

try:
    result = client.host.create(...)
except ZabbixAPIError as e:
    logger.error("Failed to create host %s: %s", host_name, e)
    raise SystemExit(1)
```

---

## Testing

### Running Tests

```bash
# Unit tests
pytest tests/unit/

# Integration tests (requires a live Zabbix instance)
ZABBIX_URL=https://... pytest tests/integration/

# All tests with coverage
pytest --cov=lib tests/
```

### Test Conventions

- Unit tests must **not** make real network calls — mock the `ZabbixClient`.
- Integration tests should run against a **test/staging Zabbix instance**, never production.
- Test file names: `test_<module_name>.py`.
- Each test function name should clearly describe the scenario: `test_create_host_returns_id_on_success`.

---

## Linting and Formatting

```bash
# Format code
black .

# Lint
ruff check .         # or: flake8 .

# Type checking
mypy lib/ scripts/
```

All three must pass before merging. Configure CI to enforce this.

---

## Git Workflow

### Branch Naming

| Branch type    | Pattern                        | Example                          |
|----------------|--------------------------------|----------------------------------|
| Feature        | `feature/<short-description>`  | `feature/bulk-host-import`       |
| Bug fix        | `fix/<short-description>`      | `fix/auth-token-refresh`         |
| Chore/Infra    | `chore/<short-description>`    | `chore/update-dependencies`      |
| AI-assisted    | `claude/<session-description>` | `claude/claude-md-mm6nucecl6uqa7lu-ABVY7` |

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <short summary>

[optional body]
```

Types: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`

Examples:
```
feat(hosts): add bulk host creation from CSV
fix(client): handle expired API token with auto-refresh
docs(readme): add setup instructions for Docker
```

### Pull Requests

- Keep PRs focused and small (one logical change per PR).
- Include a description of what changed and why.
- All CI checks (lint, tests) must pass before merging.
- Squash-merge into `main` to keep history clean.

---

## CI/CD

> To be defined as the project matures. Recommended pipeline steps:

1. **Lint** — `ruff check .` + `black --check .`
2. **Type check** — `mypy lib/ scripts/`
3. **Unit tests** — `pytest tests/unit/`
4. **Integration tests** — against a staging Zabbix instance (optional/manual gate)
5. **Deploy/run** — triggered by merge to `main` or a tagged release

---

## AI Assistant Guidelines

When working in this repository, AI assistants should:

1. **Read before editing** — always read the relevant files before making changes.
2. **Use the shared client** — all Zabbix API interactions must go through `lib/zabbix_client.py`.
3. **Keep operations idempotent** — scripts should be safe to run multiple times.
4. **Never commit secrets** — refuse to include credentials, tokens, or passwords in any file.
5. **Follow existing style** — match the formatting, naming, and structure of adjacent code.
6. **Write tests** — any new utility function in `lib/` should have a corresponding unit test.
7. **Update this file** — if the project structure changes significantly, update `CLAUDE.md` to reflect the new state.
8. **Prefer small, focused changes** — avoid large refactors unless explicitly requested.
9. **Ask before destructive actions** — deleting hosts, triggers, or Zabbix objects is irreversible; confirm intent.
10. **Log, don't print** — use the `logging` module; avoid bare `print()` in library code.

---

## Useful References

- [Zabbix API Documentation](https://www.zabbix.com/documentation/current/en/manual/api)
- [pyzabbix library](https://github.com/lukecyca/pyzabbix) — common Python Zabbix API client
- [zabbix-cli](https://github.com/unioslo/zabbix-cli) — CLI wrapper for the Zabbix API
- [Conventional Commits](https://www.conventionalcommits.org/)
- [PEP 8 Style Guide](https://peps.python.org/pep-0008/)
