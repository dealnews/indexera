<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Mapper;

use Dealnews\Indexera\Data\Session;
use DealNews\DB\AbstractMapper;

/**
 * Maps Session objects to the sessions table.
 *
 * @package Dealnews\Indexera\Mapper
 */
class SessionMapper extends AbstractMapper {

    public const DATABASE_NAME = 'indexera';

    public const TABLE = 'indexera_sessions';

    public const PRIMARY_KEY = 'session_id';

    public const MAPPED_CLASS = Session::class;

    public const MAPPING = [
        'session_id' => [],
        'token'      => [],
        'data'       => [],
        'created_at' => ['read_only' => true],
        'updated_at' => ['read_only' => true],
    ];

    /**
     * Deletes sessions inactive longer than $max_lifetime seconds.
     *
     * Uses COALESCE(updated_at, created_at) so sessions that have never
     * been updated are expired by their creation time.
     *
     * @param int $max_lifetime Maximum session age in seconds.
     *
     * @return int Number of deleted sessions.
     */
    public function deleteExpired(int $max_lifetime): int {
        $driver     = $this->crud->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $table      = $this->crud->quoteField($this->table);
        $updated_at = $this->crud->quoteField('updated_at');
        $created_at = $this->crud->quoteField('created_at');

        switch ($driver) {
            case 'mysql':
                $query = "DELETE FROM {$table} " .
                         "WHERE COALESCE({$updated_at}, {$created_at}) < " .
                         "DATE_SUB(NOW(), INTERVAL :lifetime SECOND)";
                break;
            case 'pgsql':
                $query = "DELETE FROM {$table} " .
                         "WHERE COALESCE({$updated_at}, {$created_at}) < " .
                         "NOW() - (:lifetime * INTERVAL '1 second')";
                break;
            default:
                $query = "DELETE FROM {$table} " .
                         "WHERE COALESCE({$updated_at}, {$created_at}) < " .
                         "datetime('now', '-' || :lifetime || ' seconds')";
        }

        $sth = $this->crud->run($query, ['lifetime' => $max_lifetime]);

        return $sth->rowCount();
    }
}
