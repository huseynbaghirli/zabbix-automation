<?php

declare(strict_types=1);

/**
 * @var array $data
 * @var array $data['users']
 * @var array $data['roles']
 * @var array $data['usergroups']
 * @var array $data['media_types']
 * @var array $data['hostgroups']
 * @var array $data['template_groups']
 */

// ── Static lookup tables ─────────────────────────────────────────────────────

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

// Avatar palette — deterministic colour from username hash
$palette = ['#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#ef4444','#22c55e','#6366f1'];

$roles      = $data['roles'] ?? [];
$hostgroups = $data['hostgroups'];
$users      = $data['users'];

// ── Summary counts ────────────────────────────────────────────────────────────
$total   = count($users);
$n_media = count(array_filter($users, fn($u) => !empty($u['medias'])));
$n_act   = count(array_filter($users, fn($u) => !empty($u['trigger_actions'])));
$n_full  = count(array_filter($users, fn($u) => !empty($u['medias']) && !empty($u['trigger_actions'])));

// ── Coverage bar segments ─────────────────────────────────────────────────────
$n_media_only  = count(array_filter($users, fn($u) => !empty($u['medias']) && empty($u['trigger_actions'])));
$n_action_only = count(array_filter($users, fn($u) => empty($u['medias'])  && !empty($u['trigger_actions'])));
$n_none        = $total - $n_full - $n_media_only - $n_action_only;

// Helper: percentage string for bar widths
$pct = fn(int $n) => $total > 0 ? round($n / $total * 100, 1) : 0;

// Helper: SVG ring dashoffset (r=20, circumference ≈ 125.66)
$circ    = 2 * M_PI * 20;
$dashoff = fn(int $n) => $total > 0 ? round($circ * (1 - $n / $total), 2) : $circ;

