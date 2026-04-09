<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads a paginated list of all groups for the group directory.
 *
 * @package Dealnews\Indexera\Model
 */
class GroupsModel extends ModelAbstract {

    /**
     * Number of groups to display per page.
     */
    protected const PER_PAGE = 24;

    /**
     * The 1-based page number.
     *
     * @var int
     */
    public int $page = 1;

    /**
     * Loads groups with member counts.
     *
     * @return array
     */
    public function getData(): array {
        $repository   = Repository::init();
        $current_page = max(1, $this->page);
        $per_page     = self::PER_PAGE;
        $start        = ($current_page - 1) * $per_page;

        $raw_groups = $repository->find(
            'Group',
            [],
            limit: $per_page + 1,
            start: $start,
            order: 'name'
        ) ?? [];

        $has_next = count($raw_groups) > $per_page;
        if ($has_next) {
            array_pop($raw_groups);
        }

        $groups = [];

        foreach ($raw_groups as $group) {
            $member_count = count(
                $repository->find('GroupMember', ['group_id' => $group->group_id]) ?? []
            );
            $entry                 = $group->toArray();
            $entry['member_count'] = $member_count;
            $groups[]              = $entry;
        }

        return [
            'groups'       => $groups,
            'current_page' => $current_page,
            'has_prev'     => $current_page > 1,
            'has_next'     => $has_next,
        ];
    }
}
