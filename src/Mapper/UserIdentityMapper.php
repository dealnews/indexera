<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\UserIdentity;
use DealNews\DB\AbstractMapper;

/**
 * Maps UserIdentity objects to the user_identities table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class UserIdentityMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_user_identities';

    public const PRIMARY_KEY = 'user_identity_id';

    public const MAPPED_CLASS = UserIdentity::class;

    public const MAPPING = [
        'user_identity_id' => [],
        'user_id'          => [],
        'provider'         => [],
        'provider_user_id' => [],
        'created_at'       => ['read_only' => true],
    ];
}
