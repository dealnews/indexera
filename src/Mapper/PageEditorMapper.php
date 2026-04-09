<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\PageEditor;
use DealNews\DB\AbstractMapper;

/**
 * Maps PageEditor objects to the page_editors table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class PageEditorMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_page_editors';

    public const PRIMARY_KEY = 'page_editor_id';

    public const MAPPED_CLASS = PageEditor::class;

    public const MAPPING = [
        'page_editor_id' => [],
        'page_id'        => [],
        'user_id'        => [],
        'created_at'     => ['read_only' => true],
    ];
}
