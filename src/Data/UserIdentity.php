<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents an OAuth identity linked to a user account.
 *
 * @package Dealnews\Indexera\Data
 */
class UserIdentity extends ValueObject {

    public const UNIQUE_ID_FIELD = 'user_identity_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $user_identity_id = 0;

    /**
     * Foreign key to the owning user.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * OAuth provider name (e.g. "github", "google").
     *
     * @var string
     */
    public string $provider = '';

    /**
     * The user's ID as returned by the OAuth provider.
     *
     * @var string
     */
    public string $provider_user_id = '';

    /**
     * Record creation timestamp.
     *
     * @var string
     */
    public string $created_at = '';
}
