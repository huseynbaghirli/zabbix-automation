/*
 * Zabbix Automation Module — Maintenance Manager JS
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    const btn    = document.getElementById('btn-create-maintenance');
    const result = document.getElementById('maintenance-result');
    const form   = document.getElementById('maintenance-form');

    if (!btn || !result || !form) return;

    function showResult(el, type, text) {
        el.className = 'automation-results' + (type ? ' ' + type : '');
        el.textContent = text;
    }

    btn.addEventListener('click', async () => {

        const name  = form.querySelector('[name="maintenance_name"]').value.trim();
        const desc  = form.querySelector('[name="maintenance_description"]').value.trim();
        const type  = form.querySelector('[name="maintenance_type"]').value;
        const since = form.querySelector('[name="active_since"]').value;
        const till  = form.querySelector('[name="active_till"]').value;

        const hostIds  = [...form.querySelectorAll('[name="host_ids[]"] option:checked')]
            .map(o => o.value);
        const groupIds = [...form.querySelectorAll('[name="group_ids[]"] option:checked')]
            .map(o => o.value);

        if (!name) {
            showResult(result, 'error', 'Maintenance name is required.');
            return;
        }
        if (!since || !till) {
            showResult(result, 'error', '"Active since" and "Active till" are required.');
            return;
        }

        const tsFrom = Math.floor(new Date(since).getTime() / 1000);
        const tsTo   = Math.floor(new Date(till).getTime()  / 1000);

        if (tsTo <= tsFrom) {
            showResult(result, 'error', '"Active till" must be after "Active since".');
            return;
        }
        if (hostIds.length === 0 && groupIds.length === 0) {
            showResult(result, 'error', 'Select at least one host or host group.');
            return;
        }

        btn.disabled = true;
        showResult(result, '', '...');

        const fd = new FormData();
        fd.append('name',         name);
        fd.append('description',  desc);
        fd.append('type',         type);
        fd.append('active_since', tsFrom);
        fd.append('active_till',  tsTo);
        hostIds.forEach(id  => fd.append('host_ids[]',  id));
        groupIds.forEach(id => fd.append('group_ids[]', id));

        try {
            const resp = await fetch('zabbix.php?action=automation.maintenance.submit', {
                method: 'POST',
                body:   fd,
            });

            const data = await resp.json();

            if (data.error) {
                showResult(result, 'error',
                    data.error.title + (data.error.messages && data.error.messages.length
                        ? '\n' + data.error.messages.join('\n') : ''));
            } else {
                showResult(result, 'success',
                    'Maintenance window created (ID: ' + data.maintenanceid + '). Reload to see it in the list.');
            }
        } catch (err) {
            showResult(result, 'error', 'Request failed: ' + err.message);
        } finally {
            btn.disabled = false;
        }
    });
});
