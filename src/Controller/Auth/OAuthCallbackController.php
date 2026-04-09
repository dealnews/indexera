<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Data\UserIdentity;
use Dealnews\Indexera\Repository;
use PageMill\HTTP\Response;

/**
 * Handles the OAuth2 provider callback.
 *
 * Verifies the CSRF state token, exchanges the authorization code for an
 * access token, fetches the provider user profile, then either logs in the
 * existing linked account or creates a new user and UserIdentity record.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
class OAuthCallbackController extends BaseController {

    use OAuthProviderTrait;

    /**
     * Validates the callback and logs the user in.
     *
     * @return void
     */
    public function handleRequest(): void {
        if ($this->current_user !== null) {
            Response::init()->redirect('/dashboard');
            return;
        }

        $state         = (string)($_GET['state'] ?? '');
        $code          = (string)($_GET['code'] ?? '');
        $provider_name = strtolower((string)($this->inputs['provider'] ?? ''));

        if ($state === '' ||
            $code === '' ||
            empty($_SESSION['oauth_state']) ||
            !hash_equals($_SESSION['oauth_state'], $state) ||
            ($provider_name !== ($_SESSION['oauth_provider'] ?? '')))
        {
            unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);
            Response::init()->redirect('/login');
            return;
        }

        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

        $provider = $this->buildProvider($provider_name);

        if ($provider === null) {
            http_response_code(404);
            return;
        }

        try {
            $token        = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $oauth_user   = $provider->getResourceOwner($token);
            $provider_uid = (string)$oauth_user->getId();
            $email        = method_exists($oauth_user, 'getEmail')
                                ? (string)$oauth_user->getEmail()
                                : '';
            $name         = method_exists($oauth_user, 'getName')
                                ? (string)$oauth_user->getName()
                                : '';
            $avatar       = method_exists($oauth_user, 'getAvatarUrl')
                                ? (string)$oauth_user->getAvatarUrl()
                                : null;
        } catch (\Throwable $e) {
            Response::init()->redirect('/login');
            return;
        }

        $repository = new Repository();
        $identities = $repository->find('UserIdentity', [
            'provider'    => $provider_name,
            'provider_id' => $provider_uid,
        ], limit: 1);

        if (!empty($identities)) {
            $_SESSION['user_id'] = reset($identities)->user_id;
            Response::init()->redirect('/dashboard');
            return;
        }

        // No linked identity — find or create the user account.
        $user = null;

        if ($email !== '') {
            $existing = $repository->find('User', ['email' => $email], limit: 1);
            if (!empty($existing)) {
                $user = reset($existing);
            }
        }

        if ($user === null) {
            $is_first_user = empty($repository->find('User', [], limit: 1));

            $source   = $name !== '' ? $name : $email;
            $username = strtolower($source);
            $username = preg_replace('/\s+/', '_', $username);
            $username = preg_replace('/[^a-z0-9_-]+/', '', $username);
            $username = trim($username, '_-');

            if ($username === '') {
                $username = 'user' . time();
            }

            $user               = new User();
            $user->email        = $email;
            $user->display_name = $username;
            $user->avatar_url   = $avatar;
            $user->is_admin     = $is_first_user;
            $user               = $repository->save('User', $user);
        }

        $identity              = new UserIdentity();
        $identity->user_id     = $user->user_id;
        $identity->provider    = $provider_name;
        $identity->provider_id = $provider_uid;
        $repository->save('UserIdentity', $identity);

        $_SESSION['user_id'] = $user->user_id;

        Response::init()->redirect('/dashboard');
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
