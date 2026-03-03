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

        // ── 1. Media types ────────────────────────────────────────────────────
        $mt_raw      = API::MediaType()->get(['output' => ['mediatypeid', 'name']]) ?: [];
        $media_types = array_column($mt_raw, null, 'mediatypeid');

        // ── 2. Roles (Zabbix 6.2+) ────────────────────────────────────────────
        $roles = [];
        try {
            $roles = array_column(
                API::Role()->get(['output' => ['roleid', 'name', 'type']]) ?: [],
                null, 'roleid'
            );
        } catch (\Throwable $e) {}

        // ── 3. User groups — with member user IDs (to resolve group recipients) ─
        $ug_raw     = API::UserGroup()->get([
            'output'      => ['usrgrpid', 'name'],
            'selectUsers' => ['userid'],
        ]) ?: [];
        $usergroups = array_column($ug_raw, null, 'usrgrpid');

        // ── 4. All users — with their media ───────────────────────────────────
        $users_raw = API::User()->get([
            'output'       => ['userid', 'username', 'name', 'surname', 'roleid'],
            'selectMedias' => 'extend',
        ]) ?: [];

        // Index by userid; enrich media with type name
        $users = [];
        foreach ($users_raw as $u) {
            foreach ($u['medias'] as &$m) {
                $m['media_type_name'] = $media_types[$m['mediatypeid']]['name'] ?? '?';
                if (is_array($m['sendto'])) {
                    $m['sendto'] = implode(', ', $m['sendto']);
                }
            }
            unset($m);
            $users[$u['userid']] = $u;
        }

        // ── 5. Trigger actions with send-message operations ───────────────────
        $actions_raw = API::Action()->get([
            'output'           => ['actionid', 'name', 'status'],
            'selectOperations' => 'extend',
            'filter'           => ['eventsource' => EVENT_SOURCE_TRIGGERS],
        ]) ?: [];

        // ── 6. Build action-centric structure ─────────────────────────────────
        $actions = [];

        foreach ($actions_raw as $act) {
            $ops = [];

            foreach ((array) ($act['operations'] ?? []) as $op) {
                // Only "send message" operations
                if ((int) $op['operationtype'] !== OPERATION_TYPE_MESSAGE) {
                    continue;
                }

                $mediatypeid  = (int) ($op['opmessage']['mediatypeid'] ?? 0);
                $mt_label     = $mediatypeid === 0
                    ? _('All media types')
                    : ($media_types[$mediatypeid]['name'] ?? '?');

                // Resolve recipients — direct users first
                $recipients = [];

                foreach ((array) ($op['opmessage_usr'] ?? []) as $ou) {
                    $uid = $ou['userid'];
                    if (!isset($users[$uid])) continue;
                    $recipients[$uid] = [
                        'user' => $users[$uid],
                        'via'  => 'direct',
                    ];
                }

                // Then users resolved through groups
                foreach ((array) ($op['opmessage_grp'] ?? []) as $og) {
                    $grpid    = $og['usrgrpid'];
                    $grp_name = $usergroups[$grpid]['name'] ?? '?';
                    foreach ((array) ($usergroups[$grpid]['users'] ?? []) as $gu) {
                        $uid = $gu['userid'];
                        if (!isset($users[$uid]) || isset($recipients[$uid])) continue;
                        $recipients[$uid] = [
                            'user' => $users[$uid],
                            'via'  => $grp_name,   // name of the group
                        ];
                    }
                }

                // Filter media by the operation's mediatypeid (0 = all)
                foreach ($recipients as &$r) {
                    $all = $r['user']['medias'] ?? [];
                    $r['medias'] = $mediatypeid === 0
                        ? $all
                        : array_values(array_filter($all, fn($m) => (int) $m['mediatypeid'] === $mediatypeid));
                }
                unset($r);

                if (empty($recipients)) continue;

                $ops[] = [
                    'mediatypeid'  => $mediatypeid,
                    'mt_label'     => $mt_label,
                    'recipients'   => array_values($recipients),
                ];
            }

            if (empty($ops)) continue;

            $actions[] = [
                'actionid'   => $act['actionid'],
                'name'       => $act['name'],
                'status'     => (int) $act['status'],   // 0=enabled 1=disabled
                'operations' => $ops,
            ];
        }

        // Enabled actions first
        usort($actions, fn($a, $b) => $a['status'] - $b['status']);

        $this->setResponse(new CControllerResponseData([
            'title'   => _('User Alert Overview'),
            'actions' => $actions,
            'roles'   => $roles,
            'user'    => ['debug_mode' => $this->getDebugMode()],
        ]));
    }
}
