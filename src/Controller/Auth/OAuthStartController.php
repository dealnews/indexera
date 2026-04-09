<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Controller\BaseController;
use PageMill\HTTP\Response;

/**
 * Initiates an OAuth2 authorization flow.
 *
 * Reads the provider token from the route, builds the appropriate
 * OAuth2 provider, stores the CSRF state token in the session, and
 * redirects the user to the provider's authorization URL.
 *
 * Supported providers: github, google, microsoft.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class OAuthStartController extends BaseController {

    use OAuthProviderTrait;

    /**
     * Redirects to the dashboard if already logged in, otherwise
     * builds the provider and redirects to the authorization URL.
     *
     * @return void
     */
    public function handleRequest(): void {
        if ($this->current_user !== null) {
            Response::init()->redirect('/dashboard');
            return;
        }

        $provider_name = strtolower((string)($this->inputs['provider'] ?? ''));
        $provider      = $this->buildProvider($provider_name);

        if ($provider === null) {
            http_response_code(404);
            return;
        }

        $auth_url                   = $provider->getAuthorizationUrl();
        $_SESSION['oauth_state']    = $provider->getState();
        $_SESSION['oauth_provider'] = $provider_name;

        Response::init()->redirect($auth_url);
    }

    /**
     * No models needed for this controller.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * No responder needed; this controller always redirects.
     *
     * @return \PageMill\MVC\ResponderAbstract
     */
    protected function getResponder(): \PageMill\MVC\ResponderAbstract {
        return new \Dealnews\Indexera\Responder\HtmlResponder(
            \Dealnews\Indexera\View\Auth\LoginView::class
        );
    }
}
