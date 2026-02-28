/*
 * Zabbix Automation Module — Bulk Host Manager JS
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    const form    = document.getElementById('automation-bulk-form');
    const btn     = document.getElementById('btn-bulk-execute');
    const results = document.getElementById('bulk-results');

    if (!form || !btn || !results) return;

    btn.addEventListener('click', async () => {

        const action  = form.querySelector('[name="bulk_action"]').value;
        const rawJson = form.querySelector('[name="hosts_json"]').value.trim();

        if (!rawJson) {
            showResult(results, 'error', 'Please enter host definitions (JSON).');
            return;
        }

        // Validate JSON client-side
        let hosts;
        try {
            const parsed = JSON.parse(rawJson);
            hosts = Array.isArray(parsed) ? parsed : [parsed];
        } catch (err) {
            try {
                hosts = rawJson.split('\n')
                    .map(l => l.trim()).filter(Boolean)
                    .map(l => JSON.parse(l));
            } catch (err2) {
                showResult(results, 'error', 'Invalid JSON: ' + err.message);
                return;
            }
        }

        btn.disabled = true;
        showResult(results, '', '…');

        const fd = new FormData();
        fd.append('action',     action);
        fd.append('hosts_json', JSON.stringify(hosts));

        try {
            const resp = await fetch('zabbix.php?action=automation.bulk.hosts.submit', {
                method: 'POST',
                body:   fd,
            });

            const data = await resp.json();

            if (data.error) {
                showResult(results, 'error',
                    data.error.title + (data.error.messages?.length
                        ? '\n' + data.error.messages.join('\n') : ''));
            } else {
                const msg = `Created: ${data.created}  |  Updated: ${data.updated}  |  Deleted: ${data.deleted}`
                    + (data.errors?.length ? '\n\nWarnings:\n' + data.errors.join('\n') : '');
                showResult(results, 'success', msg);
            }
        } catch (err) {
            showResult(results, 'error', 'Request failed: ' + err.message);
        } finally {
            btn.disabled = false;
        }
    });

    // Click-to-copy for reference IDs
    document.querySelectorAll('.automation-ref-item code').forEach(el => {
        el.addEventListener('click', () => {
            navigator.clipboard.writeText(el.textContent).catch(() => {});
        });
    });

    function showResult(el, type, text) {
        el.className = 'automation-results' + (type ? ' ' + type : '');
        el.textContent = text;
    }
});
