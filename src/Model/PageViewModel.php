<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads a public page and its sections and links for display.
 *
 * Handles both personal pages (/{username}/{slug}) and group pages
 * (/{group_slug}/{slug}). Group slug lookup takes precedence over
 * username lookup to support the unified route.
 *
 * Also checks whether the current user is subscribed to the page.
 *
 * Expects inputs to contain:
 *   - username        string  The first URL segment (group slug or username).
 *   - slug            string  The page slug.
 *   - current_user_id int     The authenticated user's ID, or 0 for guests.
 *
 * @package Dealnews\Indexera\Model
 */
class PageViewModel extends ModelAbstract {

    /**
     * First URL segment — a group slug or a user display name.
     *
     * @var string
     */
    public string $username = '';

    /**
     * Page slug from the route.
     *
     * @var string
     */
    public string $slug = '';

    /**
     * The authenticated user's ID, or 0 for guests.
     *
     * @var int
     */
    public int $current_user_id = 0;

    /**
     * Loads the page, its owner or group, sections, links, and subscription status.
     *
     * @return array
     */
    public function getData(): array {
        $repository = new Repository();

        $settings = $repository->get('Settings', 1);
        if ($settings !== null &&
            !$settings->public_pages &&
            $this->current_user_id === 0)
        {
            return [
                'login_required' => true,
                'next_url'       => '/' . $this->username . '/' . $this->slug,
            ];
        }

        // Try group slug first.
        $groups = $repository->find('Group', ['slug' => $this->username], limit: 1);
        $group  = !empty($groups) ? reset($groups) : null;

        if ($group !== null) {
            return $this->loadGroupPage($repository, $group, $this->slug);
        }

        // Fall back to user display name.
        return $this->loadUserPage($repository, $this->username, $this->slug);
    }

    /**
     * Loads a page that belongs to a group.
     *
     * @param Repository $repository The data repository.
     * @param object     $group      The matched group.
     * @param string     $slug       The page slug.
     *
     * @return array
     */
    protected function loadGroupPage(
        Repository $repository,
        object $group,
        string $slug
    ): array {
        $pages = $repository->find('Page', [
            'group_id' => $group->group_id,
            'slug'     => $slug,
        ], limit: 1);
        $page = !empty($pages) ? reset($pages) : null;

        if ($page === null) {
            return ['not_found' => true];
        }

        $is_member = $this->current_user_id !== 0 &&
                     !empty($repository->find('GroupMember', [
                         'group_id' => $group->group_id,
                         'user_id'  => $this->current_user_id,
                     ], limit: 1));

        // Group-only pages require membership.
        if (!$page->is_public && !$is_member) {
            if ($this->current_user_id === 0) {
                return [
                    'login_required' => true,
                    'next_url'       => '/' . $group->slug . '/' . $slug,
                ];
            }

            return ['not_found' => true];
        }

        $sections_with_links = $this->loadSections($repository, (int)$page->page_id);

        $subscription_id = null;
        $is_editor       = $is_member;

        if ($this->current_user_id !== 0 &&
            $this->current_user_id !== $page->user_id)
        {
            $subs = $repository->find('PageSubscription', [
                'user_id' => $this->current_user_id,
                'page_id' => $page->page_id,
            ], limit: 1);

            if (!empty($subs)) {
                $sub             = reset($subs);
                $subscription_id = $sub->page_subscription_id;
            }
        }

        $page_owner = $repository->get('User', (int)$page->user_id);

        return [
            'not_found'       => false,
            'page'            => $page,
            'page_owner'      => $page_owner,
            'group'           => $group,
            'is_group_page'   => true,
            'is_member'       => $is_member,
            'sections'        => $sections_with_links,
            'subscription_id' => $subscription_id,
            'is_editor'       => $is_editor,
        ];
    }

    /**
     * Loads a personal page owned by a user identified by display name.
     *
     * @param Repository $repository The data repository.
     * @param string     $username   The user's display name.
     * @param string     $slug       The page slug.
     *
     * @return array
     */
    protected function loadUserPage(
        Repository $repository,
        string $username,
        string $slug
    ): array {
        $users = $repository->find('User', ['display_name' => $username], limit: 1);
        $owner = !empty($users) ? reset($users) : null;

        if ($owner === null) {
            return ['not_found' => true];
        }

        $pages = $repository->find('Page', [
            'user_id' => $owner->user_id,
            'slug'    => $slug,
        ], limit: 1);
        $page = !empty($pages) ? reset($pages) : null;

        if ($page === null) {
            return ['not_found' => true];
        }

        if (!$page->is_public && $page->user_id !== $this->current_user_id) {
            $editor_records = $repository->find('PageEditor', [
                'page_id' => $page->page_id,
                'user_id' => $this->current_user_id,
            ], limit: 1);

            if (empty($editor_records)) {
                return ['not_found' => true];
            }
        }

        $sections_with_links = $this->loadSections($repository, (int)$page->page_id);

        $subscription_id = null;
        $is_editor       = false;

        if ($this->current_user_id !== 0 &&
            $this->current_user_id !== $page->user_id)
        {
            $subs = $repository->find('PageSubscription', [
                'user_id' => $this->current_user_id,
                'page_id' => $page->page_id,
            ], limit: 1);

            if (!empty($subs)) {
                $sub             = reset($subs);
                $subscription_id = $sub->page_subscription_id;
            }

            $editor_records = $repository->find('PageEditor', [
                'page_id' => $page->page_id,
                'user_id' => $this->current_user_id,
            ], limit: 1);

            $is_editor = !empty($editor_records);
        }

        return [
            'not_found'       => false,
            'page'            => $page,
            'page_owner'      => $owner,
            'group'           => null,
            'is_group_page'   => false,
            'is_member'       => false,
            'sections'        => $sections_with_links,
            'subscription_id' => $subscription_id,
            'is_editor'       => $is_editor,
        ];
    }

    /**
     * Loads sections and their links for a given page.
     *
     * @param Repository $repository The data repository.
     * @param int        $page_id    The page primary key.
     *
     * @return array
     */
    protected function loadSections(Repository $repository, int $page_id): array {
        $sections            = $repository->find(
            'Section',
            ['page_id' => $page_id],
            order: 'sort_order'
        ) ?? [];
        $sections_with_links = [];

        foreach ($sections as $section) {
            $links = $repository->find(
                'Link',
                ['section_id' => $section->section_id],
                order: 'sort_order'
            ) ?? [];

            $sections_with_links[] = [
                'section' => $section,
                'links'   => $links,
            ];
        }

        return $sections_with_links;
    }
}
