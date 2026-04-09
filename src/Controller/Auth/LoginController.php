<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Controller\BaseController;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Auth\LoginView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Displays the login form.
 *
 * Redirects authenticated users to the dashboard.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class LoginController extends BaseController {

    /**
     * Redirects to the dashboard if already logged in,
     * otherwise runs the standard controller pipeline.
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
     * Injects the next URL from the query string into view data.
     *
     * @param array $models
     *
     * @return void
     */
    protected function buildModels(array $models = []): void {
        $next = (string)($_GET['next'] ?? '');
        if ($next !== '' &&
            (!str_starts_with($next, '/') || str_starts_with($next, '//')))
        {
            $next = '';
        }
        $this->data['next']               = $next;
        $this->data['allow_registration'] = $this->data['settings']->allow_registration ?? true;
        parent::buildModels($models);
    }

    /**
     * Returns the HTML responder for the login form.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(LoginView::class);
    }

    /**
     * No models needed for the login form.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }
}
