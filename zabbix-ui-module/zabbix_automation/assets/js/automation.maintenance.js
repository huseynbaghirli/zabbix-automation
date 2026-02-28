/*
 * Zabbix Automation Module — Maintenance Manager JS
 *
 * Handles AJAX form submission for creating maintenance windows.
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const btn    = document.getElementById('btn-create-maintenance');
    const result = document.getElementById('maintenance-result');
    const form   = document.getElementById('maintenance-form');

    if (!btn || !result || !form) return;

    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        const name    = form.querySelector('[name="maintenance_name"]').value.trim();
        const desc    = form.querySelector('[name="maintenance_description"]').value.trim();
        const type    = parseInt(form.querySelector('[name="maintenance_type"]').value, 10);
        const since   = form.querySelector('[name="active_since"]').value;
        const till    = form.querySelector('[name="active_till"]').value;

        // Collect multi-select IDs
        const hostIds  = [...form.querySelectorAll('[name="host_ids[]"]')].map(el => el.value).filter(Boolean);
        const groupIds = [...form.querySelectorAll('[name="group_ids[]"]')].map(el => el.value).filter(Boolean);

        if (!name) {
            result.className = 'automation-results error';
            result.textContent = 'Maintenance name is required.';
            return;
        }
        if (!since || !till) {
            result.className = 'automation-results error';
            result.textContent = '"Active since" and "Active till" are required.';
            return;
        }

        // Convert datetime strings to Unix timestamps
        const tsFrom = Math.floor(new Date(since).getTime() / 1000);
        const tsTo   = Math.floor(new Date(till).getTime()  / 1000);

        if (tsTo <= tsFrom) {
            result.className = 'automation-results error';
            result.textContent = '"Active till" must be after "Active since".';
            return;
        }

        if (hostIds.length === 0 && groupIds.length === 0) {
            result.className = 'automation-results error';
            result.textContent = 'Select at least one host or host group.';
            return;
        }

        btn.disabled = true;
        result.className = 'automation-results';
        result.textContent = '…';

        try {
            const resp = await fetch('zabbix.php?action=automation.maintenance.submit', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    name,
                    description:  desc,
                    type,
                    active_since: tsFrom,
                    active_till:  tsTo,
                    host_ids:     hostIds,
                    group_ids:    groupIds,
                }),
            });

            const data = await resp.json();

            if (data.error) {
                result.classList.add('error');
                result.textContent = data.error.title + '\n' +
                    (data.error.messages || []).join('\n');
            } else {
                result.classList.add('success');
                result.textContent = `Maintenance window created (ID: ${data.maintenanceid}). Reload the page to see it in the list.`;
            }
        } catch (err) {
            result.classList.add('error');
            result.textContent = 'Request failed: ' + err.message;
        } finally {
            btn.disabled = false;
        }
    });
});
