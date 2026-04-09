<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Admin;

use Dealnews\Indexera\Action\Admin\ToggleAdminAction;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Admin\UsersView;
use PageMill\MVC\ResponderAbstract;

/**
 * Toggles the admin flag for a user.
 *
 * Expects a route token: user_id (int).
 *
 * @package Dealnews\Indexera\Controller\Admin
 */
class ToggleAdminController extends BaseAdminController {

    /**
     * Casts user_id to int and injects the session user's ID.
     *
     * @param array $filters
     *
     * @return void
     */
    protected function filterInput(array $filters): void {
        parent::filterInput($filters);
        $this->inputs['user_id']         = (int)($this->inputs['user_id'] ?? 0);
        $this->inputs['current_user_id'] = $this->current_user?->user_id ?? 0;
    }

    /**
     * @inheritDoc
     */
    protected function getRequestActions(): array {
        return [ToggleAdminAction::class];
    }

    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(UsersView::class);
    }
}
