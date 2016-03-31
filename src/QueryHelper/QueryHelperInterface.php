<?php
namespace Corma\QueryHelper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;

interface QueryHelperInterface
{
    /**
     * Build a simple select query for table
     *
     * @param string $table
     * @param array|string $columns
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @param array $orderBy of column => ASC / DESC pairs
     * @return QueryBuilder
     */
    public function buildSelectQuery($table, $columns = 'main.*', array $where = [], array $orderBy = []);

    /**
     * Build an update query for the provided table
     *
     * @param string $table
     * @param array $update column => value pairs to update in SET clause
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @return QueryBuilder
     */
    public function buildUpdateQuery($table, array $update, array $where);

    /**
     * Build a delete query for the provided table
     *
     * @param string $table
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @return QueryBuilder
     */
    public function buildDeleteQuery($table, array $where);

    /**
     * Update multiple rows
     *
     * @param string $table
     * @param array $update column => value pairs to update in SET clause
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @return int The number of affected rows.
     */
    public function massUpdate($table, array $update, array $where);

    /**
     * Insert multiple rows
     *
     * @param string $table
     * @param array $rows array of column => value
     * @return int The number of inserted rows
     */
    public function massInsert($table, array $rows);

    /**
     * Insert multiple rows, if a row with a duplicate key is found will update the row, may assume that id is the primary key
     *
     * @param string $table
     * @param array $rows array of column => value
     * @param null $lastInsertId Optional reference to populate with the last auto increment id
     * @return int The number of affected rows
     */
    public function massUpsert($table, array $rows, &$lastInsertId = null);

    /**
     * Delete multiple rows
     *
     * @param string $table
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @return int Number of affected rows
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function massDelete($table, array $where);

    /**
     * Counts the number of results that would be returned by the select query provided
     *
     * @param QueryBuilder $qb
     * @return int
     */
    public function getCount(QueryBuilder $qb);

    /**
     * Sets the where query part on the provided query
     *
     * @param QueryBuilder $qb
     * @param array $where column => value pairs, value may be an array for an IN() clause
     */
    public function processWhereQuery(QueryBuilder $qb, array $where);

    /**
     * Returns table metadata for the provided table
     *
     * @param string $table
     * @return array column => accepts null (bool)
     */
    public function getDbColumns($table);

    /**
     * Is this exception caused by a duplicate record (i.e. unique index constraint violation)
     *
     * @param DBALException $error
     * @return bool
     */
    public function isDuplicateException(DBALException $error);

    /**
     * Retrieve the last inserted row id
     * 
     * @param string $table
     * @param string $column
     * @return string
     */
    public function getLastInsertId($table, $column = 'id');

    /**
     * @return Connection
     */
    public function getConnection();
}
