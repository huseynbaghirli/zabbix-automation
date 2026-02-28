/*
 * Zabbix Automation Module — Bulk Host Manager JS
 *
 * Handles the AJAX form submission for bulk host create/update/delete.
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form     = document.getElementById('automation-bulk-form');
    const btn      = document.getElementById('btn-bulk-execute');
    const results  = document.getElementById('bulk-results');

    if (!form || !btn || !results) return;

    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        const action    = form.querySelector('[name="bulk_action"]').value;
        const raw       = form.querySelector('[name="hosts_json"]').value.trim();

        results.className = 'automation-results';
        results.textContent = '…';

        // Parse input — accept a JSON array or newline-separated JSON objects
        let hosts;
        try {
            const parsed = JSON.parse(raw);
            hosts = Array.isArray(parsed) ? parsed : [parsed];
        } catch (err) {
            // Try NDJSON (one object per line)
            try {
                hosts = raw.split('\n')
                    .map(l => l.trim())
                    .filter(Boolean)
                    .map(l => JSON.parse(l));
            } catch (err2) {
                results.classList.add('error');
                results.textContent = 'Invalid JSON: ' + err.message;
                return;
            }
        }

        btn.disabled = true;

        try {
            const resp = await fetch('zabbix.php?action=automation.bulk.hosts.submit', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ action, hosts }),
            });

            const data = await resp.json();

            if (data.error) {
                results.classList.add('error');
                results.textContent = data.error.title + '\n' +
                    (data.error.messages || []).join('\n');
            } else {
                results.classList.add('success');
                results.textContent =
                    `Created: ${data.created}  |  Updated: ${data.updated}  |  Deleted: ${data.deleted}` +
                    (data.errors.length ? '\n\nWarnings:\n' + data.errors.join('\n') : '');
            }
        } catch (err) {
            results.classList.add('error');
            results.textContent = 'Request failed: ' + err.message;
        } finally {
            btn.disabled = false;
        }
    });

    // Click-to-copy for reference IDs
    document.querySelectorAll('.automation-ref-item code').forEach(el => {
        el.style.cursor = 'pointer';
        el.title = 'Click to copy ID';
        el.addEventListener('click', () => {
            navigator.clipboard.writeText(el.textContent).catch(() => {});
        });
    });
});
