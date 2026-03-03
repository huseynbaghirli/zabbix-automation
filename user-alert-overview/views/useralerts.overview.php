<?php

declare(strict_types=1);

$role_map = [
    1 => ['label' => 'User',        'cls' => 'r-u'],
    2 => ['label' => 'Admin',       'cls' => 'r-a'],
    3 => ['label' => 'Super Admin', 'cls' => 'r-s'],
];

$sevs = [
    ['bit' => 0, 'l' => 'NC',  't' => 'Not classified', 'c' => 'sv-nc'],
    ['bit' => 1, 'l' => 'Inf', 't' => 'Information',    'c' => 'sv-in'],
    ['bit' => 2, 'l' => 'W',   't' => 'Warning',        'c' => 'sv-w'],
    ['bit' => 3, 'l' => 'Avg', 't' => 'Average',        'c' => 'sv-av'],
    ['bit' => 4, 'l' => 'H',   't' => 'High',           'c' => 'sv-h'],
    ['bit' => 5, 'l' => 'D',   't' => 'Disaster',       'c' => 'sv-d'],
];

$roles   = $data['roles']   ?? [];
$actions = $data['actions'] ?? [];

$total_actions = count($actions);
$enabled       = count(array_filter($actions, fn($a) => $a['status'] === 0));
$disabled      = $total_actions - $enabled;
$total_ops     = array_sum(array_map(fn($a) => count($a['operations']), $actions));

