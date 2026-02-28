/*
 * Zabbix Automation Module — Template Sync JS
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    function showResult(el, type, text) {
        el.className = 'automation-results' + (type ? ' ' + type : '');
        el.textContent = text;
    }

    // ── Export ──────────────────────────────────────────────
    const exportBtn    = document.getElementById('btn-export');
    const exportOutput = document.getElementById('export-output');

    if (exportBtn && exportOutput) {
        exportBtn.addEventListener('click', async () => {

            const form     = document.getElementById('export-form');
            const select   = document.getElementById('export-templates');
            const format   = form.querySelector('[name="export_format"]').value;
            const selected = [...select.selectedOptions].map(o => o.value);

            if (selected.length === 0) {
                showResult(exportOutput, 'error', 'Please select at least one template.');
                return;
            }

            exportBtn.disabled = true;
            showResult(exportOutput, '', '…');

            const fd = new FormData();
            selected.forEach(id => fd.append('templateids[]', id));
            fd.append('format', format);

            try {
                const resp = await fetch('zabbix.php?action=automation.templates.export', {
                    method: 'POST',
                    body:   fd,
                });

                const data = await resp.json();

                if (data.error) {
                    showResult(exportOutput, 'error', data.error.title);
                } else {
                    showResult(exportOutput, 'success', data.content);

                    // Offer file download
                    const blob = new Blob([data.content], { type: 'text/' + format });
                    const url  = URL.createObjectURL(blob);
                    const a    = document.createElement('a');
                    a.href     = url;
                    a.download = 'zabbix_templates_export.' + format;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
            } catch (err) {
                showResult(exportOutput, 'error', 'Request failed: ' + err.message);
            } finally {
                exportBtn.disabled = false;
            }
        });
    }

    // ── Import ──────────────────────────────────────────────
    const importBtn    = document.getElementById('btn-import');
    const importResult = document.getElementById('import-result');

    if (importBtn && importResult) {
        importBtn.addEventListener('click', async () => {

            const form    = document.getElementById('import-form');
            const content = form.querySelector('[name="import_content"]').value.trim();
            const format  = form.querySelector('[name="import_format"]').value;

            if (!content) {
                showResult(importResult, 'error', 'Paste template content first.');
                return;
            }

            importBtn.disabled = true;
            showResult(importResult, '', '…');

            const fd = new FormData();
            fd.append('content', content);
            fd.append('format',  format);

            try {
                const resp = await fetch('zabbix.php?action=automation.templates.import', {
                    method: 'POST',
                    body:   fd,
                });

                const data = await resp.json();

                if (data.error) {
                    showResult(importResult, 'error',
                        data.error.title + (data.error.messages?.length
                            ? '\n' + data.error.messages.join('\n') : ''));
                } else {
                    showResult(importResult, 'success', 'Import successful.');
                }
            } catch (err) {
                showResult(importResult, 'error', 'Request failed: ' + err.message);
            } finally {
                importBtn.disabled = false;
            }
        });
    }
});
