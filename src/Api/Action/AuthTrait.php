<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Api\Action;

use DealNews\DataMapper\Repository;

/**
 * Verifies the session before dispatching an API action.
 *
 * Apply this trait to any action class that requires an authenticated
 * user. Returns a 401 JSON response and halts execution when no
 * valid session user is present.
 *
 * @package Dealnews\Indexera\Api\Action
 */
trait AuthTrait {

    /**
     * Checks authentication before invoking the action.
     *
     * @param array      $inputs     Route tokens and request data.
     * @param Repository $repository Data mapper repository.
     * @param bool       $throw      Whether to throw on error.
     *
     * @return void
     */
    public function __invoke(
        array $inputs,
        Repository $repository,
        bool $throw = false
    ): void {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        parent::__invoke($inputs, $repository, $throw);
    }
}
