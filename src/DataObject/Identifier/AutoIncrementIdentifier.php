<?php
namespace Corma\DataObject\Identifier;

use Corma\DataObject\TableConvention\TableConventionInterface;
use Corma\QueryHelper\QueryHelperInterface;
use Corma\Util\Inflector;

/**
 * Sets the id for new objects from the database sequence / auto increment id
 */
class AutoIncrementIdentifier extends BaseIdentifier
{
    public function __construct(Inflector $inflector, private QueryHelperInterface $queryHelper, private TableConventionInterface $convention)
    {
        parent::__construct($inflector);
    }

    public function setNewId(object $object): object
    {
        $table = $this->convention->getTable($object);
        return $this->setId($object, $this->queryHelper->getLastInsertId($table, $this->getIdColumn($object)));
    }

    public function isNew($object): bool
    {
        return !$this->getId($object);
    }
}
