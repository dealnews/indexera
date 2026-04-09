<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\PageSubscription;
use DealNews\DB\AbstractMapper;

/**
 * Maps PageSubscription objects to the page_subscriptions table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class PageSubscriptionMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_page_subscriptions';

    public const PRIMARY_KEY = 'page_subscription_id';

    public const MAPPED_CLASS = PageSubscription::class;

    public const MAPPING = [
        'page_subscription_id' => [],
        'user_id'              => [],
        'page_id'              => [],
        'created_at'           => ['read_only' => true],
    ];
}
