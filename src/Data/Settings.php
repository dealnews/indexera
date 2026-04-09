<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Application-wide settings (single row, always id = 1).
 *
 * @package Dealnews\Indexera\Data
 */
class Settings extends ValueObject {

    public const UNIQUE_ID_FIELD = 'settings_id';

    /**
     * Primary key. Always 1.
     *
     * @var int
     */
    public int $settings_id = 0;

    /**
     * The site name used in HTML <title> tags.
     *
     * @var string
     */
    public string $site_title = 'Indexera';

    /**
     * The brand text shown in the navigation bar.
     *
     * @var string
     */
    public string $nav_heading = 'Indexera';

    /**
     * Whether pages are visible to guests. When false, guests are
     * redirected to the login page.
     *
     * @var bool
     */
    public bool $public_pages = true;

    /**
     * Whether new user registration is open. When false, the registration
     * form is disabled and the route redirects to login.
     *
     * @var bool
     */
    public bool $allow_registration = true;

    /**
     * Optional URL for a custom navigation bar icon. When null the
     * built-in /icon-white.svg is used.
     *
     * @var string|null
     */
    public ?string $nav_icon_url = null;
}
