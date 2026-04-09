<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\PagesModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\NotFoundView;
use Dealnews\Indexera\View\PagesView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the public page directory.
 *
 * Accessible to guests unless the global public_pages setting is
 * disabled, in which case guests are redirected to login.
 *
 * @package Dealnews\Indexera\Controller
 */
class PagesController extends BaseController {

    /**
     * Build models before getResponder() so the login_required check works.
     *
     * @var bool
     */
    protected bool $build_models_first = true;

    /**
     * Injects the current user ID and the requested page number into inputs.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        $this->inputs['current_user_id'] = $this->current_user?->user_id ?? 0;
        $this->inputs['page']            = max(1, (int)($_GET['page'] ?? 1));
        parent::buildModels($models);
    }

    /**
     * Returns the pages directory model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [PagesModel::class];
    }

    /**
     * Returns the appropriate responder.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        if (!empty($this->data['login_required'])) {
            Response::init()->redirect('/login?next=' . urlencode('/pages'));
            return new HtmlResponder(NotFoundView::class);
        }

        return new HtmlResponder(PagesView::class);
    }
}