?>
<div class="uo-page">

    <!-- ── Page header ──────────────────────────────────────────────────── -->
    <div class="uo-header">
        <div>
            <div class="uo-title"><?= htmlspecialchars($data['title']) ?></div>
            <div class="uo-subtitle">
                Hər istifadəçi üçün alert konfiqurasiyasının tam mənzərəsi —
                qrup üzvlüyü, media &amp; severity, trigger actions, host qrup icazələri.
            </div>
        </div>
    </div>

    <!-- ── Stat cards ───────────────────────────────────────────────────── -->
    <div class="uo-stats-row">

        <?php
        $cards = [
            ['c-total',  _('Total Users'),          $total,   $total],
            ['c-media',  _('With Media'),            $n_media, $total],
            ['c-action', _('With Trigger Actions'),  $n_act,   $total],
            ['c-full',   _('Fully Configured'),      $n_full,  $total],
        ];
        foreach ($cards as [$cls, $label, $val, $of]):
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
                            stroke-dasharray="<?= round(2 * M_PI * 18, 2) ?>"
                            stroke-dashoffset="<?= round(2 * M_PI * 18 * (1 - $p / 100), 2) ?>"/>
                    </svg>
                    <div class="uo-ring-pct"><?= $p ?>%</div>
                </div>
            </div>
            <div class="uo-stat-label"><?= htmlspecialchars($label) ?></div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- ── Coverage stacked bar ─────────────────────────────────────────── -->
    <div class="uo-coverage">
        <div class="uo-coverage-title"><?= _('User Configuration Coverage') ?></div>
        <div class="uo-bar-track">
            <?php foreach ([
                ['uo-bar-full',   $n_full,        'Fully'],
                ['uo-bar-media',  $n_media_only,  'Media only'],
                ['uo-bar-action', $n_action_only, 'Action only'],
                ['uo-bar-none',   $n_none,        'None'],
            ] as [$seg_cls, $seg_n, $seg_lbl]):
                $w = $pct($seg_n);
                if ($w <= 0) continue;
            ?>
            <div class="uo-bar-seg <?= $seg_cls ?>" style="width:<?= $w ?>%"
                 title="<?= htmlspecialchars($seg_lbl) ?>: <?= $seg_n ?> (<?= $w ?>%)">
                <?php if ($w > 8): ?><?= $seg_n ?><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="uo-bar-legend">
            <?php foreach ([
                ['uo-bar-full',   _('Fully configured'),  $n_full],
                ['uo-bar-media',  _('Media only'),         $n_media_only],
                ['uo-bar-action', _('Action only'),        $n_action_only],
                ['uo-bar-none',   _('Not configured'),     $n_none],
            ] as [$dot_cls, $leg_lbl, $leg_n]): ?>
            <div class="uo-legend-item">
                <div class="uo-legend-dot <?= $dot_cls ?>"></div>
                <?= htmlspecialchars($leg_lbl) ?> &mdash; <strong><?= $leg_n ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Search ───────────────────────────────────────────────────────── -->
    <div class="uo-search-bar">
        <div class="uo-search-wrap">
            <span class="uo-search-icon">&#9906;</span>
            <input
                type="text"
                id="uo-search"
                class="uo-search-input"
                placeholder="<?= _('Filter by username, group, media, action…') ?>"
                autocomplete="off"
            >
        </div>
        <span class="uo-count-badge" id="uo-count"></span>
    </div>

    <!-- ── Table ────────────────────────────────────────────────────────── -->
    <div class="uo-table-wrap">
        <table class="uo-table" id="uo-table">
            <thead>
                <tr>
                    <th class="uo-col-user"><?= _('User') ?></th>
                    <th class="uo-col-groups"><?= _('User Groups') ?></th>
                    <th class="uo-col-media"><?= _('Media &amp; Severity') ?></th>
                    <th class="uo-col-actions"><?= _('Trigger Actions') ?></th>
                    <th class="uo-col-access"><?= _('Host Group Access') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="uo-no-data"><?= _('No users found.') ?></td></tr>
            <?php else: foreach ($users as $user):
                $fullname    = trim($user['name'] . ' ' . $user['surname']);
                $roleid      = $user['roleid'] ?? null;
                $role_type   = $roleid && isset($roles[$roleid]) ? (int) $roles[$roleid]['type'] : 1;
                $type_info   = $role_type_labels[$role_type] ?? $role_type_labels[1];
                $medias      = $user['medias'] ?? [];
                $actions     = $user['trigger_actions'];
                $hg_perms    = $user['host_group_permissions'];
                $has_media   = !empty($medias);
                $has_actions = !empty($actions);

                // Config score: 0, 1, or 2
                $score     = (int) $has_media + (int) $has_actions;
                $score_cls = match($score) { 2 => 's-full', 1 => 's-half', default => 's-none' };
                $score_pct = $score * 50;

                // Avatar
                $initials  = strtoupper(substr($user['username'], 0, 1));
                $av_color  = $palette[abs(crc32($user['username'])) % count($palette)];

                // Full text for client-side search
                $search_text = strtolower(implode(' ', array_filter([
                    $user['username'],
                    $fullname,
                    implode(' ', array_column($user['usrgrps'] ?? [], 'name')),
                    implode(' ', array_column($medias, 'media_type_name')),
                    implode(' ', array_map(fn($m) => $m['sendto'], $medias)),
                    implode(' ', array_column($actions, 'name')),
                    implode(' ', array_map(fn($id) => $hostgroups[$id]['name'] ?? '', array_keys($hg_perms))),
                ])));
            ?>
            <tr class="uo-row" data-search="<?= htmlspecialchars($search_text) ?>">

                <!-- ── User identity ── -->
                <td>
                    <div class="uo-user-cell">
                        <div class="uo-avatar" style="background:<?= $av_color ?>"><?= htmlspecialchars($initials) ?></div>
                        <div class="uo-user-info">
                            <div class="uo-user-name"><?= htmlspecialchars($user['username']) ?></div>
                            <?php if ($fullname !== ''): ?>
                            <div class="uo-user-full"><?= htmlspecialchars($fullname) ?></div>
                            <?php endif; ?>
                            <div class="uo-meta-row">
                                <span class="uo-type-badge <?= $type_info['class'] ?>"><?= htmlspecialchars($type_info['label']) ?></span>
                            </div>
                            <!-- Config score bar -->
                            <div class="uo-score">
                                <div class="uo-score-track">
                                    <div class="uo-score-fill <?= $score_cls ?>" style="width:<?= $score_pct ?>%"></div>
                                </div>
                                <div class="uo-score-dots">
                                    <div class="uo-score-dot <?= $has_media   ? 'on-media'  : '' ?>" title="<?= _('Media') ?>"></div>
                                    <div class="uo-score-dot <?= $has_actions ? 'on-action' : '' ?>" title="<?= _('Actions') ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- ── User groups ── -->
                <td>
                    <div class="uo-chip-list">
                    <?php if (empty($user['usrgrps'])): ?>
                        <span class="uo-empty">—</span>
                    <?php else: foreach ($user['usrgrps'] as $grp): ?>
                        <span class="uo-chip uo-chip-group" title="<?= htmlspecialchars($grp['name']) ?>"><?= htmlspecialchars($grp['name']) ?></span>
                    <?php endforeach; endif; ?>
                    </div>
                </td>

                <!-- ── Media & severity ── -->
                <td>
                    <?php if (empty($medias)): ?>
                        <span class="uo-empty"><?= _('No media configured') ?></span>
                    <?php else: foreach ($medias as $media):
                        $sev_mask  = (int) $media['severity'];
                        $is_active = ((int) $media['active'] === 0);
                    ?>
                    <div class="uo-media-block<?= $is_active ? '' : ' uo-media-disabled' ?>">
                        <div class="uo-media-header">
                            <span class="uo-media-type"><?= htmlspecialchars($media['media_type_name']) ?></span>
                            <?php if (!$is_active): ?>
                            <span class="uo-badge-off"><?= _('OFF') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="uo-media-dest" title="<?= htmlspecialchars($media['sendto']) ?>"><?= htmlspecialchars($media['sendto']) ?></div>
                        <div class="uo-sev-strip">
                            <?php foreach ($severities as $s):
                                $on = ($sev_mask >> $s['bit']) & 1;
                            ?>
                            <div class="uo-sev-cell <?= $on ? $s['class'] : 'sev-off' ?>" title="<?= htmlspecialchars($s['title']) ?>"><?= $s['label'] ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </td>

                <!-- ── Trigger actions ── -->
                <td>
                    <div class="uo-chip-list">
                    <?php if (empty($actions)): ?>
                        <span class="uo-empty"><?= _('Not in any action') ?></span>
                    <?php else: foreach ($actions as $act):
                        $enabled = ((int) $act['status'] === 0);
                    ?>
                        <span class="uo-chip <?= $enabled ? 'uo-chip-action' : 'uo-chip-action-off' ?>"
                              title="<?= htmlspecialchars($act['name']) . ($enabled ? '' : ' (' . _('disabled') . ')') ?>">
                            <span class="uo-act-dot"><?= $enabled ? '●' : '○' ?></span><?= htmlspecialchars($act['name']) ?>
                        </span>
                    <?php endforeach; endif; ?>
                    </div>
                </td>

                <!-- ── Host group access ── -->
                <td>
                    <div class="uo-chip-list">
                    <?php if (empty($hg_perms)): ?>
                        <span class="uo-empty"><?= _('No access defined') ?></span>
                    <?php else: foreach ($hg_perms as $gid => $perm):
                        $gname     = $hostgroups[$gid]['name'] ?? '(id:' . $gid . ')';
                        $perm_info = $perm_labels[$perm] ?? ['label' => '?', 'class' => 'perm-deny'];
                    ?>
                        <span class="uo-chip uo-chip-perm <?= $perm_info['class'] ?>"
                              title="<?= htmlspecialchars($gname) ?> — <?= htmlspecialchars($perm_info['label']) ?>">
                            <?= htmlspecialchars($gname) ?>&nbsp;<span class="uo-perm-level">[<?= $perm_info['label'] ?>]</span>
                        </span>
                    <?php endforeach; endif; ?>
                    </div>
                </td>

            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
(function () {
    'use strict';
    var search  = document.getElementById('uo-search');
    var rows    = document.querySelectorAll('#uo-table .uo-row');
    var counter = document.getElementById('uo-count');

    function refresh() {
        var q       = search.value.toLowerCase().trim();
        var visible = 0;
        rows.forEach(function (row) {
            var match = !q || row.dataset.search.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        counter.textContent = visible + ' / ' + rows.length + ' <?= _('user(s)') ?>';
    }

    search.addEventListener('input', refresh);
    refresh();
    search.focus();
}());
</script>
