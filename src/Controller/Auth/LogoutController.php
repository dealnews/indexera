<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Controller\BaseController;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Auth\LoginView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Destroys the session and redirects to the home page.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class LogoutController extends BaseController {

    /**
     * Clears session data and redirects to /.
     *
     * @return void
     */
    public function handleRequest(): void {
        $_SESSION = [];
        session_destroy();
        Response::init()->redirect('/');
    }

    /**
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(LoginView::class);
    }

    /**
     * @return array
     */
    protected function getModels(): array {
        return [];
    }
}
