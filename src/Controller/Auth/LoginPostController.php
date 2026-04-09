<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Action\Auth\LoginAction;
use Dealnews\Indexera\Controller\BaseController;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Auth\LoginView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles login form submission.
 *
 * Delegates credential validation to LoginAction, which redirects on
 * success. On failure the login form is re-rendered with an error message.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class LoginPostController extends BaseController {

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
     * Filters email and password from the POST body.
     *
     * @return array
     */
    protected function getFilters(): array {
        return [
            INPUT_POST => [
                'email'    => FILTER_SANITIZE_EMAIL,
                'password' => FILTER_DEFAULT,
            ],
        ];
    }

    /**
     * Runs the login action before models are built.
     *
     * @return array
     */
    protected function getRequestActions(): array {
        return [LoginAction::class];
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
