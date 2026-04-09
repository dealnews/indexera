<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads a group and its member list for the management page.
 *
 * Returns not_found = true if the group does not exist or the current
 * user is not a member. Returns not_member = true for authenticated
 * users who aren't members, so the controller can redirect them to the
 * group home instead of showing a 404.
 *
 * Expects inputs to contain:
 *   - group_slug      string  The group slug from the route.
 *   - current_user_id int     The authenticated user's ID.
 *
 * @package Dealnews\Indexera\Model
 */
class GroupManageModel extends ModelAbstract {

    /**
     * The group slug from the route.
     *
     * @var string
     */
    public string $group_slug = '';

    /**
     * The authenticated user's ID.
     *
     * @var int
     */
    public int $current_user_id = 0;

    /**
     * Loads the group and its members.
     *
     * @return array
     */
    public function getData(): array {
        if ($this->group_slug === '' || $this->current_user_id === 0) {
            return ['not_found' => true];
        }

        $repository = new Repository();

        $groups = $repository->find('Group', ['slug' => $this->group_slug], limit: 1);
        $group  = !empty($groups) ? reset($groups) : null;

        if ($group === null) {
            return ['not_found' => true];
        }

        $member_records = $repository->find('GroupMember', [
            'group_id' => $group->group_id,
            'user_id'  => $this->current_user_id,
        ], limit: 1);

        if (empty($member_records)) {
            return ['not_found' => false, 'not_member' => true, 'group' => $group];
        }

        $all_members  = $repository->find('GroupMember', ['group_id' => $group->group_id]) ?? [];
        $members_data = [];

        foreach ($all_members as $member) {
            $user = $repository->get('User', (int)$member->user_id);
            if ($user === null) {
                continue;
            }
            $members_data[] = [
                'group_member_id' => $member->group_member_id,
                'user_id'         => $member->user_id,
                'display_name'    => $user->display_name,
                'email'           => $user->email,
            ];
        }

        return [
            'not_found'  => false,
            'not_member' => false,
            'group'      => $group,
            'members'    => $members_data,
        ];
    }
}
