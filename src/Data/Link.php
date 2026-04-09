<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a labeled URL within a section.
 *
 * @package Dealnews\Indexera\Data
 */
class Link extends ValueObject {

    public const UNIQUE_ID_FIELD = 'link_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $link_id = 0;

    /**
     * Foreign key to the owning section.
     *
     * @var int
     */
    public int $section_id = 0;

    /**
     * Display label for the link.
     *
     * @var string
     */
    public string $label = '';

    /**
     * Target URL.
     *
     * @var string
     */
    public string $url = '';

    /**
     * Display order of this link within its section.
     *
     * @var int
     */
    public int $sort_order = 0;

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
