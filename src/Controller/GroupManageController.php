<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\GroupManageModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\GroupManageView;
use Dealnews\Indexera\View\NotFoundView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the group member management page.
 *
 * Requires authentication. Non-members are redirected to the group
 * home page. Groups that don't exist return 404.
 *
 * @package Dealnews\Indexera\Controller
 */
class GroupManageController extends BaseController {

    /**
     * Require an authenticated user.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * Build models before getResponder() to check membership and existence.
     *
     * @var bool
     */
    protected bool $build_models_first = true;

    /**
     * Injects the group slug and current user ID into inputs.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        $this->inputs['group_slug']      = $this->inputs['group_slug'] ?? '';
        $this->inputs['current_user_id'] = $this->current_user?->user_id ?? 0;
        parent::buildModels($models);
    }

    /**
     * Returns the group manage model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [GroupManageModel::class];
    }

    /**
     * Returns the appropriate responder based on group existence and membership.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        if (!empty($this->data['not_found'])) {
            http_response_code(404);
            return new HtmlResponder(NotFoundView::class);
        }

        if (!empty($this->data['not_member'])) {
            $slug = $this->inputs['group_slug'] ?? '';
            Response::init()->redirect('/groups/' . $slug);
            return new HtmlResponder(NotFoundView::class);
        }

        return new HtmlResponder(GroupManageView::class);
    }
}
