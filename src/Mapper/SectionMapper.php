<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\Section;
use DealNews\DB\AbstractMapper;

/**
 * Maps Section objects to the sections table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class SectionMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_sections';

    public const PRIMARY_KEY = 'section_id';

    public const MAPPED_CLASS = Section::class;

    public const MAPPING = [
        'section_id' => [],
        'page_id'    => [],
        'title'      => [],
        'sort_order' => [],
        'created_at' => ['read_only' => true],
        'updated_at' => ['read_only' => true],
    ];
}
