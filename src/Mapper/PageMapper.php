<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\Page;
use DealNews\DB\AbstractMapper;

/**
 * Maps Page objects to the pages table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class PageMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_pages';

    public const PRIMARY_KEY = 'page_id';

    public const MAPPED_CLASS = Page::class;

    public const MAPPING = [
        'page_id'     => [],
        'user_id'     => [],
        'group_id'    => [],
        'slug'        => [],
        'title'       => [],
        'description' => [],
        'is_public'   => [],
        'created_at'  => ['read_only' => true],
        'updated_at'  => ['read_only' => true],
    ];
}
