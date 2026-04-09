<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a persisted PHP session.
 *
 * @package Dealnews\Indexera\Data
 */
class Session extends ValueObject {

    public const UNIQUE_ID_FIELD = 'session_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $session_id = 0;

    /**
     * Unique session token used as the session identifier.
     *
     * @var string
     */
    public string $token = '';

    /**
     * Serialized session data.
     *
     * @var string|null
     */
    public ?string $data = null;

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
