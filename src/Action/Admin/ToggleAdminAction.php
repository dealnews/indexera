<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Action\Admin;

use Dealnews\Indexera\Repository;
use DealNews\DataMapper\Repository as BaseRepository;
use PageMill\HTTP\Response;
use PageMill\MVC\ActionAbstract;

/**
 * Toggles the is_admin flag on a user account.
 *
 * An admin cannot demote themselves. Redirects to /admin/users
 * on both success and failure.
 *
 * @package Dealnews\Indexera\Action\Admin
 */
class ToggleAdminAction extends ActionAbstract {

    /**
     * ID of the user to toggle.
     *
     * @var int
     */
    public int $user_id = 0;

    /**
     * ID of the currently logged-in admin (to prevent self-demotion).
     *
     * @var int
     */
    public int $current_user_id = 0;

    /**
     * Repository instance. Injected for testing; production code creates its own.
     *
     * @var BaseRepository|null
     */
    protected ?BaseRepository $repository = null;

    /**
     * Flips the is_admin flag and redirects.
     *
     * @param array $data Unused.
     *
     * @return null
     */
    public function doAction(array $data = []): mixed {
        if ($this->user_id === 0 || $this->user_id === $this->current_user_id) {
            $this->doRedirect('/admin/users');
            return null;
        }

        $repository = $this->repository ?? Repository::init();
        $user       = $repository->get('User', $this->user_id);

        if ($user !== null) {
            $user->is_admin = !$user->is_admin;
            $repository->save('User', $user);
        }

        $this->doRedirect('/admin/users');

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
