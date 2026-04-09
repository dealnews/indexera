<?php

declare(strict_types=1);

namespace Dealnews\Indexera;

use Dealnews\Indexera\Data\Session;
use Dealnews\Indexera\Mapper\SessionMapper;

/**
 * Database-backed PHP session handler.
 *
 * Stores session data in the sessions table, enabling sessions to be
 * shared across multiple application nodes.
 *
 * @package Dealnews\Indexera
 */
class SessionHandler implements \SessionHandlerInterface {

    /**
     * @param SessionMapper $mapper Session mapper instance.
     */
    public function __construct(protected SessionMapper $mapper) {
    }

    /**
     * Opens the session.
     *
     * @param string $path Save path (unused).
     * @param string $name Session name (unused).
     *
     * @return bool
     */
    public function open(string $path, string $name): bool {
        return true;
    }

    /**
     * Closes the session.
     *
     * @return bool
     */
    public function close(): bool {
        return true;
    }

    /**
     * Reads session data by token.
     *
     * Returns an empty string (not false) when the session does not
     * exist — PHP treats an empty string as a new session.
     *
     * @param string $id Session token.
     *
     * @return string|false Serialized session data, or empty string if
     *                      not found.
     */
    public function read(string $id): string|false {
        $return  = '';
        $results = $this->mapper->find(['token' => $id]);

        if (!empty($results)) {
            $session = reset($results);
            $return  = (string)($session->data ?? '');
        }

        return $return;
    }

    /**
     * Writes session data for the given token.
     *
     * Creates the session record if it does not yet exist.
     *
     * @param string $id   Session token.
     * @param string $data Serialized session data.
     *
     * @return bool
     */
    public function write(string $id, string $data): bool {
        $results = $this->mapper->find(['token' => $id]);

        if (!empty($results)) {
            $session = reset($results);
        } else {
            $session        = new Session();
            $session->token = $id;
        }

        $session->data = $data;
        $this->mapper->save($session);

        return true;
    }

    /**
     * Destroys the session record for the given token.
     *
     * @param string $id Session token.
     *
     * @return bool
     */
    public function destroy(string $id): bool {
        $results = $this->mapper->find(['token' => $id]);

        if (!empty($results)) {
            $session = reset($results);
            $this->mapper->delete($session->session_id);
        }

        return true;
    }

    /**
     * Removes sessions inactive longer than $max_lifetime seconds.
     *
     * @param int $max_lifetime Session lifetime in seconds.
     *
     * @return int|false Number of sessions deleted, or false on failure.
     */
    public function gc(int $max_lifetime): int|false {
        return $this->mapper->deleteExpired($max_lifetime);
    }
}
