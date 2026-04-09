<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Model\GroupsModel;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\GroupsView;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the public group directory.
 *
 * @package Dealnews\Indexera\Controller
 */
class GroupsController extends BaseController {

    /**
     * Injects the requested page number into inputs.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        $this->inputs['page'] = max(1, (int)($_GET['page'] ?? 1));
        parent::buildModels($models);
    }

    /**
     * Returns the groups directory model.
     *
     * @return array
     */
    protected function getModels(): array {
        return [GroupsModel::class];
    }

    /**
     * Returns the HTML responder for the group directory.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(GroupsView::class);
    }
}
