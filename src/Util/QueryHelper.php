<?php
namespace Corma\Util;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class QueryHelper
{
    /**
     * @var Connection
     */
    private $db;
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Connection $db, Cache $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    /**
     * Shortcut to build a simple query on this DataObject's table
     *
     * @param string $table
     * @param array|string $columns
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @param array $orderBy of column => ASC / DESC pairs
     * @return QueryBuilder
     */
    public function buildSelectQuery($table, $columns = 'main.*', array $where = [], array $orderBy = [])
    {
        $qb = $this->db->createQueryBuilder()->select($columns)->from($this->db->quoteIdentifier($table), 'main');

        $this->processWhereQuery($qb, $where);

        foreach($orderBy as $column => $order) {
            $qb->addOrderBy($this->db->quoteIdentifier($column), $order);
        }

        return $qb;
    }

    /**
     * @param string $table
     * @param array $update column => value pairs to update in SET clause
     * @param array $where column => value pairs, value may be an array for an IN() clause
     * @return QueryBuilder
     */
    public function buildUpdateQuery($table, array $update, array $where)
    {
        $qb = $this->db->createQueryBuilder()->update($this->db->quoteIdentifier($table), 'main');

        foreach($update as $column => $value) {
            $paramName = self::getParameterName($column);
            if($value === null) {
                $qb->set($this->db->quoteIdentifier($column), 'NULL');
            } else  {
                $qb->set($this->db->quoteIdentifier($column), ":$paramName")
                    ->setParameter($paramName, $value);
            }
        }

        $this->processWhereQuery($qb, $where);

        return $qb;
    }

    /**
     * Counts the number of results that would be returned
     *
     * @param QueryBuilder $qb
     * @return int
     */
    public static function getCount(QueryBuilder $qb)
    {
        if($qb->getType() != QueryBuilder::SELECT) {
            throw new \InvalidArgumentException('Query builder must be a select query');
        }

        $select = $qb->getQueryPart('select');
        $count = (int) $qb->select('COUNT(main.id)')
            ->execute()->fetchColumn();
        $qb->select($select);
        return $count;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $where
     */
    public function processWhereQuery(QueryBuilder $qb, array $where)
    {
        $firstWhere = true;
        $db = $qb->getConnection();
        foreach ($where as $column => $value) {
            $paramName = $this->getParameterName($column);
            if (is_array($value)) {
                $clause = $db->quoteIdentifier($column) . " IN(:$paramName)";
                $qb->setParameter($paramName, $value, Connection::PARAM_STR_ARRAY);
            } else if($value === null && $this->acceptsNull($qb->getQueryPart('from')['table'], $column)) {
                $clause = $db->quoteIdentifier($column) . ' IS NULL';
            } else {
                $clause = $db->quoteIdentifier($column) . ' = :' . $paramName;
                $qb->setParameter($paramName, $value);
            }

            if ($firstWhere) {
                $qb->where($clause);
                $firstWhere = false;
            } else {
                $qb->andWhere($clause);
            }
        }
    }

    /**
     * @param string $table
     * @param string $column
     * @return bool
     */
    private function acceptsNull($table, $column)
    {
        $columns = $this->getDbColumns($table);
        if(!isset($columns[$column])) {
            return false;
        }
        return $columns[$column];
    }

    /**
     * @param string $table
     * @return array column => accepts null (bool)
     */
    public function getDbColumns($table)
    {
        $key = 'db_columns.'.$table;
        if($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        } else {
            $query = 'DESCRIBE ' . $this->db->quoteIdentifier($table);
            $statement = $this->db->prepare($query);
            $statement->execute();
            $dbColumnInfo = $statement->fetchAll(\PDO::FETCH_OBJ);
            $dbColumns = [];
            foreach($dbColumnInfo as $column) {
                $dbColumns[$column->Field] = $column->Null == 'YES' ? true : false;
            }
            $this->cache->save($key, $dbColumns);
            return $dbColumns;
        }
    }

    /**
     * @param $columnName
     * @return string
     */
    private function getParameterName($columnName)
    {
        //named parameters with the table alias are not handled properly, chop off table alias
        $paramName = preg_replace('/^([\w]+\\.)(.*)/', '$2', $columnName);
        return $paramName;
    }
}