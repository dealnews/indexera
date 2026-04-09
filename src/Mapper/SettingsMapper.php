<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\Settings;
use DealNews\DB\AbstractMapper;

/**
 * Maps Settings objects to the settings table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class SettingsMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_settings';

    public const PRIMARY_KEY = 'settings_id';

    public const MAPPED_CLASS = Settings::class;

    public const MAPPING = [
        'settings_id'  => [],
        'site_title'   => [],
        'nav_heading'  => [],
        'public_pages'       => [],
        'allow_registration' => [],
        'nav_icon_url'       => [],
    ];
}
