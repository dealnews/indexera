<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Api\Action;

/**
 * Authenticated search action that constrains results to the session user.
 *
 * For objects with a direct user_id column the filter is injected into
 * the JSON query before the search runs. For Section and Link, the
 * caller is expected to provide a page_id or section_id filter; the
 * action verifies that the referenced parent is owned by the session
 * user before executing the query.
 *
 * For GroupMember, the caller must supply a group_id filter and the
 * session user must be a member of that group.
 *
 * @package Dealnews\Indexera\Api\Action
 */
class SearchObjects extends \DealNews\DataMapperAPI\Action\SearchObjects {

    use AuthTrait;
    use OwnershipTrait;

    /**
     * Injects ownership constraints then delegates to the parent search.
     *
     * @throws \LogicException When a required parent filter is missing or
     *                         the referenced parent is not owned by the
     *                         session user.
     *
     * @return array
     */
    public function loadData(): array {
        $query = json_decode($this->post_data, true);

        if (!is_array($query)) {
            $query = [];
        }

        $user_id = (int)$_SESSION['user_id'];

        switch ($this->object_name) {
            case 'Section':
                $page_id = (int)($query['filter']['page_id'] ?? 0);
                if ($page_id === 0 || !$this->isPageEditable($page_id, $user_id)) {
                    throw new \LogicException(
                        'A valid page_id filter owned by the current user is required'
                    );
                }
                break;

            case 'Link':
                $section_id = (int)($query['filter']['section_id'] ?? 0);
                if ($section_id === 0 || !$this->isSectionEditable($section_id, $user_id)) {
                    throw new \LogicException(
                        'A valid section_id filter owned by the current user is required'
                    );
                }
                break;

            case 'GroupMember':
                $group_id = (int)($query['filter']['group_id'] ?? 0);
                if ($group_id === 0 || !$this->isGroupMember($group_id, $user_id)) {
                    throw new \LogicException(
                        'A valid group_id filter for a group you belong to is required'
                    );
                }
                break;

            default:
                $query['filter']['user_id'] = $user_id;
                break;
        }

        $this->post_data = json_encode($query);

        return parent::loadData();
    }
}
