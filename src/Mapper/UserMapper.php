<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\User;
use DealNews\DB\AbstractMapper;

/**
 * Maps User objects to the users table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class UserMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_users';

    public const PRIMARY_KEY = 'user_id';

    public const MAPPED_CLASS = User::class;

    public const MAPPING = [
        'user_id'      => [],
        'email'        => [],
        'password'     => [],
        'display_name' => [],
        'avatar_url'   => [],
        'is_admin'     => [],
        'created_at'   => ['read_only' => true],
        'updated_at'   => ['read_only' => true],
    ];
}
