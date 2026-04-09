<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\UserPagesModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\DashboardView;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the authenticated user's dashboard.
 *
 * Requires authentication. Loads the user's pages via UserPagesModel.
 *
 * @package Dealnews\Indexera\Controller
 */
class DashboardController extends BaseController {

    /**
     * Require an authenticated user for this controller.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * Injects the current user's ID into inputs so UserPagesModel
     * can receive it, then runs the standard pipeline.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        if ($this->current_user !== null) {
            $this->inputs['user_id'] = $this->current_user->user_id;
        }

        parent::buildModels($models);
    }

    /**
     * Returns the user's pages model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [UserPagesModel::class];
    }

    /**
     * Returns the HTML responder for the dashboard.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(DashboardView::class);
    }
}
