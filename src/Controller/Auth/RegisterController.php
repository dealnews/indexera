<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Controller\BaseController;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Auth\RegisterView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Displays the registration form.
 *
 * Redirects authenticated users to the dashboard.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class RegisterController extends BaseController {

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

        if (!($this->data['settings']->allow_registration ?? true)) {
            Response::init()->redirect('/login');
            return;
        }

        parent::handleRequest();
    }

    /**
     * Returns the HTML responder for the registration form.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(RegisterView::class);
    }

    /**
     * No models needed for the registration form.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }
}
