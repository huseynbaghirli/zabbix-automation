<?php

declare(strict_types=1);

$role_map = [
    1 => ['label' => 'User',        'cls' => 'r-user'],
    2 => ['label' => 'Admin',       'cls' => 'r-admin'],
    3 => ['label' => 'Super Admin', 'cls' => 'r-super'],
];

$perm_map = [
    0 => ['label' => 'Deny', 'cls' => 'p-deny'],
    2 => ['label' => 'Read', 'cls' => 'p-read'],
    3 => ['label' => 'R/W',  'cls' => 'p-rw'],
];

$sevs = [
    ['bit' => 0, 'l' => 'NC',  't' => 'Not classified', 'c' => 'sev-nc'],
    ['bit' => 1, 'l' => 'Inf', 't' => 'Information',    'c' => 'sev-info'],
    ['bit' => 2, 'l' => 'W',   't' => 'Warning',        'c' => 'sev-warn'],
    ['bit' => 3, 'l' => 'Avg', 't' => 'Average',        'c' => 'sev-avg'],
    ['bit' => 4, 'l' => 'H',   't' => 'High',           'c' => 'sev-high'],
    ['bit' => 5, 'l' => 'D',   't' => 'Disaster',       'c' => 'sev-dis'],
];

$roles      = $data['roles']      ?? [];
$hostgroups = $data['hostgroups'] ?? [];
$users      = $data['users']      ?? [];

$total  = count($users);
$n_med  = count(array_filter($users, fn($u) => !empty($u['medias'])));
$n_act  = count(array_filter($users, fn($u) => !empty($u['trigger_actions'])));
$n_full = count(array_filter($users, fn($u) => !empty($u['medias']) && !empty($u['trigger_actions'])));
$n_mo   = count(array_filter($users, fn($u) => !empty($u['medias'])  && empty($u['trigger_actions'])));
$n_ao   = count(array_filter($users, fn($u) =>  empty($u['medias'])  && !empty($u['trigger_actions'])));
$n_none = $total - $n_full - $n_mo - $n_ao;

$wpct = fn(int $n) => $total > 0 ? round($n / $total * 100, 1) : 0;

