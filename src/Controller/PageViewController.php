<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\PageViewModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\NotFoundView;
use Dealnews\Indexera\View\PageView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders a user's public link page.
 *
 * Accessible to guests for public pages. Private pages are visible
 * only to the owning user; all other viewers receive a 404.
 *
 * @package Dealnews\Indexera\Controller
 */
class PageViewController extends BaseController {

    /**
     * Build models before getResponder() so the not_found check works.
     *
     * @var bool
     */
    protected bool $build_models_first = true;

    /**
     * Injects the current user's ID into inputs for the model,
     * then runs the standard pipeline.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        $this->inputs['current_user_id'] = $this->current_user?->user_id ?? 0;
        parent::buildModels($models);
    }

    /**
     * Returns the page view model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [PageViewModel::class];
    }

    /**
     * Returns the appropriate responder based on whether the page was found.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        if (!empty($this->data['login_required'])) {
            $next = urlencode($this->data['next_url'] ?? '');
            Response::init()->redirect('/login?next=' . $next);
            return new HtmlResponder(NotFoundView::class);
        }

        if (!empty($this->data['not_found'])) {
            http_response_code(404);
            return new HtmlResponder(NotFoundView::class);
        }

        return new HtmlResponder(PageView::class);
    }
}
