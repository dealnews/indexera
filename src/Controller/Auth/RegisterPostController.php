<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Action\Auth\RegisterAction;
use Dealnews\Indexera\Controller\BaseController;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Auth\RegisterView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles registration form submission.
 *
 * Delegates account creation to RegisterAction, which redirects on
 * success. On failure the registration form is re-rendered with an error.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class RegisterPostController extends BaseController {

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
     * Filters registration fields from the POST body.
     *
     * @return array
     */
    protected function getFilters(): array {
        return [
            INPUT_POST => [
                'email'            => FILTER_SANITIZE_EMAIL,
                'display_name'     => FILTER_DEFAULT,
                'password'         => FILTER_DEFAULT,
                'password_confirm' => FILTER_DEFAULT,
            ],
        ];
    }

    /**
     * Runs the registration action before models are built.
     *
     * @return array
     */
    protected function getRequestActions(): array {
        return [RegisterAction::class];
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
