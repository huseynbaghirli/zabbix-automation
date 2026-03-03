<?php

declare(strict_types=1);

/**
 * @var array $data
 * @var array $data['users']
 * @var array $data['usergroups']
 * @var array $data['media_types']
 * @var array $data['hostgroups']
 * @var array $data['template_groups']
 */

// ── Static lookup tables ─────────────────────────────────────────────────────
$user_type_labels = [
    USER_TYPE_ZABBIX_USER  => ['label' => 'User',        'class' => 'utype-user'],
    USER_TYPE_ZABBIX_ADMIN => ['label' => 'Admin',       'class' => 'utype-admin'],
    USER_TYPE_SUPER_ADMIN  => ['label' => 'Super Admin', 'class' => 'utype-super'],
];

$perm_labels = [
    0 => ['label' => 'Denied', 'class' => 'perm-deny'],
    2 => ['label' => 'Read',   'class' => 'perm-read'],
    3 => ['label' => 'R/W',    'class' => 'perm-rw'],
];

// Severity bitmask positions match Zabbix trigger severity levels (0–5)
$severities = [
    ['bit' => 0, 'label' => 'NC',  'title' => 'Not classified', 'class' => 'sev-nc'],
    ['bit' => 1, 'label' => 'Inf', 'title' => 'Information',    'class' => 'sev-info'],
    ['bit' => 2, 'label' => 'W',   'title' => 'Warning',        'class' => 'sev-warn'],
    ['bit' => 3, 'label' => 'Avg', 'title' => 'Average',        'class' => 'sev-avg'],
    ['bit' => 4, 'label' => 'H',   'title' => 'High',           'class' => 'sev-high'],
    ['bit' => 5, 'label' => 'D',   'title' => 'Disaster',       'class' => 'sev-dis'],
];

$users      = $data['users'];
$hostgroups = $data['hostgroups'];

// ── Summary counts ────────────────────────────────────────────────────────────
$total_users        = count($users);
$users_with_media   = count(array_filter($users, fn($u) => !empty($u['medias'])));
$users_with_actions = count(array_filter($users, fn($u) => !empty($u['trigger_actions'])));
$fully_configured   = count(array_filter($users, fn($u) => !empty($u['medias']) && !empty($u['trigger_actions'])));

?>
<h1><?= htmlspecialchars($data['title']) ?></h1>

