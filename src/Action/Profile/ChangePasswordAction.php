<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Action\Profile;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ActionAbstract;

/**
 * Validates the current password and updates it to the new one.
 *
 * Expects inputs:
 *   - user_id          int     The authenticated user's ID.
 *   - current_password string  The user's existing password.
 *   - new_password     string  The desired new password.
 *   - confirm_password string  Must match new_password.
 *
 * Returns ['error' => string] on failure, redirects to /profile on success.
 *
 * @package Dealnews\Indexera\Action\Profile
 */
class ChangePasswordAction extends ActionAbstract {

    /**
     * The authenticated user's ID.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * The user's current password.
     *
     * @var string
     */
    public string $current_password = '';

    /**
     * The desired new password.
     *
     * @var string
     */
    public string $new_password = '';

    /**
     * Confirmation of the new password.
     *
     * @var string
     */
    public string $confirm_password = '';

    /**
     * Validates and applies the password change.
     *
     * @return array
     */
    public function doAction(): array {
        if ($this->new_password !== $this->confirm_password) {
            return ['error' => 'New passwords do not match.'];
        }

        if (strlen($this->new_password) < 8) {
            return ['error' => 'New password must be at least 8 characters.'];
        }

        $repository = new Repository();
        $user       = $repository->get('User', $this->user_id);

        if ($user === null || !password_verify($this->current_password, $user->password)) {
            return ['error' => 'Current password is incorrect.'];
        }

        $user->password = password_hash($this->new_password, PASSWORD_DEFAULT);
        $repository->save('User', $user);

        header('Location: /profile');
        exit;
    }
}
