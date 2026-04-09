<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a user's page of links.
 *
 * @package Dealnews\Indexera\Data
 */
class Page extends ValueObject {

    public const UNIQUE_ID_FIELD = 'page_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $page_id = 0;

    /**
     * Foreign key to the owning user.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * Foreign key to the group this page belongs to, or 0 for personal pages.
     *
     * @var int
     */
    public int $group_id = 0;

    /**
     * URL slug for this page, unique per user or per group.
     *
     * @var string
     */
    public string $slug = '';

    /**
     * Display title of the page.
     *
     * @var string
     */
    public string $title = '';

    /**
     * Optional description of the page.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Whether the page is publicly visible.
     *
     * @var bool
     */
    public bool $is_public = false;

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
