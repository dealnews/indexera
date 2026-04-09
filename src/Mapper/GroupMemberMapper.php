<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\GroupMember;
use DealNews\DB\AbstractMapper;

/**
 * Maps GroupMember objects to the group_members table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class GroupMemberMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_group_members';

    public const PRIMARY_KEY = 'group_member_id';

    public const MAPPED_CLASS = GroupMember::class;

    public const MAPPING = [
        'group_member_id' => [],
        'group_id'        => [],
        'user_id'         => [],
        'created_at'      => ['read_only' => true],
    ];
}
