<?php

declare(strict_types=1);

/**
 * @var array $data['users']
 * @var array $data['roles']
 * @var array $data['hostgroups']
 * @var array $data['media_types']
 */

// ── Lookup tables ─────────────────────────────────────────────────────────────

$role_type_labels = [
    1 => ['label' => 'User',        'class' => 'utype-user'],
    2 => ['label' => 'Admin',       'class' => 'utype-admin'],
    3 => ['label' => 'Super Admin', 'class' => 'utype-super'],
];

$perm_labels = [
    0 => ['label' => 'Denied', 'class' => 'perm-deny'],
    2 => ['label' => 'Read',   'class' => 'perm-read'],
    3 => ['label' => 'R/W',    'class' => 'perm-rw'],
];

$severities = [
    ['bit' => 0, 'label' => 'NC',  'title' => 'Not classified', 'class' => 'sev-nc'],
    ['bit' => 1, 'label' => 'Inf', 'title' => 'Information',    'class' => 'sev-info'],
    ['bit' => 2, 'label' => 'W',   'title' => 'Warning',        'class' => 'sev-warn'],
    ['bit' => 3, 'label' => 'Avg', 'title' => 'Average',        'class' => 'sev-avg'],
    ['bit' => 4, 'label' => 'H',   'title' => 'High',           'class' => 'sev-high'],
    ['bit' => 5, 'label' => 'D',   'title' => 'Disaster',       'class' => 'sev-dis'],
];

// Avatar palette — deterministic per username
$palette = ['#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#ef4444','#22c55e','#6366f1'];

$roles      = $data['roles']      ?? [];
$hostgroups = $data['hostgroups'] ?? [];
$users      = $data['users']      ?? [];

// ── Counts ────────────────────────────────────────────────────────────────────
$total         = count($users);
$n_media       = count(array_filter($users, fn($u) => !empty($u['medias'])));
$n_act         = count(array_filter($users, fn($u) => !empty($u['trigger_actions'])));
$n_full        = count(array_filter($users, fn($u) => !empty($u['medias']) && !empty($u['trigger_actions'])));
$n_media_only  = count(array_filter($users, fn($u) => !empty($u['medias'])  && empty($u['trigger_actions'])));
$n_action_only = count(array_filter($users, fn($u) =>  empty($u['medias'])  && !empty($u['trigger_actions'])));
$n_none        = $total - $n_full - $n_media_only - $n_action_only;

$pct   = fn(int $n) => $total > 0 ? round($n / $total * 100, 1) : 0;
$circ  = round(2 * M_PI * 18, 2); // r=18 → ≈ 113.1

