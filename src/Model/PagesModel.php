<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use DealNews\DataMapper\Repository as BaseRepository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads a paginated list of all public pages for the directory.
 *
 * Respects the global public_pages setting: if public pages are disabled
 * and the visitor is a guest, returns login_required = true.
 *
 * Expects inputs to contain:
 *   - current_user_id  int  The authenticated user's ID, or 0 for guests.
 *   - page             int  The 1-based page number (default 1).
 *
 * @package Dealnews\Indexera\Model
 */
class PagesModel extends ModelAbstract {

    /**
     * Number of pages to display per page.
     */
    protected const PER_PAGE = 24;

    /**
     * The authenticated user's ID, or 0 for guests.
     *
     * @var int
     */
    public int $current_user_id = 0;

    /**
     * The 1-based page number.
     *
     * @var int
     */
    public int $page = 1;

    /**
     * Repository instance. Injected for testing; production code creates its own.
     *
     * @var BaseRepository|null
     */
    protected ?BaseRepository $repository = null;

    /**
     * Loads public pages with owner display names.
     *
     * @return array
     */
    public function getData(): array {
        $repository = $this->repository ?? Repository::init();

        $settings = $repository->get('Settings', 1);
        if ($settings !== null &&
            !$settings->public_pages &&
            $this->current_user_id === 0)
        {
            return ['login_required' => true];
        }

        $current_page = max(1, $this->page);
        $per_page     = self::PER_PAGE;
        $start        = ($current_page - 1) * $per_page;

        // Fetch one extra to detect whether a next page exists.
        $raw_pages = $repository->find(
            'Page',
            ['is_public' => true],
            limit: $per_page + 1,
            start: $start,
            order: 'title'
        ) ?? [];

        $has_next = count($raw_pages) > $per_page;
        if ($has_next) {
            array_pop($raw_pages);
        }

        // Build user and group caches to avoid N+1 queries.
        $user_cache  = [];
        $group_cache = [];
        $pages       = [];

        foreach ($raw_pages as $page) {
            $user_id  = (int)$page->user_id;
            $group_id = (int)$page->group_id;

            if (!isset($user_cache[$user_id])) {
                $user_cache[$user_id] = $repository->get('User', $user_id);
            }

            $owner = $user_cache[$user_id];
            $entry = $page->toArray();
            $entry['owner_name'] = $owner !== null ? $owner->display_name : '';
            $entry['group_slug'] = null;

            if ($group_id > 0) {
                if (!isset($group_cache[$group_id])) {
                    $group_cache[$group_id] = $repository->get('Group', $group_id);
                }
                $group               = $group_cache[$group_id];
                $entry['group_slug'] = $group !== null ? $group->slug : null;
            }

            $pages[] = $entry;
        }

        return [
            'login_required' => false,
            'pages'          => $pages,
            'current_page'   => $current_page,
            'has_prev'       => $current_page > 1,
            'has_next'       => $has_next,
        ];
    }
}
