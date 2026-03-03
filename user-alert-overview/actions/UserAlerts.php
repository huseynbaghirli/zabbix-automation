<?php

declare(strict_types=1);

namespace Modules\UserAlertOverview\Actions;

use API;
use CController;
use CControllerResponseData;

class UserAlerts extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
    }

    protected function doAction(): void {
        // ── Media types ──────────────────────────────────────────────────────
        $mt_raw      = API::MediaType()->get(['output' => ['mediatypeid', 'name', 'type']]);
        $media_types = array_column($mt_raw, null, 'mediatypeid');

        // ── Host groups ──────────────────────────────────────────────────────
        $hg_raw     = API::HostGroup()->get(['output' => ['groupid', 'name']]);
        $hostgroups = array_column($hg_raw, null, 'groupid');

        // ── Template groups (Zabbix 6.2+) ────────────────────────────────────
        $template_groups = [];
        try {
            $tg_api = API::TemplateGroup();
            if ($tg_api !== null) {
                $tg_raw          = $tg_api->get(['output' => ['groupid', 'name']]);
                $template_groups = array_column($tg_raw, null, 'groupid');
            }
        } catch (\Throwable $e) {
            // Not available in older versions — ignore.
        }

        // ── User groups with host-group access rights ─────────────────────────
        $ug_raw     = API::UserGroup()->get([
            'output'       => ['usrgrpid', 'name', 'gui_access', 'users_status'],
            'selectRights' => 'extend',
        ]);
        $usergroups = array_column($ug_raw, null, 'usrgrpid');

        // ── Trigger actions with send-message operations ─────────────────────
        $actions_raw = API::Action()->get([
            'output'           => ['actionid', 'name', 'status'],
            'selectOperations' => 'extend',
            'filter'           => ['eventsource' => EVENT_SOURCE_TRIGGERS],
        ]);

        // Map:  userid   → [actionid => action]
        //       usrgrpid → [actionid => action]
        $direct_act = [];
        $group_act  = [];
        foreach ($actions_raw as $act) {
            foreach ((array) ($act['operations'] ?? []) as $op) {
                if ((int) $op['operationtype'] !== OPERATION_TYPE_MESSAGE) {
                    continue;
                }
                foreach ((array) ($op['opmessage_usr'] ?? []) as $ou) {
                    $direct_act[$ou['userid']][$act['actionid']] = $act;
                }
                foreach ((array) ($op['opmessage_grp'] ?? []) as $og) {
                    $group_act[$og['usrgrpid']][$act['actionid']] = $act;
                }
            }
        }

        // ── Users ─────────────────────────────────────────────────────────────
        $users = API::User()->get([
            'output'        => ['userid', 'username', 'name', 'surname', 'type'],
            'selectMedias'  => ['mediaid', 'mediatypeid', 'sendto', 'active', 'severity', 'period'],
            'selectUsrgrps' => ['usrgrpid', 'name'],
            'sortfield'     => 'username',
            'sortorder'     => 'ASC',
        ]);

        foreach ($users as &$user) {
            $uid  = $user['userid'];
            $gids = array_column($user['usrgrps'] ?? [], 'usrgrpid');

            // Merge direct + group trigger actions; enabled first
            $all_acts = $direct_act[$uid] ?? [];
            foreach ($gids as $gid) {
                foreach ($group_act[$gid] ?? [] as $aid => $act) {
                    $all_acts[$aid] = $act;
                }
            }
            usort($all_acts, fn($a, $b) => (int)$a['status'] - (int)$b['status']);
            $user['trigger_actions'] = $all_acts;

            // Host-group permissions: aggregate max permission across all user groups
            $hg_perms = [];
            foreach ($gids as $gid) {
                foreach ($usergroups[$gid]['rights'] ?? [] as $right) {
                    $id   = $right['id'];
                    $perm = (int) $right['permission'];
                    if (!isset($hg_perms[$id]) || $perm > $hg_perms[$id]) {
                        $hg_perms[$id] = $perm;
                    }
                }
            }
            arsort($hg_perms); // R/W first, then Read, then Denied
            $user['host_group_permissions'] = $hg_perms;

            // Enrich media with type name; normalise sendto to string
            foreach ($user['medias'] ?? [] as &$media) {
                $mtid                     = $media['mediatypeid'];
                $media['media_type_name'] = $media_types[$mtid]['name'] ?? '(unknown)';
                if (is_array($media['sendto'])) {
                    $media['sendto'] = implode(', ', $media['sendto']);
                }
            }
            unset($media);
        }
        unset($user);

        $this->setResponse(new CControllerResponseData([
            'title'           => _('User Alert Overview'),
            'users'           => $users,
            'usergroups'      => $usergroups,
            'media_types'     => $media_types,
            'hostgroups'      => $hostgroups,
            'template_groups' => $template_groups,
            'user'            => ['debug_mode' => $this->getDebugMode()],
        ]));
    }
}
