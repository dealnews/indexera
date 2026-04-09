<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a group that users can belong to and share pages within.
 *
 * @package Dealnews\Indexera\Data
 */
class Group extends ValueObject {

    public const UNIQUE_ID_FIELD = 'group_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $group_id = 0;

    /**
     * URL slug for this group, globally unique.
     *
     * @var string
     */
    public string $slug = '';

    /**
     * Display name of the group.
     *
     * @var string
     */
    public string $name = '';

    /**
     * Optional description of the group.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Foreign key to the user who created the group.
     *
     * @var int
     */
    public int $created_by = 0;

    /**
     * Record creation timestamp.
     *
     * @var string
     */
    public string $created_at = '';

    /**
     * Record last-updated timestamp.
     *
     * @var string|null
     */
    public ?string $updated_at = null;
}
