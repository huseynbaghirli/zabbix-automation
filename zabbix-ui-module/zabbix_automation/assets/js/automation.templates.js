/*
 * Zabbix Automation Module — Template Sync JS
 *
 * Handles export (fetch → display) and import (POST content → result).
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── Export ──────────────────────────────────────────────────────────────
    const exportBtn    = document.getElementById('btn-export');
    const exportOutput = document.getElementById('export-output');

    if (exportBtn && exportOutput) {
        exportBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const form   = document.getElementById('export-form');
            const format = form.querySelector('[name="export_format"]').value;

            // Collect selected template IDs from the multiselect
            const ids = [...form.querySelectorAll('[name="export_templateids[]"]')]
                .map(el => el.value)
                .filter(Boolean);

            if (ids.length === 0) {
                exportOutput.className = 'automation-results error';
                exportOutput.textContent = 'Please select at least one template.';
                return;
            }

            exportBtn.disabled = true;
            exportOutput.className = 'automation-results';
            exportOutput.textContent = '…';

            try {
                const resp = await fetch('zabbix.php?action=automation.templates.export', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ templateids: ids, format }),
                });

                const data = await resp.json();

                if (data.error) {
                    exportOutput.classList.add('error');
                    exportOutput.textContent = data.error.title;
                } else {
                    exportOutput.classList.add('success');
                    exportOutput.textContent = data.content;

                    // Offer download
                    const blob = new Blob([data.content], { type: 'text/' + format });
                    const url  = URL.createObjectURL(blob);
                    const a    = document.createElement('a');
                    a.href     = url;
                    a.download = `zabbix_templates_export.${format}`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
            } catch (err) {
                exportOutput.classList.add('error');
                exportOutput.textContent = 'Request failed: ' + err.message;
            } finally {
                exportBtn.disabled = false;
            }
        });
    }

    // ── Import ──────────────────────────────────────────────────────────────
    const importBtn    = document.getElementById('btn-import');
    const importResult = document.getElementById('import-result');

    if (importBtn && importResult) {
        importBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const form    = document.getElementById('import-form');
            const content = form.querySelector('[name="import_content"]').value.trim();
            const format  = form.querySelector('[name="import_format"]').value;

            if (!content) {
                importResult.className = 'automation-results error';
                importResult.textContent = 'Paste template content first.';
                return;
            }

            importBtn.disabled = true;
            importResult.className = 'automation-results';
            importResult.textContent = '…';

            try {
                const resp = await fetch('zabbix.php?action=automation.templates.import', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ content, format }),
                });

                const data = await resp.json();

                if (data.error) {
                    importResult.classList.add('error');
                    importResult.textContent = data.error.title + '\n' +
                        (data.error.messages || []).join('\n');
                } else {
                    importResult.classList.add('success');
                    importResult.textContent = 'Import successful.';
                }
            } catch (err) {
                importResult.classList.add('error');
                importResult.textContent = 'Request failed: ' + err.message;
            } finally {
                importBtn.disabled = false;
            }
        });
    }
});
