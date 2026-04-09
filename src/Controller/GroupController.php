<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\GroupViewModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\GroupView;
use Dealnews\Indexera\View\NotFoundView;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the group home page with its list of pages.
 *
 * @package Dealnews\Indexera\Controller
 */
class GroupController extends BaseController {

    /**
     * Build models before getResponder() so the not_found check works.
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
     * Returns the group view model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [GroupViewModel::class];
    }

    /**
     * Returns the appropriate responder based on whether the group was found.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        if (!empty($this->data['not_found'])) {
            http_response_code(404);
            return new HtmlResponder(NotFoundView::class);
        }

        return new HtmlResponder(GroupView::class);
    }
}
