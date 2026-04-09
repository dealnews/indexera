<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Represents a user who has been granted edit access to a page.
 *
 * A PageEditor record grants the user full editing rights on the page,
 * including adding/removing sections, links, and other editors.
 * The page owner is never stored as a PageEditor record.
 *
 * @package Dealnews\Indexera\Data
 */
class PageEditor extends ValueObject {

    public const UNIQUE_ID_FIELD = 'page_editor_id';

    /**
     * Primary key.
     *
     * @var int
     */
    public int $page_editor_id = 0;

    /**
     * The page this editor has access to.
     *
     * @var int
     */
    public int $page_id = 0;

    /**
     * The user who has editor access.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * Timestamp when editor access was granted.
     *
     * @var string
     */
    public string $created_at = '';
}
