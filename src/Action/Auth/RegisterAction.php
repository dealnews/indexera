<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Action\Auth;

use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Repository;
use PageMill\HTTP\Response;
use PageMill\MVC\ActionAbstract;
use DealNews\DataMapper\Repository as BaseRepository;

/**
 * Creates a new user account from registration form input.
 *
 * Validates that the email is not already registered, that the two
 * password fields match, then hashes the password and persists the
 * new user. On success, writes the user ID to the session and redirects
 * to the dashboard. On failure, returns an error string.
 *
 * @package Dealnews\Indexera\Action\Auth
 */
class RegisterAction extends ActionAbstract {

    /**
     * Email address submitted by the user.
     *
     * @var string
     */
    public string $email = '';

    /**
     * Username submitted by the user.
     *
     * @var string
     */
    public string $display_name = '';

    /**
     * Password submitted by the user.
     *
     * @var string
     */
    public string $password = '';

    /**
     * Password confirmation submitted by the user.
     *
     * @var string
     */
    public string $password_confirm = '';

    /**
     * Repository instance. Injected for testing; production code creates its own.
     *
     * @var BaseRepository|null
     */
    protected ?BaseRepository $repository = null;

    /**
     * Validates input, creates the account, and logs the user in.
     *
     * Returns an error key on failure; redirects on success.
     *
     * @param array $data Unused.
     *
     * @return array<string, string>|null
     */
    public function doAction(array $data = []): mixed {
        if ($this->email === '' ||
            $this->display_name === '' ||
            $this->password === '')
        {
            return ['error' => 'All fields are required.'];
        }

        $username = strtolower(trim($this->display_name));

        if (!preg_match('/^[a-z0-9_-]+$/', $username)) {
            return [
                'error' => 'Username may only contain lowercase letters, ' .
                           'numbers, hyphens, and underscores.',
            ];
        }

        $this->display_name = $username;

        if ($this->password !== $this->password_confirm) {
            return ['error' => 'Passwords do not match.'];
        }

        $repository = $this->repository ?? Repository::init();
        $existing   = $repository->find('User', ['email' => $this->email], limit: 1);

        if (!empty($existing)) {
            return ['error' => 'An account with that email already exists.'];
        }

        $is_first_user = empty($repository->find('User', [], limit: 1));

        $user               = new User();
        $user->email        = $this->email;
        $user->display_name = $this->display_name;
        $user->password     = password_hash($this->password, PASSWORD_DEFAULT);
        $user->is_admin     = $is_first_user;

        $user = $repository->save('User', $user);

        $_SESSION['user_id'] = $user->user_id;

        $this->doRedirect('/dashboard');

        return null;
    }

    /**
     * Redirects the browser. Extracted to allow test overrides.
     *
     * @param string $url The destination URL.
     *
     * @return void
     */
    protected function doRedirect(string $url): void {
        Response::init()->redirect($url);
    }
}
