<?php

declare(strict_types=1);

namespace Dealnews\Indexera;

use Dealnews\Indexera\Api\Action\DeleteObject;
use Dealnews\Indexera\Api\Action\GetObject;
use Dealnews\Indexera\Api\Action\SearchObjects;
use Dealnews\Indexera\Api\Action\UpdateObject;

/**
 * Extends the DataMapperAPI with authentication and ownership checks.
 *
 * Overrides all route action classes to use the application's own
 * action subclasses, which enforce session authentication (401) and
 * per-object ownership authorization (403).
 *
 * @package Dealnews\Indexera
 */
class Api extends \DealNews\DataMapperAPI\API {

    /**
     * @inheritDoc
     */
    protected array $get_object_route = [
        'type'    => 'regex',
        'pattern' => '/([^/]+)/(\d+)/',
        'method'  => 'GET',
        'action'  => GetObject::class,
        'tokens'  => [
            'object_name',
            'object_id',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected array $search_objects_route = [
        'type'    => 'regex',
        'pattern' => '/([^/]+)/_search/',
        'method'  => 'POST',
        'action'  => SearchObjects::class,
        'tokens'  => [
            'object_name',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected array $update_object_route = [
        'type'    => 'regex',
        'pattern' => '/([^/]+)/(\d+)/',
        'method'  => 'PUT',
        'action'  => UpdateObject::class,
        'tokens'  => [
            'object_name',
            'object_id',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected array $create_object_route = [
        'type'    => 'regex',
        'pattern' => '/([^/]+)/',
        'method'  => 'POST',
        'action'  => UpdateObject::class,
        'tokens'  => [
            'object_name',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected array $delete_object_route = [
        'type'    => 'regex',
        'pattern' => '/([^/]+)/(\d+)/',
        'method'  => 'DELETE',
        'action'  => DeleteObject::class,
        'tokens'  => [
            'object_name',
            'object_id',
        ],
    ];
}
