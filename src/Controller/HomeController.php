<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\HomeView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the public home page.
 *
 * Redirects authenticated users to the dashboard.
 *
 * @package Dealnews\Indexera\Controller
 */
class HomeController extends BaseController {

    /**
     * Redirects to the dashboard if already logged in,
     * otherwise renders the home page.
     *
     * @return void
     */
    public function handleRequest(): void {
        if ($this->current_user !== null) {
            Response::init()->redirect('/dashboard');
            return;
        }

        parent::handleRequest();
    }

    /**
     * No models needed for the home page.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * Returns the HTML responder for the home page.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(HomeView::class);
    }
}
