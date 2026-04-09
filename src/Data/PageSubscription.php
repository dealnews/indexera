<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a user's subscription to another user's public page.
 *
 * @package Dealnews\Indexera\Data
 */
class PageSubscription extends ValueObject {

    public const UNIQUE_ID_FIELD = 'page_subscription_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $page_subscription_id = 0;

    /**
     * Foreign key to the subscribing user.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * Foreign key to the subscribed page.
     *
     * @var int
     */
    public int $page_id = 0;

    /**
     * Record creation timestamp.
     *
     * @var string
     */
    public string $created_at = '';
}
