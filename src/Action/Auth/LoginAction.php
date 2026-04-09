<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Action\Auth;

use Dealnews\Indexera\Repository;
use PageMill\HTTP\Response;
use PageMill\MVC\ActionAbstract;

/**
 * Attempts to authenticate a user with email and password.
 *
 * On success, writes the user ID to the session and redirects to the
 * dashboard. On failure, returns an error string to be rendered by the
 * login form.
 *
 * @package Dealnews\Indexera\Action\Auth
 */
class LoginAction extends ActionAbstract {

    /**
     * Email address submitted by the user.
     *
     * @var string
     */
    public string $email = '';

    /**
     * Password submitted by the user.
     *
     * @var string
     */
    public string $password = '';

    /**
     * URL to redirect to after successful login, or empty string for default.
     *
     * @var string
     */
    public string $next = '';

    /**
     * Validates credentials and logs the user in.
     *
     * Returns an error key on failure; redirects on success.
     *
     * @param array $data Unused.
     *
     * @return array<string, string>|null
     */
    public function doAction(array $data = []): mixed {
        if ($this->email === '' || $this->password === '') {
            return ['error' => 'Email and password are required.'];
        }

        $repository = new Repository();
        $users      = $repository->find('User', ['email' => $this->email], limit: 1);
        $user       = !empty($users) ? reset($users) : null;

        if ($user === null ||
            $user->password === null ||
            !password_verify($this->password, $user->password))
        {
            return ['error' => 'Invalid email or password.'];
        }

        $_SESSION['user_id'] = $user->user_id;

        $redirect = ($this->next !== '' &&
            str_starts_with($this->next, '/') &&
            !str_starts_with($this->next, '//'))
            ? $this->next
            : '/dashboard';

        Response::init()->redirect($redirect);

        return null;
    }
}
