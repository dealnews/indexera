<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads the pages belonging to, edited by, subscribed to, or accessible
 * via group membership by the current user.
 *
 * Owned, editor, subscribed, and group pages are merged and sorted by
 * title. Each entry includes is_owned, is_editor, is_group_member,
 * subscription_id, owner_display_name, and group_slug so the view can
 * render them differently and build correct URLs.
 *
 * Expects inputs to contain:
 *   - user_id  int  The authenticated user's ID.
 *
 * @package Dealnews\Indexera\Model
 */
class UserPagesModel extends ModelAbstract {

    /**
     * The authenticated user's ID.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * Returns the user's pages sorted by title.
     *
     * @return array
     */
    public function getData(): array {
        if ($this->user_id === 0) {
            return ['pages' => [], 'groups' => []];
        }

        $repository    = new Repository();
        $pages         = [];
        $seen_page_ids = [];

        $owner = $repository->get('User', $this->user_id);

        // Personal owned pages (no group).
        $owned = $repository->find('Page', ['user_id' => $this->user_id]) ?? [];

        foreach ($owned as $page) {
            if ((int)$page->group_id > 0) {
                continue;
            }

            $entry                       = $page->toArray();
            $entry['is_owned']           = true;
            $entry['is_editor']          = false;
            $entry['is_group_member']    = false;
            $entry['subscription_id']    = null;
            $entry['owner_display_name'] = $owner->display_name;
            $entry['group_slug']         = null;
            $pages[]                     = $entry;
            $seen_page_ids[$page->page_id] = true;
        }

        // Pages where user is an editor (personal pages only).
        $editor_records = $repository->find(
            'PageEditor',
            ['user_id' => $this->user_id]
        ) ?? [];

        foreach ($editor_records as $editor_record) {
            $page_id = (int)$editor_record->page_id;

            if (isset($seen_page_ids[$page_id])) {
                continue;
            }

            $page = $repository->get('Page', $page_id);

            if ($page === null || (int)$page->group_id > 0) {
                continue;
            }

            $page_owner                  = $repository->get('User', $page->user_id);
            $entry                       = $page->toArray();
            $entry['is_owned']           = false;
            $entry['is_editor']          = true;
            $entry['is_group_member']    = false;
            $entry['subscription_id']    = null;
            $entry['owner_display_name'] = $page_owner !== null ? $page_owner->display_name : '';
            $entry['group_slug']         = null;
            $pages[]                     = $entry;
            $seen_page_ids[$page_id]     = true;
        }

        // Pages the user is subscribed to.
        $subscriptions = $repository->find(
            'PageSubscription',
            ['user_id' => $this->user_id]
        ) ?? [];

        foreach ($subscriptions as $subscription) {
            $page_id = (int)$subscription->page_id;

            if (isset($seen_page_ids[$page_id])) {
                continue;
            }

            $page = $repository->get('Page', $page_id);

            if ($page === null) {
                continue;
            }

            // Personal subscribed pages must be public.
            if ((int)$page->group_id === 0 && !$page->is_public) {
                continue;
            }

            $group_slug = null;

            if ((int)$page->group_id > 0) {
                $group      = $repository->get('Group', (int)$page->group_id);
                $group_slug = $group !== null ? $group->slug : null;
            }

            $sub_owner                   = $repository->get('User', $page->user_id);
            $entry                       = $page->toArray();
            $entry['is_owned']           = false;
            $entry['is_editor']          = false;
            $entry['is_group_member']    = false;
            $entry['subscription_id']    = $subscription->page_subscription_id;
            $entry['owner_display_name'] = $sub_owner !== null ? $sub_owner->display_name : '';
            $entry['group_slug']         = $group_slug;
            $pages[]                     = $entry;
            $seen_page_ids[$page_id]     = true;
        }

        // Pages from groups the user belongs to.
        $memberships = $repository->find(
            'GroupMember',
            ['user_id' => $this->user_id]
        ) ?? [];

        $group_cache = [];

        foreach ($memberships as $membership) {
            $group_id = (int)$membership->group_id;

            if (!isset($group_cache[$group_id])) {
                $group_cache[$group_id] = $repository->get('Group', $group_id);
            }

            $group      = $group_cache[$group_id];
            $group_slug = $group !== null ? $group->slug : null;

            $group_pages = $repository->find(
                'Page',
                ['group_id' => $group_id]
            ) ?? [];

            foreach ($group_pages as $page) {
                $page_id = (int)$page->page_id;

                if (isset($seen_page_ids[$page_id])) {
                    continue;
                }

                $is_owned                    = (int)$page->user_id === $this->user_id;
                $page_owner                  = $repository->get('User', (int)$page->user_id);
                $entry                       = $page->toArray();
                $entry['is_owned']           = $is_owned;
                $entry['is_editor']          = false;
                $entry['is_group_member']    = true;
                $entry['subscription_id']    = null;
                $entry['owner_display_name'] = $page_owner !== null ? $page_owner->display_name : '';
                $entry['group_slug']         = $group_slug;
                $entry['group_name']         = $group !== null ? $group->name : '';
                $pages[]                     = $entry;
                $seen_page_ids[$page_id]     = true;
            }
        }

        usort($pages, fn($a, $b) => strnatcasecmp($a['title'], $b['title']));

        // Build a list of groups the user belongs to for the create form.
        $groups      = [];
        $group_cache = [];

        foreach ($memberships as $membership) {
            $group_id = (int)$membership->group_id;

            if (!isset($group_cache[$group_id])) {
                $group_cache[$group_id] = $repository->get('Group', $group_id);
            }

            if ($group_cache[$group_id] !== null) {
                $groups[] = [
                    'group_id' => $group_id,
                    'name'     => $group_cache[$group_id]->name,
                    'slug'     => $group_cache[$group_id]->slug,
                ];
            }
        }

        usort($groups, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return ['pages' => $pages, 'groups' => $groups];
    }
}
