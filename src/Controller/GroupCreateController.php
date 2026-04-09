<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\GroupCreateView;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the group creation form.
 *
 * Requires authentication.
 *
 * @package Dealnews\Indexera\Controller
 */
class GroupCreateController extends BaseController {

    /**
     * Require an authenticated user.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * Returns no models — the form has no pre-loaded data.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * Returns the HTML responder for the create group form.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(GroupCreateView::class);
    }
}
