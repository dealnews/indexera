<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\PageEditModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\NotFoundView;
use Dealnews\Indexera\View\PageEditView;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the section and link editor for a page.
 *
 * Requires authentication. Returns 404 if the page does not exist
 * or is not owned by the current user.
 *
 * @package Dealnews\Indexera\Controller
 */
class PageEditController extends BaseController {

    /**
     * Require an authenticated user for this controller.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * Build models before getResponder() so the not_found check works.
     *
     * @var bool
     */
    protected bool $build_models_first = true;

    /**
     * Injects the current user's ID into inputs for the model.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        if ($this->current_user !== null) {
            $this->inputs['current_user_id'] = $this->current_user->user_id;
        }

        parent::buildModels($models);
    }

    /**
     * Casts the page_id route token to int.
     *
     * @param array $filters
     *
     * @return void
     */
    protected function filterInput(array $filters): void {
        parent::filterInput($filters);
        $this->inputs['page_id'] = (int)($this->inputs['page_id'] ?? 0);
    }

    /**
     * Returns the page edit model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [PageEditModel::class];
    }

    /**
     * Returns the appropriate responder based on whether the page was found.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        if (!empty($this->data['not_found'])) {
            http_response_code(404);
            return new HtmlResponder(NotFoundView::class);
        }

        return new HtmlResponder(PageEditView::class);
    }
}
