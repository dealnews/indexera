<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads a group and its pages for the group home page.
 *
 * Members see all pages; non-members see only public pages.
 *
 * Expects inputs to contain:
 *   - group_slug      string  The group slug from the route.
 *   - current_user_id int     The authenticated user's ID, or 0 for guests.
 *
 * @package Dealnews\Indexera\Model
 */
class GroupViewModel extends ModelAbstract {

    /**
     * The group slug from the route.
     *
     * @var string
     */
    public string $group_slug = '';

    /**
     * The authenticated user's ID, or 0 for guests.
     *
     * @var int
     */
    public int $current_user_id = 0;

    /**
     * Loads the group, its membership status, and its pages.
     *
     * @return array
     */
    public function getData(): array {
        if ($this->group_slug === '') {
            return ['not_found' => true];
        }

        $repository = new Repository();

        $groups = $repository->find('Group', ['slug' => $this->group_slug], limit: 1);
        $group  = !empty($groups) ? reset($groups) : null;

        if ($group === null) {
            return ['not_found' => true];
        }

        $is_member = false;

        if ($this->current_user_id !== 0) {
            $member_records = $repository->find('GroupMember', [
                'group_id' => $group->group_id,
                'user_id'  => $this->current_user_id,
            ], limit: 1);
            $is_member      = !empty($member_records);
        }

        $page_criteria = ['group_id' => $group->group_id];
        if (!$is_member) {
            $page_criteria['is_public'] = true;
        }

        $raw_pages = $repository->find('Page', $page_criteria, order: 'title') ?? [];
        $pages     = [];

        foreach ($raw_pages as $page) {
            $page_owner = $repository->get('User', (int)$page->user_id);
            $entry      = $page->toArray();
            $entry['owner_display_name'] = $page_owner !== null
                ? $page_owner->display_name
                : '';
            $pages[] = $entry;
        }

        return [
            'not_found' => false,
            'group'     => $group,
            'is_member' => $is_member,
            'pages'     => $pages,
        ];
    }
}