?>
<div class="uo-page">

    <!-- ── Page header ──────────────────────────────────────────────────── -->
    <div class="uo-header">
        <div>
            <div class="uo-title"><?= htmlspecialchars($data['title']) ?></div>
            <div class="uo-subtitle">
                Hər istifadəçi üçün alert axışı: Trigger Actions → User → User Media → Severity
            </div>
        </div>
    </div>

    <!-- ── Stat cards ───────────────────────────────────────────────────── -->
    <div class="uo-stats-row">
    <?php
    foreach ([
        ['c-total',  _('Total Users'),         $total,   $total],
        ['c-media',  _('With Media'),           $n_media, $total],
        ['c-action', _('With Trigger Actions'), $n_act,   $total],
        ['c-full',   _('Fully Configured'),     $n_full,  $total],
    ] as [$cls, $lbl, $val, $of]):
        $p      = $of > 0 ? round($val / $of * 100) : 0;
        $offset = round($circ * (1 - $p / 100), 2);
    ?>
    <div class="uo-stat-card <?= $cls ?>">
        <div class="uo-stat-body">
            <div class="uo-stat-value"><?= $val ?></div>
            <div class="uo-ring">
                <svg viewBox="0 0 44 44">
                    <circle class="ring-bg"   cx="22" cy="22" r="18"/>
                    <circle class="ring-fill" cx="22" cy="22" r="18"
                        stroke-dasharray="<?= $circ ?>"
                        stroke-dashoffset="<?= $offset ?>"/>
                </svg>
                <div class="uo-ring-pct"><?= $p ?>%</div>
            </div>
        </div>
        <div class="uo-stat-label"><?= htmlspecialchars($lbl) ?></div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- ── Coverage stacked bar ─────────────────────────────────────────── -->
    <div class="uo-coverage">
        <div class="uo-coverage-title"><?= _('Configuration Coverage') ?></div>
        <div class="uo-bar-track">
        <?php foreach ([
            ['uo-bar-full',   $n_full,        _('Fully configured')],
            ['uo-bar-media',  $n_media_only,  _('Media only')],
            ['uo-bar-action', $n_action_only, _('Action only')],
            ['uo-bar-none',   $n_none,        _('Not configured')],
        ] as [$seg, $n, $tip]):
            $w = $pct($n);
            if ($w <= 0) continue;
        ?>
        <div class="uo-bar-seg <?= $seg ?>" style="width:<?= $w ?>%"
             title="<?= htmlspecialchars($tip) ?>: <?= $n ?> (<?= $w ?>%)">
            <?php if ($w > 7): ?><?= $n ?><?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="uo-bar-legend">
        <?php foreach ([
            ['uo-bar-full',   _('Fully configured'), $n_full],
            ['uo-bar-media',  _('Media only'),        $n_media_only],
            ['uo-bar-action', _('Action only'),       $n_action_only],
            ['uo-bar-none',   _('Not configured'),    $n_none],
        ] as [$dot, $lbl, $n]): ?>
        <div class="uo-legend-item">
            <div class="uo-legend-dot <?= $dot ?>"></div>
            <?= htmlspecialchars($lbl) ?> — <strong><?= $n ?></strong>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Search ───────────────────────────────────────────────────────── -->
    <div class="uo-search-bar">
        <div class="uo-search-wrap">
            <span class="uo-search-icon">&#9906;</span>
            <input type="text" id="uo-search" class="uo-search-input"
                   placeholder="<?= _('Filter by username, group, media, action…') ?>"
                   autocomplete="off">
        </div>
        <span class="uo-count-badge" id="uo-count"></span>
    </div>

    <!-- ── Flow cards ───────────────────────────────────────────────────── -->
    <?php if (empty($users)): ?>
    <div class="uo-no-users"><?= _('No users found.') ?></div>
    <?php else: ?>
    <div class="uo-flow-list" id="uo-list">
    <?php foreach ($users as $user):
        $fullname    = trim($user['name'] . ' ' . $user['surname']);
        $roleid      = $user['roleid'] ?? null;
        $role_type   = ($roleid && isset($roles[$roleid])) ? (int) $roles[$roleid]['type'] : 1;
        $type_info   = $role_type_labels[$role_type] ?? $role_type_labels[1];
        $medias      = $user['medias']               ?? [];
        $actions     = $user['trigger_actions']      ?? [];
        $hg_perms    = $user['host_group_permissions'] ?? [];
        $grps        = $user['usrgrps']              ?? [];

        $av_color    = $palette[abs(crc32($user['username'])) % count($palette)];
        $initials    = strtoupper(substr($user['username'], 0, 1));

        $search_text = strtolower(implode(' ', array_filter([
            $user['username'],
            $fullname,
            implode(' ', array_column($grps,    'name')),
            implode(' ', array_column($medias,  'media_type_name')),
            implode(' ', array_map(fn($m) => $m['sendto'], $medias)),
            implode(' ', array_column($actions, 'name')),
            implode(' ', array_map(fn($id) => $hostgroups[$id]['name'] ?? '', array_keys($hg_perms))),
        ])));
    ?>
    <div class="uo-flow-card" data-search="<?= htmlspecialchars($search_text) ?>">

        <!-- ─── Flow row ─────────────────────────────────────────────── -->
        <div class="uo-flow-row">

            <!-- Node: Trigger Actions -->
            <div class="uo-node n-actions">
                <div class="uo-node-hdr">
                    <span class="uo-node-icon">&#9889;</span>
                    <?= _('Trigger Actions') ?>
                </div>
                <div class="uo-node-body">
                <?php if (empty($actions)): ?>
                    <div class="uo-empty-node"><?= _('No actions') ?></div>
                <?php else: ?>
                    <div class="uo-act-list">
                    <?php foreach ($actions as $act):
                        $on = ((int) $act['status'] === 0);
                    ?>
                    <div class="uo-act-chip <?= $on ? 'act-on' : 'act-off' ?>"
                         title="<?= htmlspecialchars($act['name']) . ($on ? '' : ' (' . _('disabled') . ')') ?>">
                        <span class="uo-act-dot"><?= $on ? '●' : '○' ?></span>
                        <?= htmlspecialchars($act['name']) ?>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <!-- Arrow -->
            <div class="uo-arr"></div>

            <!-- Node: User -->
            <div class="uo-node n-user">
                <div class="uo-node-hdr">
                    <span class="uo-node-icon">&#128100;</span>
                    <?= _('User') ?>
                </div>
                <div class="uo-node-body">
                    <div class="uo-user-cell">
                        <div class="uo-avatar" style="background:<?= $av_color ?>"><?= htmlspecialchars($initials) ?></div>
                        <div class="uo-user-info">
                            <div class="uo-user-name"><?= htmlspecialchars($user['username']) ?></div>
                            <?php if ($fullname !== ''): ?>
                            <div class="uo-user-full"><?= htmlspecialchars($fullname) ?></div>
                            <?php endif; ?>
                            <span class="uo-type-badge <?= $type_info['class'] ?>"><?= htmlspecialchars($type_info['label']) ?></span>
                        </div>
                    </div>
                    <?php if (!empty($grps)): ?>
                    <div class="uo-grp-row">
                        <?php foreach ($grps as $grp): ?>
                        <span class="uo-grp-chip" title="<?= htmlspecialchars($grp['name']) ?>"><?= htmlspecialchars($grp['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Arrow -->
            <div class="uo-arr"></div>

            <!-- Node: User Media -->
            <div class="uo-node n-media">
                <div class="uo-node-hdr">
                    <span class="uo-node-icon">&#128276;</span>
                    <?= _('User Media') ?> &amp; <?= _('Severity') ?>
                </div>
                <div class="uo-node-body">
                <?php if (empty($medias)): ?>
                    <div class="uo-empty-node"><?= _('No media configured') ?></div>
                <?php else: foreach ($medias as $media):
                    $sev_mask  = (int) $media['severity'];
                    $is_active = ((int) $media['active'] === 0);
                ?>
                <div class="uo-media-entry<?= $is_active ? '' : ' is-disabled' ?>">
                    <div class="uo-media-top">
                        <span class="uo-media-name"><?= htmlspecialchars($media['media_type_name']) ?></span>
                        <?php if (!$is_active): ?>
                        <span class="uo-off-badge">OFF</span>
                        <?php endif; ?>
                    </div>
                    <div class="uo-sendto" title="<?= htmlspecialchars($media['sendto']) ?>"><?= htmlspecialchars($media['sendto']) ?></div>
                    <div class="uo-sev-strip">
                    <?php foreach ($severities as $s):
                        $on = ($sev_mask >> $s['bit']) & 1;
                    ?>
                    <div class="uo-sev-cell <?= $on ? $s['class'] : 'sev-off' ?>"
                         title="<?= htmlspecialchars($s['title']) ?>"><?= $s['label'] ?></div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                </div>
            </div>

        </div><!-- /.uo-flow-row -->

        <!-- ─── Host access bar ──────────────────────────────────────── -->
        <div class="uo-access-row">
            <span class="uo-access-lbl">&#127968; <?= _('Host Group Access') ?>:</span>
            <?php if (empty($hg_perms)): ?>
            <span style="font-size:.78rem;color:#cbd5e1;font-style:italic"><?= _('No access defined') ?></span>
            <?php else: foreach ($hg_perms as $gid => $perm):
                $gname = $hostgroups[$gid]['name'] ?? '(id:' . $gid . ')';
                $pi    = $perm_labels[$perm] ?? ['label' => '?', 'class' => 'perm-deny'];
            ?>
            <span class="uo-perm-chip <?= $pi['class'] ?>"
                  title="<?= htmlspecialchars($gname) ?> — <?= htmlspecialchars($pi['label']) ?>">
                <?= htmlspecialchars($gname) ?>&nbsp;<span class="uo-perm-lvl">[<?= $pi['label'] ?>]</span>
            </span>
            <?php endforeach; endif; ?>
        </div>

    </div><!-- /.uo-flow-card -->
    <?php endforeach; ?>
    </div><!-- /#uo-list -->
    <?php endif; ?>

</div><!-- /.uo-page -->

<script>
(function () {
    'use strict';
    var search  = document.getElementById('uo-search');
    var list    = document.getElementById('uo-list');
    var counter = document.getElementById('uo-count');
    if (!search || !list) return;

    var cards = list.querySelectorAll('.uo-flow-card');

    function refresh() {
        var q = search.value.toLowerCase().trim();
        var n = 0;
        cards.forEach(function (c) {
            var show = !q || c.dataset.search.indexOf(q) !== -1;
            c.style.display = show ? '' : 'none';
            if (show) n++;
        });
        counter.textContent = n + ' / ' + cards.length + ' <?= _('user(s)') ?>';
    }

    search.addEventListener('input', refresh);
    refresh();
    search.focus();
}());
</script>
