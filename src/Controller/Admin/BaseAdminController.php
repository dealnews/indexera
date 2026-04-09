<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Admin;

use Dealnews\Indexera\Controller\BaseController;

/**
 * Base controller for all admin-only pages.
 *
 * Requires both authentication and admin privileges.
 *
 * @package Dealnews\Indexera\Controller\Admin
 */
abstract class BaseAdminController extends BaseController {

    /**
     * @inheritDoc
     */
    protected bool $require_auth = true;

    /**
     * @inheritDoc
     */
    protected bool $require_admin = true;
}
