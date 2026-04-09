<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Api\Action;

/**
 * Authenticated, ownership-checked delete action.
 *
 * Admins may delete any User account except their own. All other
 * objects require standard ownership verification.
 *
 * @package Dealnews\Indexera\Api\Action
 */
class DeleteObject extends \DealNews\DataMapperAPI\Action\DeleteObject {

    use AuthTrait;
    use OwnershipTrait;

    /**
     * Verifies ownership before deleting the object.
     *
     * @return array
     */
    public function loadData(): array {
        $session_user_id = (int)$_SESSION['user_id'];

        $object = $this->repository->get(
            $this->object_name,
            $this->object_id
        );

        if ($object === null) {
            return [
                'http_status' => 404,
                'error'       => 'Not Found',
            ];
        }

        if ($this->object_name === 'User') {
            $session_user = $this->repository->get('User', $session_user_id);

            if ($session_user === null || !$session_user->is_admin) {
                return [
                    'http_status' => 403,
                    'error'       => 'Forbidden',
                ];
            }

            if ($this->object_id === $session_user_id) {
                return [
                    'http_status' => 422,
                    'error'       => 'You cannot delete your own account.',
                ];
            }
        } elseif (!$this->isOwned($object)) {
            return [
                'http_status' => 403,
                'error'       => 'Forbidden',
            ];
        }

        return parent::loadData();
    }
}
