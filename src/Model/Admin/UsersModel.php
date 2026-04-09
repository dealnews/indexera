<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Model\Admin;

use Dealnews\Indexera\Repository;
use PageMill\MVC\ModelAbstract;

/**
 * Loads all user accounts for the admin user list.
 *
 * @package Dealnews\Indexera\Model\Admin
 */
class UsersModel extends ModelAbstract {

    /**
     * Fetches all users ordered by user_id.
     *
     * @return array
     */
    public function getData(): array {
        $repository = new Repository();
        $users      = $repository->find('User', [], order: 'user_id', limit: 10000) ?? [];

        return ['users' => array_values($users)];
    }
}
