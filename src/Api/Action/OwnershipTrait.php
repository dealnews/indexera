<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Api\Action;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Link;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\PageEditor;
use Dealnews\Indexera\Data\Section;

/**
 * Provides ownership and editor-access verification helpers for API actions.
 *
 * Requires $this->repository to be set by Action\Base::__invoke()
 * before any method here is called.
 *
 * Edit access is granted to the page owner and any user with a
 * PageEditor record for the page. Both are treated equally for all
 * CRUD operations on pages, sections, links, and page editors.
 *
 * For group pages, any group member has edit access. For Group and
 * GroupMember objects, any member of the group has access.
 *
 * @package Dealnews\Indexera\Api\Action
 */
trait OwnershipTrait {

    /**
     * Returns whether the session user may read or modify the given object.
     *
     * - Page (group): any group member.
     * - Page (personal): owner or editor.
     * - Section, PageEditor: owner or editor of the parent page.
     * - Link: owner or editor of the grandparent page.
     * - Group: any group member (or any authenticated user for GET).
     * - GroupMember: any member of the same group.
     * - All other objects (User, PageSubscription): direct user_id match.
     *
     * @param object $object The object to check.
     *
     * @return bool
     */
    protected function isOwned(object $object): bool {
        $user_id = (int)$_SESSION['user_id'];

        if ($object instanceof Page) {
            if ((int)$object->group_id > 0) {
                return $this->isGroupMember((int)$object->group_id, $user_id);
            }

            return (int)$object->user_id === $user_id ||
                   $this->isPageEditor((int)$object->page_id, $user_id);
        }

        if ($object instanceof Section || $object instanceof PageEditor) {
            return $this->isPageEditable((int)$object->page_id, $user_id);
        }

        if ($object instanceof Link) {
            return $this->isSectionEditable((int)$object->section_id, $user_id);
        }

        if ($object instanceof Group) {
            return $this->isGroupMember((int)$object->group_id, $user_id);
        }

        if ($object instanceof GroupMember) {
            return $this->isGroupMember((int)$object->group_id, $user_id);
        }

        if (property_exists($object, 'user_id')) {
            return (int)$object->user_id === $user_id;
        }

        return false;
    }

    /**
     * Returns whether the session user may edit the given page.
     *
     * True for the page owner, any registered editor, and (for group
     * pages) any group member.
     *
     * @param int $page_id The page primary key.
     * @param int $user_id The user to check.
     *
     * @return bool
     */
    protected function isPageEditable(int $page_id, int $user_id): bool {
        $page = $this->repository->get('Page', $page_id);

        if ($page === null) {
            return false;
        }

        if ((int)$page->group_id > 0) {
            return $this->isGroupMember((int)$page->group_id, $user_id);
        }

        return (int)$page->user_id === $user_id ||
               $this->isPageEditor($page_id, $user_id);
    }

    /**
     * Returns whether the given user is a registered editor of the page.
     *
     * @param int $page_id The page primary key.
     * @param int $user_id The user to check.
     *
     * @return bool
     */
    protected function isPageEditor(int $page_id, int $user_id): bool {
        $editors = $this->repository->find('PageEditor', [
            'page_id' => $page_id,
            'user_id' => $user_id,
        ], limit: 1);

        return !empty($editors);
    }

    /**
     * Returns whether the session user may edit the section's parent page.
     *
     * @param int $section_id The section primary key.
     * @param int $user_id    The user to check.
     *
     * @return bool
     */
    protected function isSectionEditable(int $section_id, int $user_id): bool {
        $section = $this->repository->get('Section', $section_id);

        return $section !== null &&
               $this->isPageEditable((int)$section->page_id, $user_id);
    }

    /**
     * Returns whether the given user is a member of the specified group.
     *
     * @param int $group_id The group primary key.
     * @param int $user_id  The user to check.
     *
     * @return bool
     */
    protected function isGroupMember(int $group_id, int $user_id): bool {
        $members = $this->repository->find('GroupMember', [
            'group_id' => $group_id,
            'user_id'  => $user_id,
        ], limit: 1);

        return !empty($members);
    }
}
