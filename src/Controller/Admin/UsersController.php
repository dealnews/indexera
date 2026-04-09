<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Admin;

use Dealnews\Indexera\Model\Admin\UsersModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Admin\UsersView;
use PageMill\MVC\ResponderAbstract;

/**
 * Lists all users for admin management.
 *
 * @package Dealnews\Indexera\Controller\Admin
 */
class UsersController extends BaseAdminController {

    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [UsersModel::class];
    }

    /**
     * @inheritDoc
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(UsersView::class);
    }
}