<div class="uo-page">

    <!-- ── Description ──────────────────────────────────────────────────── -->
    <div class="uo-description">
        Hər istifadəçi üçün alert konfiqurasiyasının tam mənzərəsi:
        qrup üzvlüyü, media növləri &amp; severity, trigger action-lar və host qrup icazələri.
        Axtarış qutusu ilə istənilən sahəyə görə filter etmək olar.
    </div>

    <!-- ── Summary cards ────────────────────────────────────────────────── -->
    <div class="uo-cards">
        <?php foreach ([
            [_('Total Users'),          $total_users],
            [_('With Media'),           $users_with_media],
            [_('With Trigger Actions'), $users_with_actions],
            [_('Fully Configured'),     $fully_configured],
        ] as [$label, $value]): ?>
        <div class="uo-card">
            <div class="uo-card-value"><?= (int) $value ?></div>
            <div class="uo-card-label"><?= htmlspecialchars($label) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Search / filter ──────────────────────────────────────────────── -->
    <div class="uo-search-bar">
        <input
            type="text"
            id="uo-search"
            class="uo-search-input"
            placeholder="<?= _('Filter by username, group, media type, action name…') ?>"
            autocomplete="off"
        >
        <span class="uo-count" id="uo-count"></span>
    </div>

    <!-- ── Main table ───────────────────────────────────────────────────── -->
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
                $fullname  = trim($user['name'] . ' ' . $user['surname']);
                $utype     = (int) $user['type'];
                $type_info = $user_type_labels[$utype] ?? ['label' => 'Unknown', 'class' => 'utype-user'];
                $medias    = $user['medias'] ?? [];
                $actions   = $user['trigger_actions'];
                $hg_perms  = $user['host_group_permissions'];

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

                <!-- User identity -->
                <td>
                    <div class="uo-user-name"><?= htmlspecialchars($user['username']) ?></div>
                    <?php if ($fullname !== ''): ?>
                    <div class="uo-user-full"><?= htmlspecialchars($fullname) ?></div>
                    <?php endif; ?>
                    <span class="uo-type-badge <?= $type_info['class'] ?>"><?= htmlspecialchars($type_info['label']) ?></span>
                </td>

                <!-- User groups -->
                <td>
                    <div class="uo-chip-list">
                    <?php if (empty($user['usrgrps'])): ?>
                        <span class="uo-empty">—</span>
                    <?php else: foreach ($user['usrgrps'] as $grp): ?>
                        <span class="uo-chip uo-chip-group" title="<?= htmlspecialchars($grp['name']) ?>"><?= htmlspecialchars($grp['name']) ?></span>
                    <?php endforeach; endif; ?>
                    </div>
                </td>

                <!-- Media & severity -->
                <td>
                    <?php if (empty($medias)): ?>
                        <span class="uo-empty"><?= _('No media configured') ?></span>
                    <?php else: foreach ($medias as $media):
                        $sev_mask  = (int) $media['severity'];
                        $is_active = ((int) $media['active'] === 0);
                    ?>
                    <div class="uo-media-row<?= $is_active ? '' : ' uo-media-disabled' ?>">
                        <div class="uo-media-header">
                            <span class="uo-media-type"><?= htmlspecialchars($media['media_type_name']) ?></span>
                            <?php if (!$is_active): ?>
                            <span class="uo-badge-off"><?= _('Disabled') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="uo-media-dest" title="<?= htmlspecialchars($media['sendto']) ?>"><?= htmlspecialchars($media['sendto']) ?></div>
                        <div class="uo-sev-row">
                            <?php foreach ($severities as $s):
                                $on = ($sev_mask >> $s['bit']) & 1;
                            ?>
                            <span class="uo-sev-badge <?= $on ? $s['class'] : 'sev-off' ?>" title="<?= htmlspecialchars($s['title']) ?>"><?= $s['label'] ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </td>

                <!-- Trigger actions -->
                <td>
                    <div class="uo-chip-list">
                    <?php if (empty($actions)): ?>
                        <span class="uo-empty"><?= _('Not in any action') ?></span>
                    <?php else: foreach ($actions as $act):
                        $enabled = ((int) $act['status'] === 0);
                    ?>
                        <span class="uo-chip <?= $enabled ? 'uo-chip-action' : 'uo-chip-action-off' ?>" title="<?= htmlspecialchars($act['name']) . ($enabled ? '' : ' (' . _('disabled') . ')') ?>"><span class="uo-act-dot"><?= $enabled ? '●' : '○' ?></span><?= htmlspecialchars($act['name']) ?></span>
                    <?php endforeach; endif; ?>
                    </div>
                </td>

                <!-- Host group access -->
                <td>
                    <div class="uo-chip-list">
                    <?php if (empty($hg_perms)): ?>
                        <span class="uo-empty"><?= _('No access defined') ?></span>
                    <?php else: foreach ($hg_perms as $gid => $perm):
                        $gname     = $hostgroups[$gid]['name'] ?? '(id:' . $gid . ')';
                        $perm_info = $perm_labels[$perm] ?? ['label' => '?', 'class' => 'perm-deny'];
                    ?>
                        <span class="uo-chip uo-chip-perm <?= $perm_info['class'] ?>" title="<?= htmlspecialchars($gname) ?> — <?= htmlspecialchars($perm_info['label']) ?>"><?= htmlspecialchars($gname) ?>&nbsp;<span class="uo-perm-level">[<?= $perm_info['label'] ?>]</span></span>
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

    function updateCounter(n) {
        counter.textContent = n + ' / ' + rows.length + ' <?= _('user(s)') ?>';
    }

    search.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var visible = 0;
        rows.forEach(function (row) {
            var match = !q || row.dataset.search.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        updateCounter(visible);
    });

    updateCounter(rows.length);
    search.focus();
})();
</script>