?>
<style>
*{box-sizing:border-box}
.uo{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;color:#1e293b;display:flex;flex-direction:column;gap:16px;padding:16px 0;line-height:1.4}

/* Stats */
.uo-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.st{background:#fff;border:1px solid #e2e8f0;border-top:3px solid var(--c,#94a3b8);border-radius:8px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.st-n{font-size:2rem;font-weight:900;color:var(--c,#64748b);line-height:1;letter-spacing:-.03em}
.st-l{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-top:3px}

/* Search */
.uo-sb{display:flex;align-items:center;gap:10px}
.uo-si{border:1.5px solid #e2e8f0;border-radius:6px;padding:7px 12px;font-size:13px;width:320px;background:#fff;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s}
.uo-si:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.uo-cnt{font-size:.72rem;color:#94a3b8}

/* Action list */
.uo-list{display:flex;flex-direction:column;gap:12px}

/* Action card */
.ac{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);transition:box-shadow .2s}
.ac:hover{box-shadow:0 4px 16px rgba(0,0,0,.1)}
.ac.off{opacity:.75}

/* Card header */
.ac-hd{display:flex;align-items:center;gap:10px;padding:10px 16px;background:linear-gradient(135deg,#1e293b,#334155);color:#fff}
.ac-name{font-weight:700;font-size:.92rem;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#fff;text-decoration:none}
.ac-name:hover{text-decoration:underline;color:#e2e8f0}
.ac-badge{font-size:.61rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px;flex-shrink:0}
.ac-badge.on{background:#22c55e;color:#fff}
.ac-badge.off{background:#6b7280;color:#fff}

/* Operations */
.ac-ops{padding:0}
.op{border-top:1px solid #f1f5f9;padding:12px 16px}
.op:first-child{border-top:none}

/* Operation header */
.op-hd{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.op-step{width:22px;height:22px;border-radius:50%;background:#6366f1;color:#fff;font-size:.65rem;font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.op-lbl{font-size:.73rem;color:#64748b}
.op-mt{font-size:.72rem;font-weight:700;color:#6366f1;background:#eef2ff;border:1px solid #c7d2fe;border-radius:4px;padding:2px 8px;flex-shrink:0}
.op-mt.clickable{cursor:pointer;text-decoration:none;transition:background .15s}
.op-mt.clickable:hover{background:#c7d2fe;color:#4338ca}

/* Recipients table */
.rcp-tbl{width:100%;border-collapse:collapse}

/* Column headers */
.rcp-tbl .col-hd{border-bottom:2px solid #e2e8f0;background:#f8fafc}
.rcp-tbl .col-hd th{padding:5px 10px;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;text-align:left;white-space:nowrap}
.rcp-tbl .col-hd th:first-child{border-radius:4px 0 0 0}
.rcp-tbl .col-hd th:last-child{border-radius:0 4px 0 0}

/* Recipient rows */
.rcp-tbl tbody tr{border-bottom:1px solid #f4f6f8;transition:background .12s}
.rcp-tbl tbody tr:last-child{border-bottom:none}
.rcp-tbl tbody tr:hover{background:#fafbff}
.rcp-tbl td{padding:8px 10px;vertical-align:middle}

/* User cell */
.td-user{width:190px;min-width:160px}
.u-link{font-weight:700;font-size:.83rem;color:#1d4ed8;text-decoration:none;display:block}
.u-link:hover{text-decoration:underline;color:#1e40af}
.u-full{font-size:.7rem;color:#94a3b8;margin-top:1px}
.u-meta{display:flex;align-items:center;gap:5px;margin-top:4px;flex-wrap:wrap}
.u-role{font-size:.59rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;padding:1px 6px;border-radius:3px;border:1px solid transparent}
.r-u{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.r-a{background:#fffbeb;color:#92400e;border-color:#fde68a}
.r-s{background:#fdf4ff;color:#7e22ce;border-color:#e9d5ff}
.u-via{font-size:.64rem;color:#94a3b8}
.via-d{color:#22c55e;font-weight:700}
.via-g{color:#6366f1;font-weight:700}

/* Arrow cell */
.td-arr{width:20px;color:#cbd5e1;text-align:center;font-size:.85rem;padding:0 4px !important}

/* Media type cell */
.td-mt{width:130px;min-width:110px}
.mt-link{font-size:.78rem;font-weight:700;color:#7c3aed;text-decoration:none;display:inline-block;padding:2px 8px;border-radius:4px;background:#f5f3ff;border:1px solid #ddd6fe;transition:background .15s}
.mt-link:hover{background:#ddd6fe;color:#5b21b6;text-decoration:none}
.mt-off-badge{font-size:.57rem;font-weight:800;text-transform:uppercase;background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;border-radius:2px;padding:0 3px;margin-left:3px;vertical-align:middle}

/* Send to cell */
.td-send{max-width:200px}
.send-val{font-size:.72rem;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:190px;display:block}

/* Severity cell */
.td-sev{white-space:nowrap}
.ss{display:inline-flex;gap:2px}
.sc{width:26px;height:18px;border-radius:3px;display:inline-flex;align-items:center;justify-content:center;font-size:.54rem;font-weight:900;cursor:default;user-select:none}
.sv-nc{background:#7b8fa0;color:#fff}
.sv-in{background:#5b7fe0;color:#fff}
.sv-w{background:#e8b53f;color:#3d2a00}
.sv-av{background:#e8894a;color:#fff}
.sv-h{background:#d95f3b;color:#fff}
.sv-d{background:#c93535;color:#fff}
.sv-off{background:#f0f4f8;color:#d1d5db;border:1px solid #e2e8f0}

/* Multi-media: second+ rows in same user block */
.rcp-tbl td.td-extra{border-top:1px dashed #f0f4f8}

/* No media */
.no-med{font-size:.71rem;color:#cbd5e1;font-style:italic}

/* Empty */
.uo-empty{text-align:center;color:#94a3b8;font-style:italic;padding:48px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}
</style>

<div class="uo">

    <!-- Stats -->
    <div class="uo-stats">
        <div class="st" style="--c:#6366f1"><div class="st-n"><?= $total_actions ?></div><div class="st-l"><?= _('Trigger Actions') ?></div></div>
        <div class="st" style="--c:#22c55e"><div class="st-n"><?= $enabled ?></div><div class="st-l"><?= _('Enabled') ?></div></div>
        <div class="st" style="--c:#94a3b8"><div class="st-n"><?= $disabled ?></div><div class="st-l"><?= _('Disabled') ?></div></div>
        <div class="st" style="--c:#f59e0b"><div class="st-n"><?= $total_ops ?></div><div class="st-l"><?= _('Send Message Operations') ?></div></div>
    </div>

    <!-- Search -->
    <div class="uo-sb">
        <input type="text" id="uo-si" class="uo-si"
               placeholder="<?= _('Filter by action, username, media type, send to…') ?>"
               autocomplete="off">
        <span class="uo-cnt" id="uo-cnt"></span>
    </div>

    <!-- Action cards -->
    <?php if (empty($actions)): ?>
        <div class="uo-empty"><?= _('No trigger actions with send-message operations found.') ?></div>
    <?php else: ?>
    <div class="uo-list" id="uo-list">
    <?php foreach ($actions as $action):
        $is_on = ($action['status'] === 0);

        $srch_parts = [$action['name']];
        foreach ($action['operations'] as $op) {
            $srch_parts[] = $op['mt_label'];
            foreach ($op['recipients'] as $r) {
                $srch_parts[] = $r['user']['username'];
                $srch_parts[] = $r['user']['name'] . ' ' . $r['user']['surname'];
                $srch_parts[] = $r['via'];
                foreach ($r['medias'] as $m) {
                    $srch_parts[] = $m['media_type_name'];
                    $srch_parts[] = $m['sendto'];
                }
            }
        }
        $srch = strtolower(implode(' ', array_filter($srch_parts)));
    ?>
    <div class="ac<?= $is_on ? '' : ' off' ?>" data-s="<?= htmlspecialchars($srch) ?>">

        <!-- Action header — action name links to action edit page -->
        <div class="ac-hd">
            <a class="ac-name"
               href="zabbix.php?action=action.edit&actionid=<?= urlencode($action['actionid']) ?>"
               title="<?= htmlspecialchars($action['name']) ?>">
                <?= $is_on ? '&#9889;' : '&#9675;' ?>
                <?= htmlspecialchars($action['name']) ?>
            </a>
            <span class="ac-badge <?= $is_on ? 'on' : 'off' ?>"><?= $is_on ? _('Enabled') : _('Disabled') ?></span>
        </div>

        <!-- Operations -->
        <div class="ac-ops">
        <?php foreach ($action['operations'] as $step_i => $op): ?>
        <div class="op">

            <!-- Step header -->
            <div class="op-hd">
                <div class="op-step"><?= $step_i + 1 ?></div>
                <span class="op-lbl"><?= _('Send message via') ?></span>
                <?php if ($op['mediatypeid'] !== 0): ?>
                <a class="op-mt clickable"
                   href="zabbix.php?action=mediatype.edit&mediatypeid=<?= urlencode($op['mediatypeid']) ?>"
                   title="<?= _('Open media type') ?>">
                    <?= htmlspecialchars($op['mt_label']) ?>
                </a>
                <?php else: ?>
                <span class="op-mt"><?= htmlspecialchars($op['mt_label']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Recipients table -->
            <table class="rcp-tbl">
                <thead>
                    <tr class="col-hd">
                        <th><?= _('User') ?></th>
                        <th class="td-arr"></th>
                        <th><?= _('Media Type') ?></th>
                        <th><?= _('Send To') ?></th>
                        <th><?= _('Severity') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($op['recipients'] as $r):
                    $u        = $r['user'];
                    $fullname = trim($u['name'] . ' ' . $u['surname']);
                    $roleid   = $u['roleid'] ?? null;
                    $rtype    = ($roleid && isset($roles[$roleid])) ? (int) $roles[$roleid]['type'] : 1;
                    $ri       = $role_map[$rtype] ?? $role_map[1];
                    $is_dir   = ($r['via'] === 'direct');
                    $medias   = $r['medias'];
                    $med_cnt  = count($medias);
                ?>
                <?php if (empty($medias)): ?>
                <tr>
                    <td class="td-user" <?= $med_cnt > 1 ? 'rowspan="'.$med_cnt.'"' : '' ?>>
                        <a class="u-link" href="zabbix.php?action=user.edit&userid=<?= urlencode($u['userid']) ?>">
                            <?= htmlspecialchars($u['username']) ?>
                        </a>
                        <?php if ($fullname !== ''): ?>
                        <div class="u-full"><?= htmlspecialchars($fullname) ?></div>
                        <?php endif; ?>
                        <div class="u-meta">
                            <span class="u-role <?= $ri['cls'] ?>"><?= htmlspecialchars($ri['label']) ?></span>
                            <span class="u-via">
                                <?php if ($is_dir): ?>
                                    <span class="via-d">&#10148; <?= _('direct') ?></span>
                                <?php else: ?>
                                    <span class="via-g">&#128101; <?= htmlspecialchars($r['via']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </td>
                    <td class="td-arr">&#8594;</td>
                    <td colspan="3"><span class="no-med"><?= _('no matching media') ?></span></td>
                </tr>
                <?php else: foreach ($medias as $mi => $m):
                    $active = ((int) $m['active'] === 0);
                    $mask   = (int) $m['severity'];
                ?>
                <tr>
                    <?php if ($mi === 0): // User cell spans all media rows ?>
                    <td class="td-user"<?= $med_cnt > 1 ? ' rowspan="'.$med_cnt.'"' : '' ?>>
                        <a class="u-link" href="zabbix.php?action=user.edit&userid=<?= urlencode($u['userid']) ?>">
                            <?= htmlspecialchars($u['username']) ?>
                        </a>
                        <?php if ($fullname !== ''): ?>
                        <div class="u-full"><?= htmlspecialchars($fullname) ?></div>
                        <?php endif; ?>
                        <div class="u-meta">
                            <span class="u-role <?= $ri['cls'] ?>"><?= htmlspecialchars($ri['label']) ?></span>
                            <span class="u-via">
                                <?php if ($is_dir): ?>
                                    <span class="via-d">&#10148; <?= _('direct') ?></span>
                                <?php else: ?>
                                    <span class="via-g">&#128101; <?= htmlspecialchars($r['via']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </td>
                    <td class="td-arr"<?= $med_cnt > 1 ? ' rowspan="'.$med_cnt.'"' : '' ?>>&#8594;</td>
                    <?php endif; ?>
                    <!-- Media Type — clickable link -->
                    <td class="td-mt<?= $mi > 0 ? ' td-extra' : '' ?>"<?= !$active ? ' style="opacity:.45"' : '' ?>>
                        <a class="mt-link"
                           href="zabbix.php?action=mediatype.edit&mediatypeid=<?= urlencode($m['mediatypeid']) ?>"
                           title="<?= _('Open media type') ?>: <?= htmlspecialchars($m['media_type_name']) ?>">
                            <?= htmlspecialchars($m['media_type_name']) ?>
                        </a>
                        <?php if (!$active): ?><span class="mt-off-badge">off</span><?php endif; ?>
                    </td>
                    <!-- Send To -->
                    <td class="td-send<?= $mi > 0 ? ' td-extra' : '' ?>"<?= !$active ? ' style="opacity:.45"' : '' ?>>
                        <span class="send-val" title="<?= htmlspecialchars($m['sendto']) ?>"><?= htmlspecialchars($m['sendto']) ?></span>
                    </td>
                    <!-- Severity -->
                    <td class="td-sev<?= $mi > 0 ? ' td-extra' : '' ?>"<?= !$active ? ' style="opacity:.45"' : '' ?>>
                        <div class="ss">
                        <?php foreach ($sevs as $s):
                            $on = ($mask >> $s['bit']) & 1;
                        ?>
                        <div class="sc <?= $on ? $s['c'] : 'sv-off' ?>" title="<?= htmlspecialchars($s['t']) ?>"><?= $s['l'] ?></div>
                        <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div><!-- /.op -->
        <?php endforeach; ?>
        </div><!-- /.ac-ops -->

    </div><!-- /.ac -->
    <?php endforeach; ?>
    </div><!-- /#uo-list -->
    <?php endif; ?>

</div><!-- /.uo -->

<script>
(function(){
    var inp=document.getElementById('uo-si'),list=document.getElementById('uo-list'),cnt=document.getElementById('uo-cnt');
    if(!inp||!list)return;
    var cards=list.querySelectorAll('.ac');
    function run(){
        var q=inp.value.toLowerCase().trim(),n=0;
        cards.forEach(function(c){
            var ok=!q||c.dataset.s.indexOf(q)!==-1;
            c.style.display=ok?'':'none';
            if(ok)n++;
        });
        cnt.textContent=n+' / '+cards.length+' <?= _('action(s)') ?>';
    }
    inp.addEventListener('input',run);
    run();
    inp.focus();
}());
</script>
