<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a user's membership in a group.
 *
 * @package Dealnews\Indexera\Data
 */
class GroupMember extends ValueObject {

    public const UNIQUE_ID_FIELD = 'group_member_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $group_member_id = 0;

    /**
     * Foreign key to the group.
     *
     * @var int
     */
    public int $group_id = 0;

    /**
     * Foreign key to the member user.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * Record creation timestamp.
     *
     * @var string
     */
    public string $created_at = '';
}