?>
<style>
.uo{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;color:#1e293b;display:flex;flex-direction:column;gap:16px;padding:16px 0}
.uo-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.uo-stat{background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--c,#94a3b8);border-radius:8px;padding:16px 18px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.uo-stat-n{font-size:2.2rem;font-weight:900;color:var(--c,#94a3b8);line-height:1;letter-spacing:-.03em}
.uo-stat-l{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-top:4px}
.uo-cov{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.uo-cov-ttl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:8px}
.uo-bar{height:10px;border-radius:5px;display:flex;background:#f1f5f9;overflow:hidden}
.uo-seg{height:100%}
.seg-full{background:linear-gradient(90deg,#16a34a,#22c55e)}
.seg-media{background:linear-gradient(90deg,#7c3aed,#8b5cf6)}
.seg-action{background:linear-gradient(90deg,#d97706,#f59e0b)}
.seg-none{background:#e2e8f0}
.uo-leg{display:flex;flex-wrap:wrap;gap:14px;margin-top:8px}
.uo-leg-i{display:flex;align-items:center;gap:5px;font-size:.7rem;color:#64748b}
.uo-leg-d{width:10px;height:10px;border-radius:2px;flex-shrink:0}
.uo-sb{display:flex;align-items:center;gap:10px}
.uo-si{border:1.5px solid #e0e7ef;border-radius:6px;padding:7px 12px;font-size:13px;width:300px;background:#fff;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s}
.uo-si:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.uo-cnt{font-size:.73rem;color:#94a3b8}
.uo-list{display:flex;flex-direction:column;gap:10px}
.uo-card{background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--acc,#94a3b8);border-radius:8px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.06);transition:box-shadow .2s,transform .15s}
.uo-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.1);transform:translateY(-1px)}
.uo-card.cf{--acc:#22c55e}
.uo-card.ch{--acc:#f59e0b}
.uo-card.cn{--acc:#ef4444}
.uo-ch{display:flex;align-items:center;gap:10px;padding:8px 14px;background:#f8fafc;border-bottom:1px solid #f0f4f8;flex-wrap:wrap}
.uo-un{font-weight:700;font-size:.88rem;color:#0f172a}
.uo-uf{font-size:.74rem;color:#94a3b8}
.uo-role{margin-left:auto;font-size:.61rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:4px;border:1px solid transparent;flex-shrink:0}
.r-user{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.r-admin{background:#fffbeb;color:#92400e;border-color:#fde68a}
.r-super{background:#fdf4ff;color:#7e22ce;border-color:#e9d5ff}
.uo-dots{display:flex;gap:4px;margin-left:4px}
.uo-dot{width:7px;height:7px;border-radius:50%;background:#e2e8f0}
.uo-dot.dm{background:#8b5cf6}
.uo-dot.da{background:#f59e0b}
.uo-cb{display:flex;align-items:stretch;padding:12px 14px;gap:0;overflow-x:auto}
.fn-arr{display:flex;align-items:center;justify-content:center;flex-shrink:0;width:28px;position:relative}
.fn-arr::before{content:'';position:absolute;left:2px;right:9px;top:50%;height:1.5px;background:#cbd5e1;transform:translateY(-50%)}
.fn-arr::after{content:'';position:absolute;right:1px;top:50%;transform:translateY(-50%);width:0;height:0;border-top:4px solid transparent;border-bottom:4px solid transparent;border-left:6px solid #94a3b8}
.fn{display:flex;flex-direction:column;border-radius:6px;overflow:hidden;border:1px solid #e0e7ef}
.fn-h{padding:6px 11px;font-size:.6rem;font-weight:900;text-transform:uppercase;letter-spacing:.09em;color:#fff;white-space:nowrap}
.fn-b{padding:10px 11px;flex:1;background:#fff}
.fn-act{min-width:165px;max-width:195px;flex-shrink:0;border-color:#7c2d12}
.fn-act .fn-h{background:linear-gradient(135deg,#9a3412,#c2410c)}
.act-row{display:flex;align-items:flex-start;gap:5px;padding:2px 0;font-size:.75rem;color:#374151;line-height:1.4}
.act-dot{font-size:.44rem;margin-top:4px;flex-shrink:0}
.act-nm{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:165px}
.act-off .act-nm{color:#cbd5e1}
.fn-usr{min-width:155px;flex-shrink:0;border-color:#1e3a8a}
.fn-usr .fn-h{background:linear-gradient(135deg,#1e3a8a,#2563eb)}
.u-n{font-weight:700;color:#0f172a;font-size:.84rem}
.u-f{font-size:.72rem;color:#94a3b8;margin-top:1px}
.u-gs{margin-top:7px;display:flex;flex-wrap:wrap;gap:3px}
.u-g{font-size:.66rem;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:3px;padding:1px 6px;white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis}
.fn-med{flex:1;min-width:280px;border-color:#4c1d95}
.fn-med .fn-h{background:linear-gradient(135deg,#4c1d95,#7c3aed)}
.mtbl{width:100%;border-collapse:collapse}
.mtbl tr{border-bottom:1px solid #f4f4f8}
.mtbl tr:last-child{border-bottom:none}
.mtbl td{padding:5px 6px 5px 0;vertical-align:middle}
.mt-tp{font-size:.75rem;font-weight:700;color:#1e293b;white-space:nowrap;min-width:90px}
.mt-st{font-size:.7rem;color:#64748b;width:100%;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.mt-ob{font-size:.57rem;font-weight:800;text-transform:uppercase;background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;border-radius:2px;padding:0 3px;margin-left:3px}
.ss{display:flex;gap:2px;flex-shrink:0}
.sc{width:24px;height:18px;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:.54rem;font-weight:900;cursor:default;user-select:none}
.sev-nc{background:#7b8fa0;color:#fff}
.sev-info{background:#5b7fe0;color:#fff}
.sev-warn{background:#e8b53f;color:#3d2a00}
.sev-avg{background:#e8894a;color:#fff}
.sev-high{background:#d95f3b;color:#fff}
.sev-dis{background:#c93535;color:#fff}
.sev-off{background:#f0f4f8;color:#d1d5db;border:1px solid #e9edf2}
.uo-hg{border-top:1px solid #f1f5f9;padding:7px 14px;display:flex;align-items:center;gap:5px;flex-wrap:wrap;background:#fafcff}
.hg-l{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;flex-shrink:0}
.hg-c{display:inline-flex;align-items:center;gap:3px;font-size:.7rem;padding:2px 8px;border-radius:4px;border:1px solid transparent;white-space:nowrap;max-width:200px;overflow:hidden;text-overflow:ellipsis}
.p-deny{background:#fff1f2;color:#be123c;border-color:#fecdd3}
.p-read{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.p-rw{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.hg-nn{font-size:.73rem;color:#cbd5e1;font-style:italic}
.fn-em{font-size:.73rem;color:#cbd5e1;font-style:italic}
.uo-nf{text-align:center;color:#94a3b8;font-style:italic;padding:40px;background:#fff;border:1px solid #e2e8f0;border-radius:8px}
</style>

<div class="uo">

    <!-- Stats -->
    <div class="uo-stats">
        <?php foreach ([
            ['--c:#3b82f6', _('Total Users'),         $total],
            ['--c:#8b5cf6', _('With Media'),           $n_med],
            ['--c:#f59e0b', _('With Trigger Actions'), $n_act],
            ['--c:#22c55e', _('Fully Configured'),     $n_full],
        ] as [$s, $l, $v]): ?>
        <div class="uo-stat" style="<?= $s ?>">
            <div class="uo-stat-n"><?= $v ?></div>
            <div class="uo-stat-l"><?= htmlspecialchars($l) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Coverage -->
    <div class="uo-cov">
        <div class="uo-cov-ttl"><?= _('Configuration coverage') ?></div>
        <div class="uo-bar">
        <?php foreach ([
            ['seg-full',   $n_full, 'Fully configured'],
            ['seg-media',  $n_mo,   'Media only'],
            ['seg-action', $n_ao,   'Action only'],
            ['seg-none',   $n_none, 'Not configured'],
        ] as [$cls, $n, $tip]):
            if ($n <= 0) continue; ?>
            <div class="uo-seg <?= $cls ?>" style="width:<?= $wpct($n) ?>%" title="<?= $tip ?>: <?= $n ?>"></div>
        <?php endforeach; ?>
        </div>
        <div class="uo-leg">
        <?php foreach ([
            ['seg-full',   _('Fully configured'), $n_full],
            ['seg-media',  _('Media only'),        $n_mo],
            ['seg-action', _('Action only'),       $n_ao],
            ['seg-none',   _('Not configured'),    $n_none],
        ] as [$cls, $lbl, $n]): ?>
            <div class="uo-leg-i"><div class="uo-leg-d <?= $cls ?>"></div><?= htmlspecialchars($lbl) ?> — <strong><?= $n ?></strong></div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Search -->
    <div class="uo-sb">
        <input type="text" id="uo-si" class="uo-si" placeholder="<?= _('Filter by username, group, media, action…') ?>" autocomplete="off">
        <span class="uo-cnt" id="uo-cnt"></span>
    </div>

    <!-- Cards -->
    <?php if (empty($users)): ?>
        <div class="uo-nf"><?= _('No users found.') ?></div>
    <?php else: ?>
    <div class="uo-list" id="uo-list">
    <?php foreach ($users as $user):
        $fullname  = trim($user['name'] . ' ' . $user['surname']);
        $roleid    = $user['roleid'] ?? null;
        $role_type = ($roleid && isset($roles[$roleid])) ? (int) $roles[$roleid]['type'] : 1;
        $ri        = $role_map[$role_type] ?? $role_map[1];
        $medias    = $user['medias']                 ?? [];
        $actions   = $user['trigger_actions']        ?? [];
        $hg_perms  = $user['host_group_permissions'] ?? [];
        $grps      = $user['usrgrps']                ?? [];
        $has_m     = !empty($medias);
        $has_a     = !empty($actions);
        $conf      = ($has_m && $has_a) ? 'cf' : (($has_m || $has_a) ? 'ch' : 'cn');

        $srch = strtolower(implode(' ', array_filter([
            $user['username'], $fullname,
            implode(' ', array_column($grps,   'name')),
            implode(' ', array_column($medias, 'media_type_name')),
            implode(' ', array_map(fn($m) => $m['sendto'], $medias)),
            implode(' ', array_column($actions,'name')),
            implode(' ', array_map(fn($id) => $hostgroups[$id]['name'] ?? '', array_keys($hg_perms))),
        ])));
    ?>
    <div class="uo-card <?= $conf ?>" data-s="<?= htmlspecialchars($srch) ?>">

        <div class="uo-ch">
            <span class="uo-un"><?= htmlspecialchars($user['username']) ?></span>
            <?php if ($fullname !== ''): ?><span class="uo-uf"><?= htmlspecialchars($fullname) ?></span><?php endif; ?>
            <div class="uo-dots">
                <div class="uo-dot <?= $has_m ? 'dm' : '' ?>" title="Media"></div>
                <div class="uo-dot <?= $has_a ? 'da' : '' ?>" title="Actions"></div>
            </div>
            <span class="uo-role <?= $ri['cls'] ?>"><?= htmlspecialchars($ri['label']) ?></span>
        </div>

        <div class="uo-cb">

            <div class="fn fn-act">
                <div class="fn-h">&#9889; <?= _('Trigger Actions') ?></div>
                <div class="fn-b">
                <?php if (empty($actions)): ?>
                    <div class="fn-em"><?= _('none') ?></div>
                <?php else: foreach ($actions as $a):
                    $on = ((int) $a['status'] === 0); ?>
                    <div class="act-row <?= $on ? '' : 'act-off' ?>">
                        <span class="act-dot"><?= $on ? '●' : '○' ?></span>
                        <span class="act-nm" title="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></span>
                    </div>
                <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="fn-arr"></div>

            <div class="fn fn-usr">
                <div class="fn-h">&#128100; <?= _('User') ?></div>
                <div class="fn-b">
                    <div class="u-n"><?= htmlspecialchars($user['username']) ?></div>
                    <?php if ($fullname !== ''): ?><div class="u-f"><?= htmlspecialchars($fullname) ?></div><?php endif; ?>
                    <?php if (!empty($grps)): ?>
                    <div class="u-gs">
                        <?php foreach ($grps as $g): ?>
                        <span class="u-g" title="<?= htmlspecialchars($g['name']) ?>"><?= htmlspecialchars($g['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="fn-arr"></div>

            <div class="fn fn-med">
                <div class="fn-h">&#128276; <?= _('User Media') ?> &amp; <?= _('Use if severity') ?></div>
                <div class="fn-b">
                <?php if (empty($medias)): ?>
                    <div class="fn-em"><?= _('no media configured') ?></div>
                <?php else: ?>
                <table class="mtbl">
                <?php foreach ($medias as $m):
                    $mask   = (int) $m['severity'];
                    $active = ((int) $m['active'] === 0); ?>
                <tr<?= $active ? '' : ' style="opacity:.35"' ?>>
                    <td class="mt-tp"><?= htmlspecialchars($m['media_type_name']) ?><?php if (!$active): ?><span class="mt-ob">off</span><?php endif; ?></td>
                    <td class="mt-st" title="<?= htmlspecialchars($m['sendto']) ?>"><?= htmlspecialchars($m['sendto']) ?></td>
                    <td><div class="ss"><?php foreach ($sevs as $s): $on = ($mask >> $s['bit']) & 1; ?>
                        <div class="sc <?= $on ? $s['c'] : 'sev-off' ?>" title="<?= htmlspecialchars($s['t']) ?>"><?= $s['l'] ?></div>
                    <?php endforeach; ?></div></td>
                </tr>
                <?php endforeach; ?>
                </table>
                <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="uo-hg">
            <span class="hg-l">&#127968; <?= _('Host Access') ?>:</span>
            <?php if (empty($hg_perms)): ?>
                <span class="hg-nn"><?= _('none') ?></span>
            <?php else: foreach ($hg_perms as $gid => $perm):
                $gname = $hostgroups[$gid]['name'] ?? 'id:'.$gid;
                $pi    = $perm_map[$perm] ?? ['label'=>'?','cls'=>'p-deny']; ?>
            <span class="hg-c <?= $pi['cls'] ?>" title="<?= htmlspecialchars($gname) ?>">
                <?= htmlspecialchars($gname) ?> <strong>[<?= $pi['label'] ?>]</strong>
            </span>
            <?php endforeach; endif; ?>
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
    var cards=list.querySelectorAll('.uo-card');
    function run(){var q=inp.value.toLowerCase().trim(),n=0;cards.forEach(function(c){var ok=!q||c.dataset.s.indexOf(q)!==-1;c.style.display=ok?'':'none';if(ok)n++;});cnt.textContent=n+' / '+cards.length+' <?= _('user(s)') ?>';}
    inp.addEventListener('input',run);run();inp.focus();
}());
</script>
