<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a user account.
 *
 * @package Dealnews\Indexera\Data
 */
class User extends ValueObject {

    public const UNIQUE_ID_FIELD = 'user_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * Email address used for password-based login.
     *
     * @var string
     */
    public string $email = '';

    /**
     * Hashed password. Null for OAuth-only accounts.
     *
     * @var string|null
     */
    public ?string $password = null;

    /**
     * Username used in URLs and shown in the UI.
     * Contains only lowercase letters, digits, hyphens, and underscores.
     *
     * @var string
     */
    public string $display_name = '';

    /**
     * URL to the user's avatar image.
     *
     * @var string|null
     */
    public ?string $avatar_url = null;

    /**
     * Whether the user has admin privileges.
     *
     * @var bool
     */
    public bool $is_admin = false;

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
