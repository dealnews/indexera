<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\Group;
use DealNews\DB\AbstractMapper;

/**
 * Maps Group objects to the groups table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class GroupMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_groups';

    public const PRIMARY_KEY = 'group_id';

    public const MAPPED_CLASS = Group::class;

    public const MAPPING = [
        'group_id'    => [],
        'slug'        => [],
        'name'        => [],
        'description' => [],
        'created_by'  => [],
        'created_at'  => ['read_only' => true],
        'updated_at'  => ['read_only' => true],
    ];
}
