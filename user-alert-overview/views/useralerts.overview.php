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

$w = fn(int $n) => $total > 0 ? round($n / $total * 100, 1) : 0;

?>
<div class="uo-page">

    <!-- Stats -->
    <div class="uo-stats">
        <?php foreach ([
            ['--c:#3b82f6', _('Total Users'),         $total],
            ['--c:#8b5cf6', _('With Media'),           $n_med],
            ['--c:#f59e0b', _('With Trigger Actions'), $n_act],
            ['--c:#22c55e', _('Fully Configured'),     $n_full],
        ] as [$style, $lbl, $val]): ?>
        <div class="uo-stat" style="<?= $style ?>">
            <div class="uo-stat-n"><?= $val ?></div>
            <div class="uo-stat-l"><?= htmlspecialchars($lbl) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Coverage bar -->
    <div class="uo-cov">
        <div class="uo-cov-lbl"><?= _('Configuration coverage') ?></div>
        <div class="uo-bar">
            <?php foreach ([
                ['seg-full',   $n_full, 'Fully configured'],
                ['seg-media',  $n_mo,   'Media only'],
                ['seg-action', $n_ao,   'Action only'],
                ['seg-none',   $n_none, 'Not configured'],
            ] as [$cls, $n, $tip]):
                if ($n <= 0) continue;
            ?>
            <div class="uo-seg <?= $cls ?>" style="width:<?= $w($n) ?>%" title="<?= htmlspecialchars($tip) ?>: <?= $n ?>"></div>
            <?php endforeach; ?>
        </div>
        <div class="uo-leg">
            <?php foreach ([
                ['seg-full',   _('Fully configured'), $n_full],
                ['seg-media',  _('Media only'),        $n_mo],
                ['seg-action', _('Action only'),       $n_ao],
                ['seg-none',   _('Not configured'),    $n_none],
            ] as [$cls, $lbl, $n]): ?>
            <div class="uo-leg-i">
                <div class="uo-leg-d <?= $cls ?>"></div>
                <?= htmlspecialchars($lbl) ?> <strong><?= $n ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search -->
    <div class="uo-search-bar">
        <input type="text" id="uo-search" class="uo-search-input"
               placeholder="<?= _('Filter…') ?>" autocomplete="off">
        <span class="uo-count" id="uo-count"></span>
    </div>

    <!-- Flow cards -->
    <?php if (empty($users)): ?>
        <div class="uo-no-users"><?= _('No users found.') ?></div>
    <?php else: ?>
    <div class="uo-list" id="uo-list">
    <?php foreach ($users as $user):
        $fullname  = trim($user['name'] . ' ' . $user['surname']);
        $roleid    = $user['roleid'] ?? null;
        $role_type = ($roleid && isset($roles[$roleid])) ? (int) $roles[$roleid]['type'] : 1;
        $ri        = $role_map[$role_type] ?? $role_map[1];
        $medias    = $user['medias']               ?? [];
        $actions   = $user['trigger_actions']      ?? [];
        $hg_perms  = $user['host_group_permissions'] ?? [];
        $grps      = $user['usrgrps']              ?? [];

        $search = strtolower(implode(' ', array_filter([
            $user['username'], $fullname,
            implode(' ', array_column($grps,    'name')),
            implode(' ', array_column($medias,  'media_type_name')),
            implode(' ', array_map(fn($m) => $m['sendto'], $medias)),
            implode(' ', array_column($actions, 'name')),
            implode(' ', array_map(fn($id) => $hostgroups[$id]['name'] ?? '', array_keys($hg_perms))),
        ])));
    ?>
    <div class="uo-card" data-search="<?= htmlspecialchars($search) ?>">

        <!-- Header -->
        <div class="uo-card-head">
            <span class="uo-uname"><?= htmlspecialchars($user['username']) ?></span>
            <?php if ($fullname !== ''): ?>
            <span class="uo-ufull"><?= htmlspecialchars($fullname) ?></span>
            <?php endif; ?>
            <span class="uo-role <?= $ri['cls'] ?>"><?= htmlspecialchars($ri['label']) ?></span>
        </div>

        <!-- Flow -->
        <div class="uo-card-body">

            <!-- Node: Trigger Actions -->
            <div class="fn fn-actions">
                <div class="fn-hdr"><?= _('Trigger Actions') ?></div>
                <div class="fn-body">
                <?php if (empty($actions)): ?>
                    <div class="fn-empty"><?= _('none') ?></div>
                <?php else: foreach ($actions as $a):
                    $on = ((int) $a['status'] === 0);
                ?>
                    <div class="act-row <?= $on ? 'act-on' : 'act-off' ?>"
                         title="<?= htmlspecialchars($a['name']) ?>">
                        <span class="act-dot"><?= $on ? '●' : '○' ?></span>
                        <?= htmlspecialchars($a['name']) ?>
                    </div>
                <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="fn-arr">→</div>

            <!-- Node: User -->
            <div class="fn fn-user">
                <div class="fn-hdr"><?= _('User') ?></div>
                <div class="fn-body">
                    <div class="u-name"><?= htmlspecialchars($user['username']) ?></div>
                    <?php if ($fullname !== ''): ?>
                    <div class="u-full"><?= htmlspecialchars($fullname) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($grps)): ?>
                    <div class="u-groups">
                        <?php foreach ($grps as $g): ?>
                        <span class="u-grp" title="<?= htmlspecialchars($g['name']) ?>"><?= htmlspecialchars($g['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="fn-arr">→</div>

            <!-- Node: User Media + Severity -->
            <div class="fn fn-media">
                <div class="fn-hdr"><?= _('User Media') ?> &amp; <?= _('Use if severity') ?></div>
                <div class="fn-body">
                <?php if (empty($medias)): ?>
                    <div class="fn-empty"><?= _('no media configured') ?></div>
                <?php else: ?>
                <table class="media-tbl">
                <?php foreach ($medias as $m):
                    $mask   = (int) $m['severity'];
                    $active = ((int) $m['active'] === 0);
                ?>
                <tr<?= $active ? '' : ' style="opacity:.4"' ?>>
                    <td class="mt-type">
                        <?= htmlspecialchars($m['media_type_name']) ?>
                        <?php if (!$active): ?><span class="mt-off">off</span><?php endif; ?>
                    </td>
                    <td class="mt-send" title="<?= htmlspecialchars($m['sendto']) ?>"><?= htmlspecialchars($m['sendto']) ?></td>
                    <td>
                        <div class="sev-strip">
                        <?php foreach ($sevs as $s):
                            $on = ($mask >> $s['bit']) & 1;
                        ?>
                        <div class="sev-c <?= $on ? $s['c'] : 'sev-off' ?>" title="<?= $s['t'] ?>"><?= $s['l'] ?></div>
                        <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </table>
                <?php endif; ?>
                </div>
            </div>

        </div><!-- /.uo-card-body -->

        <!-- Host group access -->
        <div class="uo-hg">
            <span class="hg-lbl"><?= _('Host Access') ?>:</span>
            <?php if (empty($hg_perms)): ?>
                <span class="hg-none"><?= _('none') ?></span>
            <?php else: foreach ($hg_perms as $gid => $perm):
                $gname = $hostgroups[$gid]['name'] ?? 'id:' . $gid;
                $pi    = $perm_map[$perm] ?? ['label' => '?', 'cls' => 'p-deny'];
            ?>
            <span class="hg-chip <?= $pi['cls'] ?>" title="<?= htmlspecialchars($gname) ?>">
                <?= htmlspecialchars($gname) ?> <strong>[<?= $pi['label'] ?>]</strong>
            </span>
            <?php endforeach; endif; ?>
        </div>

    </div><!-- /.uo-card -->
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
(function () {
    var inp   = document.getElementById('uo-search');
    var list  = document.getElementById('uo-list');
    var count = document.getElementById('uo-count');
    if (!inp || !list) return;
    var cards = list.querySelectorAll('.uo-card');
    function run() {
        var q = inp.value.toLowerCase().trim(), n = 0;
        cards.forEach(function (c) {
            var show = !q || c.dataset.search.indexOf(q) !== -1;
            c.style.display = show ? '' : 'none';
            if (show) n++;
        });
        count.textContent = n + ' / ' + cards.length + ' <?= _('user(s)') ?>';
    }
    inp.addEventListener('input', run);
    run();
    inp.focus();
}());
</script>
