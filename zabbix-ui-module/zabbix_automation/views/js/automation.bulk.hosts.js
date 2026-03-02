/*
 * Zabbix Automation Module — Bulk Host Manager
 *
 * Table-based UI:
 *   - Each row = one host definition
 *   - "+" (Duplicate) button per row clones it and inserts below
 *   - "Add Row" button appends a blank row
 *   - Host Groups, Templates, Proxy selected via floating panel
 *   - "Add Hosts" submits all rows to the API
 */

'use strict';

(function () {

    /* ── Data from PHP ──────────────────────────────────── */
    const GROUPS    = JSON.parse(document.getElementById('js-groups').textContent    || '[]');
    const TEMPLATES = JSON.parse(document.getElementById('js-templates').textContent || '[]');
    const PROXIES   = JSON.parse(document.getElementById('js-proxies').textContent   || '[]');

    /* ── State ──────────────────────────────────────────── */
    let rowCounter   = 0;
    let selectorCtx  = null; // { rowEl, type }

    /* ── Init ───────────────────────────────────────────── */
    document.getElementById('btn-add-row').addEventListener('click', () => addRow());
    document.getElementById('btn-add-hosts').addEventListener('click', submitHosts);
    document.getElementById('btn-selector-apply').addEventListener('click', applySelector);
    document.getElementById('btn-selector-cancel').addEventListener('click', closeSelector);
    document.getElementById('selector-search').addEventListener('input', filterSelectorList);

    // Close panel when clicking outside
    document.addEventListener('click', function (e) {
        const panel = document.getElementById('selector-panel');
        if (panel.style.display !== 'none' &&
            !panel.contains(e.target) &&
            !e.target.classList.contains('btn-select')) {
            closeSelector();
        }
    });

    // Add one empty row on load
    addRow();

    /* ═══════════════════════════════════════════════════════
       ROW MANAGEMENT
       ═══════════════════════════════════════════════════════ */

    function addRow(defaults) {
        const tbody = document.getElementById('hosts-tbody');
        const tr    = buildRow(defaults || {});
        tbody.appendChild(tr);
        return tr;
    }

    function duplicateRow(sourceRow) {
        const data  = extractRowData(sourceRow);
        const newTr = buildRow(data);
        sourceRow.after(newTr);
    }

    function duplicateRowIncrIp(sourceRow) {
        const data = extractRowData(sourceRow);
        data.ip    = incrementIp(data.ip);
        const newTr = buildRow(data);
        sourceRow.after(newTr);
    }

    function deleteRow(rowEl) {
        const tbody = document.getElementById('hosts-tbody');
        if (tbody.querySelectorAll('.host-row').length > 1) {
            rowEl.remove();
        }
    }

    function buildRow(d) {
        rowCounter++;
        const tr = document.createElement('tr');
        tr.className  = 'host-row';
        tr.dataset.rowId        = rowCounter;
        tr.dataset.groupIds     = JSON.stringify(d.group_ids     || []);
        tr.dataset.groupNames   = JSON.stringify(d.group_names   || []);
        tr.dataset.templateIds  = JSON.stringify(d.template_ids  || []);
        tr.dataset.templateNames= JSON.stringify(d.template_names|| []);
        tr.dataset.proxyId      = d.proxy_id   || '';
        tr.dataset.proxyName    = d.proxy_name || '';

        /* ── Hostname ── */
        const tdHost = makeTd('col-hostname');
        tdHost.appendChild(makeInput('f-hostname', d.host || '', 'hostname'));

        /* ── Host Groups ── */
        const tdGroups = makeTd('col-groups');
        tdGroups.appendChild(makeChipContainer('groups', d.group_ids || [], d.group_names || []));
        tdGroups.appendChild(makeSelectBtn('groups'));

        /* ── Templates ── */
        const tdTpls = makeTd('col-templates');
        tdTpls.appendChild(makeChipContainer('templates', d.template_ids || [], d.template_names || []));
        tdTpls.appendChild(makeSelectBtn('templates'));

        /* ── IP ── */
        const tdIp  = makeTd('col-ip');
        const ipInp = makeInput('f-ip', d.ip || '', '192.168.1.1');
        const validateIpField = () => {
            ipInp.classList.toggle('ip-error', !isValidIp(ipInp.value.trim()));
        };
        ipInp.addEventListener('blur',  validateIpField);
        ipInp.addEventListener('input', validateIpField);
        tdIp.appendChild(ipInp);

        /* ── Port ── */
        const tdPort = makeTd('col-port');
        const portInput = makeInput('f-port', d.port || '10050', '10050');
        portInput.style.width = '54px';
        tdPort.appendChild(portInput);

        /* ── Proxy ── */
        const tdProxy = makeTd('col-proxy');
        const proxyDisplay = document.createElement('div');
        proxyDisplay.className = 'proxy-display';
        if (d.proxy_name) {
            const span = document.createElement('span');
            span.className   = 'proxy-name';
            span.textContent = d.proxy_name;
            proxyDisplay.appendChild(span);
        }
        tdProxy.appendChild(proxyDisplay);
        tdProxy.appendChild(makeSelectBtn('proxy'));

        /* ── Tags ── */
        const tdTags = makeTd('col-tags');
        const tagsContainer = document.createElement('div');
        tagsContainer.className = 'tags-container';
        const initialTags = (d.tags && d.tags.length) ? d.tags : [{ tag: '', value: '' }];
        initialTags.forEach(t => tagsContainer.appendChild(makeTagPair(t.tag || '', t.value || '')));
        tdTags.appendChild(tagsContainer);

        const addTagBtn = document.createElement('button');
        addTagBtn.type      = 'button';
        addTagBtn.className = 'btn-add-tag';
        addTagBtn.textContent = '+ Tag';
        addTagBtn.addEventListener('click', () => {
            tagsContainer.appendChild(makeTagPair('', ''));
        });
        tdTags.appendChild(addTagBtn);

        /* ── Actions ── */
        const tdAct = makeTd('col-actions');
        const dupBtn = document.createElement('button');
        dupBtn.type      = 'button';
        dupBtn.className = 'btn-dup';
        dupBtn.title     = 'Duplicate row';
        dupBtn.textContent = '⧉';
        dupBtn.addEventListener('click', () => duplicateRow(tr));

        const dupIpBtn = document.createElement('button');
        dupIpBtn.type      = 'button';
        dupIpBtn.className = 'btn-dup-ip';
        dupIpBtn.title     = 'Duplicate and increment last IP octet';
        dupIpBtn.textContent = '⧉+1';
        dupIpBtn.addEventListener('click', () => duplicateRowIncrIp(tr));

        const delBtn = document.createElement('button');
        delBtn.type      = 'button';
        delBtn.className = 'btn-del';
        delBtn.title     = 'Delete row';
        delBtn.textContent = '✕';
        delBtn.addEventListener('click', () => deleteRow(tr));

        const actWrap = document.createElement('div');
        actWrap.className = 'row-actions';
        actWrap.appendChild(dupBtn);
        actWrap.appendChild(dupIpBtn);
        actWrap.appendChild(delBtn);
        tdAct.appendChild(actWrap);

        /* ── Assemble ── */
        tr.appendChild(tdHost);
        tr.appendChild(tdGroups);
        tr.appendChild(tdTpls);
        tr.appendChild(tdIp);
        tr.appendChild(tdPort);
        tr.appendChild(tdProxy);
        tr.appendChild(tdTags);
        tr.appendChild(tdAct);

        /* ── Wire up Select buttons ── */
        tr.querySelectorAll('.btn-select').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                openSelector(btn, tr, btn.dataset.type);
            });
        });

        /* ── Wire up chip remove (event delegation) ── */
        tr.addEventListener('click', function (e) {
            const rm = e.target.closest('.chip-remove');
            if (!rm) return;
            const type = rm.dataset.type;
            const id   = rm.dataset.id;
            removeChip(tr, type, id);
        });

        /* ── Wire up tag remove (event delegation) ── */
        tr.addEventListener('click', function (e) {
            if (!e.target.classList.contains('tag-remove')) return;
            const pairs = tr.querySelectorAll('.tag-pair');
            if (pairs.length > 1) {
                e.target.closest('.tag-pair').remove();
            }
        });

        return tr;
    }

    /* ═══════════════════════════════════════════════════════
       DOM HELPERS
       ═══════════════════════════════════════════════════════ */

    function makeTd(cls) {
        const td = document.createElement('td');
        if (cls) td.className = cls;
        return td;
    }

    function makeInput(cls, value, placeholder) {
        const inp = document.createElement('input');
        inp.type        = 'text';
        inp.className   = 'automation-input-sm ' + cls;
        inp.value       = value;
        inp.placeholder = placeholder || '';
        return inp;
    }

    function makeSelectBtn(type) {
        const btn = document.createElement('button');
        btn.type          = 'button';
        btn.className     = 'btn-select';
        btn.dataset.type  = type;
        btn.textContent   = 'Select…';
        return btn;
    }

    function makeChipContainer(type, ids, names) {
        const div = document.createElement('div');
        div.className        = 'chip-container';
        div.dataset.target   = type;
        ids.forEach((id, i) => div.appendChild(makeChip(type, id, names[i] || id)));
        return div;
    }

    function makeChip(type, id, label) {
        const chip = document.createElement('span');
        chip.className = 'chip';

        const text = document.createElement('span');
        text.className   = 'chip-text';
        text.title       = label;
        text.textContent = label;

        const rm = document.createElement('span');
        rm.className    = 'chip-remove';
        rm.dataset.type = type;
        rm.dataset.id   = String(id);
        rm.textContent  = ' ×';

        chip.appendChild(text);
        chip.appendChild(rm);
        return chip;
    }

    function makeTagPair(key, val) {
        const div = document.createElement('div');
        div.className = 'tag-pair';

        const kIn = document.createElement('input');
        kIn.type        = 'text';
        kIn.className   = 'automation-input-sm tag-key';
        kIn.value       = key;
        kIn.placeholder = 'key';

        const sep = document.createElement('span');
        sep.className   = 'tag-colon';
        sep.textContent = ':';

        const vIn = document.createElement('input');
        vIn.type        = 'text';
        vIn.className   = 'automation-input-sm tag-val';
        vIn.value       = val;
        vIn.placeholder = 'value';

        const rm = document.createElement('span');
        rm.className   = 'tag-remove';
        rm.textContent = '✕';

        div.appendChild(kIn);
        div.appendChild(sep);
        div.appendChild(vIn);
        div.appendChild(rm);
        return div;
    }

    /* ═══════════════════════════════════════════════════════
       CHIP MANAGEMENT
       ═══════════════════════════════════════════════════════ */

    function removeChip(tr, type, idToRemove) {
        const idsKey   = type === 'groups' ? 'groupIds'      : 'templateIds';
        const namesKey = type === 'groups' ? 'groupNames'    : 'templateNames';
        const ids   = JSON.parse(tr.dataset[idsKey]   || '[]');
        const names = JSON.parse(tr.dataset[namesKey] || '[]');
        const idx   = ids.indexOf(idToRemove);
        if (idx !== -1) { ids.splice(idx, 1); names.splice(idx, 1); }
        tr.dataset[idsKey]   = JSON.stringify(ids);
        tr.dataset[namesKey] = JSON.stringify(names);
        // Rebuild chip container
        const container = tr.querySelector('.chip-container[data-target="' + type + '"]');
        container.innerHTML = '';
        ids.forEach((id, i) => container.appendChild(makeChip(type, id, names[i] || id)));
    }

    /* ═══════════════════════════════════════════════════════
       SELECTOR PANEL
       ═══════════════════════════════════════════════════════ */

    function openSelector(anchorEl, rowEl, type) {
        const panel = document.getElementById('selector-panel');
        const list  = document.getElementById('selector-list');

        const items   = type === 'groups' ? GROUPS : type === 'templates' ? TEMPLATES : PROXIES;
        const isRadio = type === 'proxy';
        const idKey   = type === 'groups' ? 'groupid' : type === 'templates' ? 'templateid' : 'proxyid';

        const selected = isRadio
            ? [tr_data(rowEl, 'proxyId')]
            : JSON.parse(rowEl.dataset[type === 'groups' ? 'groupIds' : 'templateIds'] || '[]');

        // Build list
        document.getElementById('selector-search').value = '';
        list.innerHTML = '';

        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.style.cssText = 'padding:12px;color:#888;font-size:0.8rem;text-align:center';
            empty.textContent   = 'No items available';
            list.appendChild(empty);
        } else {
            items.forEach(item => {
                const itemId    = String(item[idKey]);
                const isChecked = selected.map(String).includes(itemId);
                const label     = document.createElement('label');
                label.className = 'selector-panel-item';

                const inp = document.createElement('input');
                inp.type           = isRadio ? 'radio' : 'checkbox';
                inp.name           = 'sel-item';
                inp.value          = itemId;
                inp.dataset.label  = item.name;
                inp.checked        = isChecked;

                const txt = document.createTextNode(item.name);
                label.appendChild(inp);
                label.appendChild(txt);
                list.appendChild(label);
            });
        }

        selectorCtx = { rowEl, type, idKey, isRadio };

        // Position panel
        const rect  = anchorEl.getBoundingClientRect();
        const vpW   = window.innerWidth;
        let left    = rect.left + window.scrollX;
        if (left + 300 > vpW) left = vpW - 310;

        panel.style.top     = (rect.bottom + window.scrollY + 4) + 'px';
        panel.style.left    = left + 'px';
        panel.style.display = 'flex';
    }

    function tr_data(rowEl, key) {
        return rowEl.dataset[key] || '';
    }

    function closeSelector() {
        document.getElementById('selector-panel').style.display = 'none';
        selectorCtx = null;
    }

    function filterSelectorList() {
        const q = document.getElementById('selector-search').value.toLowerCase();
        document.querySelectorAll('.selector-panel-item').forEach(item => {
            item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    function applySelector() {
        if (!selectorCtx) return;
        const { rowEl, type, isRadio } = selectorCtx;
        const checked = [...document.querySelectorAll('#selector-list input:checked')];

        if (isRadio) {
            const sel = checked[0];
            rowEl.dataset.proxyId   = sel ? sel.value           : '';
            rowEl.dataset.proxyName = sel ? sel.dataset.label   : '';
            const display = rowEl.querySelector('.proxy-display');
            display.innerHTML = '';
            if (sel) {
                const span = document.createElement('span');
                span.className   = 'proxy-name';
                span.textContent = sel.dataset.label;
                display.appendChild(span);
            }
        } else {
            const ids   = checked.map(el => el.value);
            const names = checked.map(el => el.dataset.label);
            const idsKey   = type === 'groups' ? 'groupIds'      : 'templateIds';
            const namesKey = type === 'groups' ? 'groupNames'    : 'templateNames';
            rowEl.dataset[idsKey]   = JSON.stringify(ids);
            rowEl.dataset[namesKey] = JSON.stringify(names);
            const container = rowEl.querySelector('.chip-container[data-target="' + type + '"]');
            container.innerHTML = '';
            ids.forEach((id, i) => container.appendChild(makeChip(type, id, names[i] || id)));
        }

        closeSelector();
    }

    /* ═══════════════════════════════════════════════════════
       DATA EXTRACTION
       ═══════════════════════════════════════════════════════ */

    function extractRowData(tr) {
        const tags = [];
        tr.querySelectorAll('.tag-pair').forEach(pair => {
            const k = pair.querySelector('.tag-key').value.trim();
            const v = pair.querySelector('.tag-val').value.trim();
            if (k || v) tags.push({ tag: k, value: v });
        });

        return {
            host:          tr.querySelector('.f-hostname').value.trim(),
            ip:            tr.querySelector('.f-ip').value.trim(),
            port:          tr.querySelector('.f-port').value.trim() || '10050',
            group_ids:     JSON.parse(tr.dataset.groupIds      || '[]'),
            group_names:   JSON.parse(tr.dataset.groupNames    || '[]'),
            template_ids:  JSON.parse(tr.dataset.templateIds   || '[]'),
            template_names:JSON.parse(tr.dataset.templateNames || '[]'),
            proxy_id:      tr.dataset.proxyId   || '',
            proxy_name:    tr.dataset.proxyName || '',
            tags,
        };
    }

    /* ═══════════════════════════════════════════════════════
       SUBMISSION
       ═══════════════════════════════════════════════════════ */

    async function submitHosts() {
        const rows   = [...document.querySelectorAll('.host-row')];
        const result = document.getElementById('bulk-results');
        const btn    = document.getElementById('btn-add-hosts');

        const hosts = rows.map(extractRowData).filter(h => h.host !== '');

        if (hosts.length === 0) {
            showResult(result, 'error', 'Please enter at least one hostname.');
            return;
        }

        // Validate IP addresses before submitting
        const invalidIps = hosts.filter(h => h.ip !== '' && !isValidIp(h.ip));
        if (invalidIps.length > 0) {
            // Highlight the bad fields
            rows.forEach(tr => {
                const inp = tr.querySelector('.f-ip');
                inp.classList.toggle('ip-error', !isValidIp(inp.value.trim()));
            });
            showResult(result, 'error',
                'Invalid IP address' + (invalidIps.length > 1 ? 'es' : '') + ': ' +
                invalidIps.map(h => '"' + h.ip + '" (' + h.host + ')').join(', ')
            );
            return;
        }

        btn.disabled = true;
        showResult(result, '', 'Submitting…');

        const payload = hosts.map(h => ({
            host:         h.host,
            ip:           h.ip,
            port:         h.port,
            group_ids:    h.group_ids,
            template_ids: h.template_ids,
            proxy_id:     h.proxy_id,
            tags:         h.tags.filter(t => t.tag),
        }));

        const fd = new FormData();
        fd.append('host_action', 'create');
        fd.append('hosts_json', JSON.stringify(payload));

        const csrfToken = document.getElementById('zbx-csrf-token')?.value || '';
        if (csrfToken) fd.append('_csrf_token', csrfToken);

        try {
            const resp = await fetch('zabbix.php?action=automation.bulk.hosts.submit', {
                method: 'POST',
                body:   fd,
            });

            const rawText = await resp.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (parseErr) {
                // Server returned HTML — show diagnostic so we know the exact cause
                const textPreview = rawText
                    .replace(/<script[\s\S]*?<\/script>/gi, '')
                    .replace(/<style[\s\S]*?<\/style>/gi, '')
                    .replace(/<[^>]+>/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .slice(0, 500);
                showResult(result, 'error',
                    'HTTP ' + resp.status +
                    (resp.redirected ? ' → ' + resp.url : '') +
                    '\n\nServer response:\n' + (textPreview || '(empty)'));
                return;
            }

            if (data.error) {
                showResult(result, 'error',
                    data.error.title +
                    (data.error.messages && data.error.messages.length
                        ? '\n' + data.error.messages.join('\n') : ''));
            } else {
                const msg =
                    'Created: ' + data.created +
                    '  |  Updated: ' + data.updated +
                    '  |  Deleted: ' + data.deleted +
                    (data.errors && data.errors.length
                        ? '\n\nWarnings:\n' + data.errors.join('\n') : '');
                showResult(result, 'success', msg);
            }
        } catch (err) {
            showResult(result, 'error', 'Request failed: ' + err.message);
        } finally {
            btn.disabled = false;
        }
    }

    /* ═══════════════════════════════════════════════════════
       UTILS
       ═══════════════════════════════════════════════════════ */

    function isValidIp(val) {
        if (val === '') return true; // empty → backend defaults to 127.0.0.1
        const parts = val.split('.');
        if (parts.length !== 4) return false;
        return parts.every(p => /^\d{1,3}$/.test(p) && Number(p) <= 255);
    }

    function incrementIp(ip) {
        if (!ip) return ip;
        const parts = ip.split('.');
        if (parts.length !== 4) return ip;
        const last = parseInt(parts[3], 10);
        if (isNaN(last) || last >= 255) return ip; // 255 → do not overflow
        parts[3] = String(last + 1);
        return parts.join('.');
    }

    function showResult(el, type, text) {
        el.className  = 'automation-results' + (type ? ' ' + type : '');
        el.textContent = text;
    }

})();
