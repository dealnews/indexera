<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a named section within a page.
 *
 * @package Dealnews\Indexera\Data
 */
class Section extends ValueObject {

    public const UNIQUE_ID_FIELD = 'section_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $section_id = 0;

    /**
     * Foreign key to the owning page.
     *
     * @var int
     */
    public int $page_id = 0;

    /**
     * Display title of the section.
     *
     * @var string
     */
    public string $title = '';

    /**
     * Display order of this section within its page.
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
