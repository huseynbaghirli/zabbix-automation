<?php

declare(strict_types=1);

/**
 * @var array  $data['actions']   Action-centric list (see UserAlerts.php)
 * @var array  $data['roles']     roleid → role object
 */

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

$total_actions  = count($actions);
$enabled        = count(array_filter($actions, fn($a) => $a['status'] === 0));
$disabled       = $total_actions - $enabled;
$total_ops      = array_sum(array_map(fn($a) => count($a['operations']), $actions));

?>
<style>
*{box-sizing:border-box}
.uo{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;color:#1e293b;display:flex;flex-direction:column;gap:16px;padding:16px 0;line-height:1.4}

/* ── Stats ── */
.uo-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.st{background:#fff;border:1px solid #e2e8f0;border-top:3px solid var(--c,#94a3b8);border-radius:8px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.st-n{font-size:2rem;font-weight:900;color:var(--c,#64748b);line-height:1;letter-spacing:-.03em}
.st-l{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-top:3px}

/* ── Search ── */
.uo-sb{display:flex;align-items:center;gap:10px}
.uo-si{border:1.5px solid #e2e8f0;border-radius:6px;padding:7px 12px;font-size:13px;width:320px;background:#fff;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s}
.uo-si:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.uo-cnt{font-size:.72rem;color:#94a3b8}

/* ── Action cards list ── */
.uo-list{display:flex;flex-direction:column;gap:12px}

/* ── Action card ── */
.ac{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);transition:box-shadow .2s}
.ac:hover{box-shadow:0 4px 16px rgba(0,0,0,.1)}
.ac.off{opacity:.7}

/* Card header */
.ac-hd{display:flex;align-items:center;gap:10px;padding:10px 16px;background:linear-gradient(135deg,#1e293b,#334155);color:#fff}
.ac-name{font-weight:700;font-size:.92rem;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ac-badge{font-size:.61rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px;flex-shrink:0}
.ac-badge.on{background:#22c55e;color:#fff}
.ac-badge.off{background:#6b7280;color:#fff}

/* ── Operations inside card ── */
.ac-ops{padding:0}

.op{border-top:1px solid #f1f5f9;padding:12px 16px}
.op:first-child{border-top:none}

/* Operation header: step + media filter */
.op-hd{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.op-step{width:22px;height:22px;border-radius:50%;background:#6366f1;color:#fff;font-size:.65rem;font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.op-mt{font-size:.72rem;font-weight:700;color:#6366f1;background:#eef2ff;border:1px solid #c7d2fe;border-radius:4px;padding:2px 8px;flex-shrink:0}

/* ── Recipients grid ── */
.op-recipients{display:flex;flex-direction:column;gap:6px}

.rcp{display:flex;align-items:flex-start;gap:8px;padding:8px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px}

/* User info column */
.rcp-user{width:170px;flex-shrink:0}
.rcp-uname{font-weight:700;font-size:.82rem;color:#0f172a}
.rcp-ufull{font-size:.7rem;color:#94a3b8;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rcp-role{display:inline-block;margin-top:4px;font-size:.59rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;padding:1px 6px;border-radius:3px;border:1px solid transparent}
.r-u{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.r-a{background:#fffbeb;color:#92400e;border-color:#fde68a}
.r-s{background:#fdf4ff;color:#7e22ce;border-color:#e9d5ff}

/* Via badge */
.rcp-via{font-size:.65rem;color:#94a3b8;margin-top:3px}
.via-direct{color:#22c55e;font-weight:700}
.via-group{color:#6366f1;font-weight:700}

/* Connector dot */
.rcp-dot{color:#cbd5e1;font-size:.8rem;margin-top:6px;flex-shrink:0}

/* Media list column */
.rcp-medias{flex:1;display:flex;flex-direction:column;gap:4px}

/* Single media row */
.med-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.med-type{font-size:.73rem;font-weight:700;color:#1e293b;white-space:nowrap;min-width:85px}
.med-send{font-size:.71rem;color:#64748b;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px}
.med-off{font-size:.58rem;font-weight:800;text-transform:uppercase;background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;border-radius:2px;padding:0 3px}

/* ── Severity strip ── */
.ss{display:flex;gap:2px;flex-shrink:0}
.sc{width:26px;height:18px;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:.54rem;font-weight:900;cursor:default;user-select:none}
.sv-nc{background:#7b8fa0;color:#fff}
.sv-in{background:#5b7fe0;color:#fff}
.sv-w{background:#e8b53f;color:#3d2a00}
.sv-av{background:#e8894a;color:#fff}
.sv-h{background:#d95f3b;color:#fff}
.sv-d{background:#c93535;color:#fff}
.sv-off{background:#f0f4f8;color:#d1d5db;border:1px solid #e2e8f0}

/* No media */
.no-med{font-size:.72rem;color:#cbd5e1;font-style:italic}

/* Empty state */
.uo-empty{text-align:center;color:#94a3b8;font-style:italic;padding:48px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}
</style>

<div class="uo">

    <!-- Stats -->
    <div class="uo-stats">
        <div class="st" style="--c:#6366f1"><div class="st-n"><?= $total_actions ?></div><div class="st-l"><?= _('Trigger Actions') ?></div></div>
        <div class="st" style="--c:#22c55e"><div class="st-n"><?= $enabled ?></div><div class="st-l"><?= _('Enabled') ?></div></div>
        <div class="st" style="--c:#94a3b8"><div class="st-n"><?= $disabled ?></div><div class="st-l"><?= _('Disabled') ?></div></div>
        <div class="st" style="--c:#f59e0b"><div class="st-n"><?= $total_ops ?></div><div class="st-l"><?= _('Total Operations') ?></div></div>
    </div>

    <!-- Search -->
    <div class="uo-sb">
        <input type="text" id="uo-si" class="uo-si"
               placeholder="<?= _('Filter by action name, username, media type…') ?>"
               autocomplete="off">
        <span class="uo-cnt" id="uo-cnt"></span>
    </div>

    <!-- Action cards -->
    <?php if (empty($actions)): ?>
        <div class="uo-empty"><?= _('No trigger actions with send-message operations found.') ?></div>
    <?php else: ?>
    <div class="uo-list" id="uo-list">
    <?php foreach ($actions as $action):
        $is_on = $action['status'] === 0;

        // Build search string for this action
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

        <!-- Action header -->
        <div class="ac-hd">
            <span class="ac-name" title="<?= htmlspecialchars($action['name']) ?>">
                <?= $is_on ? '&#9889;' : '&#9675;' ?>
                <?= htmlspecialchars($action['name']) ?>
            </span>
            <span class="ac-badge <?= $is_on ? 'on' : 'off' ?>"><?= $is_on ? _('Enabled') : _('Disabled') ?></span>
        </div>

        <!-- Operations -->
        <div class="ac-ops">
        <?php foreach ($action['operations'] as $step_i => $op): ?>
        <div class="op">

            <!-- Operation header -->
            <div class="op-hd">
                <div class="op-step"><?= $step_i + 1 ?></div>
                <span><?= _('Send message via') ?></span>
                <span class="op-mt"><?= htmlspecialchars($op['mt_label']) ?></span>
            </div>

            <!-- Recipients -->
            <div class="op-recipients">
            <?php foreach ($op['recipients'] as $r):
                $u        = $r['user'];
                $fullname = trim($u['name'] . ' ' . $u['surname']);
                $roleid   = $u['roleid'] ?? null;
                $rtype    = ($roleid && isset($roles[$roleid])) ? (int) $roles[$roleid]['type'] : 1;
                $ri       = $role_map[$rtype] ?? $role_map[1];
                $is_direct = $r['via'] === 'direct';
            ?>
            <div class="rcp">

                <!-- User -->
                <div class="rcp-user">
                    <div class="rcp-uname"><?= htmlspecialchars($u['username']) ?></div>
                    <?php if ($fullname !== ''): ?>
                    <div class="rcp-ufull"><?= htmlspecialchars($fullname) ?></div>
                    <?php endif; ?>
                    <span class="rcp-role <?= $ri['cls'] ?>"><?= htmlspecialchars($ri['label']) ?></span>
                    <div class="rcp-via">
                        <?php if ($is_direct): ?>
                            <span class="via-direct">&#10148; direct</span>
                        <?php else: ?>
                            <span class="via-group">&#128101; <?= htmlspecialchars($r['via']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Connector -->
                <div class="rcp-dot">&#8594;</div>

                <!-- Media entries -->
                <div class="rcp-medias">
                <?php if (empty($r['medias'])): ?>
                    <div class="no-med"><?= _('no matching media configured') ?></div>
                <?php else: foreach ($r['medias'] as $m):
                    $active = ((int) $m['active'] === 0);
                    $mask   = (int) $m['severity'];
                ?>
                <div class="med-row"<?= $active ? '' : ' style="opacity:.4"' ?>>
                    <span class="med-type"><?= htmlspecialchars($m['media_type_name']) ?></span>
                    <span class="med-send" title="<?= htmlspecialchars($m['sendto']) ?>"><?= htmlspecialchars($m['sendto']) ?></span>
                    <?php if (!$active): ?><span class="med-off">off</span><?php endif; ?>
                    <div class="ss">
                    <?php foreach ($sevs as $s):
                        $on = ($mask >> $s['bit']) & 1;
                    ?>
                    <div class="sc <?= $on ? $s['c'] : 'sv-off' ?>" title="<?= htmlspecialchars($s['t']) ?>"><?= $s['l'] ?></div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
            </div>

        </div>
        <?php endforeach; ?>
        </div>

    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

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
