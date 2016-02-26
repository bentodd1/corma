<?php
namespace Corma\Repository;

use Corma\DataObject\DataObjectInterface;
use Doctrine\Common\Persistence\ObjectRepository as DoctrineObjectRepository;

interface ObjectRepositoryInterface extends DoctrineObjectRepository
{
    /**
     * Creates a new instance of the object
     *
     * @return DataObjectInterface
     */
    public function create();

    /**
     * Find one or more data objects by id
     *
     * @param array $ids
     * @param bool $useCache
     * @return \Corma\DataObject\DataObjectInterface[]
     */
    public function findByIds(array $ids, $useCache = true);

    /**
     * Return the database table this repository manages
     *
     * @return string
     */
    public function getTableName();

    /**
     * Persists the object to the database
     *
     * @param DataObjectInterface $object
     * @return DataObjectInterface
     */
    public function save(DataObjectInterface $object);

    /**
     * Removes the object from the database
     *
     * @param DataObjectInterface $object
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function delete(DataObjectInterface $object);
}