<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\Link;
use DealNews\DB\AbstractMapper;

/**
 * Maps Link objects to the links table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class LinkMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_links';

    public const PRIMARY_KEY = 'link_id';

    public const MAPPED_CLASS = Link::class;

    public const MAPPING = [
        'link_id'    => [],
        'section_id' => [],
        'label'      => [],
        'url'        => [],
        'sort_order' => [],
        'created_at' => ['read_only' => true],
        'updated_at' => ['read_only' => true],
    ];
}
