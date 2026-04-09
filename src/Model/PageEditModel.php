<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads a page for editing, along with its sections, links, and editors.
 *
 * Grants access to the page owner, any registered editor, and (for
 * group pages) any group member. Returns not_found = true if the page
 * does not exist or the current user lacks edit access.
 *
 * Expects inputs to contain:
 *   - page_id         int  The page to load.
 *   - current_user_id int  The authenticated user's ID.
 *
 * @package Dealnews\Indexera\Model
 */
class PageEditModel extends ModelAbstract {

    /**
     * The page ID from the route.
     *
     * @var int
     */
    public int $page_id = 0;

    /**
     * The authenticated user's ID.
     *
     * @var int
     */
    public int $current_user_id = 0;

    /**
     * Loads the page, its sections, links, and editor list.
     *
     * @return array
     */
    public function getData(): array {
        if ($this->page_id === 0 || $this->current_user_id === 0) {
            return ['not_found' => true];
        }

        $repository = new Repository();
        $page       = $repository->get('Page', $this->page_id);

        if ($page === null) {
            return ['not_found' => true];
        }

        $is_owner     = (int)$page->user_id === $this->current_user_id;
        $can_edit     = $is_owner;

        if (!$can_edit && (int)$page->group_id > 0) {
            $member_records = $repository->find('GroupMember', [
                'group_id' => $page->group_id,
                'user_id'  => $this->current_user_id,
            ], limit: 1);
            $can_edit       = !empty($member_records);
        }

        if (!$can_edit) {
            $editor_records = $repository->find('PageEditor', [
                'page_id' => $page->page_id,
                'user_id' => $this->current_user_id,
            ], limit: 1);
            $can_edit       = !empty($editor_records);
        }

        if (!$can_edit) {
            return ['not_found' => true];
        }

        $sections      = $repository->find(
            'Section',
            ['page_id' => $page->page_id],
            order: 'sort_order'
        ) ?? [];
        $sections_data = [];

        foreach ($sections as $section) {
            $links               = $repository->find(
                'Link',
                ['section_id' => $section->section_id],
                order: 'sort_order'
            ) ?? [];
            $section_data          = $section->toArray();
            $section_data['links'] = array_map(
                fn($l) => $l->toArray(),
                array_values($links)
            );
            $sections_data[]       = $section_data;
        }

        $editors      = $repository->find(
            'PageEditor',
            ['page_id' => $page->page_id]
        ) ?? [];
        $editors_data = [];

        foreach ($editors as $editor) {
            $editor_user = $repository->get('User', $editor->user_id);
            if ($editor_user === null) {
                continue;
            }
            $editors_data[] = [
                'page_editor_id' => $editor->page_editor_id,
                'user_id'        => $editor->user_id,
                'display_name'   => $editor_user->display_name,
                'email'          => $editor_user->email,
            ];
        }

        return [
            'not_found' => false,
            'page'      => $page,
            'sections'  => $sections_data,
            'editors'   => $editors_data,
            'is_owner'  => $is_owner,
        ];
    }
}
