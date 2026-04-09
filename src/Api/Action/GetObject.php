<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Api\Action;

use Moonspot\ValueObjects\Interfaces\Export;

/**
 * Authenticated, ownership-checked single object retrieval.
 *
 * @package Dealnews\Indexera\Api\Action
 */
class GetObject extends \DealNews\DataMapperAPI\Action\GetObject {

    use AuthTrait;
    use OwnershipTrait;

    /**
     * Loads the object and verifies ownership.
     *
     * @return array
     */
    public function loadData(): array {
        $data = parent::loadData();

        if (empty($data['error']) && !empty($data)) {
            $object = $this->repository->get(
                $this->object_name,
                $this->object_id
            );

            if ($object !== null && !$this->isOwned($object)) {
                return [
                    'http_status' => 403,
                    'error'       => 'Forbidden',
                ];
            }
        }

        return $data;
    }

    /**
     * Strips the password hash from User responses.
     *
     * @param Export $data The retrieved object.
     *
     * @return array
     */
    protected function formatObject(Export $data): array {
        $result = parent::formatObject($data);
        unset($result['password']);
        return $result;
    }
}
