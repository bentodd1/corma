<?php
namespace Corma\Util;

use Corma\DataObject\DataObject;
use Corma\DataObject\DataObjectInterface;
use Corma\Exception\InvalidArgumentException;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class representing a paged query
 */
class PagedQuery implements \JsonSerializable
{
    const DEFAULT_PAGESIZE = 100;

    /** @var int  */
    protected $pageSize, $resultCount, $pages, $page, $prev, $next;

    /** @var string */
    private $class;
    /**
     * @var QueryHelper
     */
    private $queryHelper;

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPrev()
    {
        return $this->prev;
    }

    /**
     * @return int
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @var QueryBuilder
     */
    private $qb;

    /**
     * @param QueryBuilder $qb
     * @param QueryHelper $queryHelper
     * @param string $class Full class name
     * @param int $pageSize
     */
    public function __construct(QueryBuilder $qb, QueryHelper $queryHelper, $class, $pageSize = self::DEFAULT_PAGESIZE)
    {
        if($pageSize < 1) {
            throw new InvalidArgumentException('Page size must be greater than 0');
        }

        $this->qb = $qb;
        $this->class = $class;
        $this->pageSize = $pageSize;
        $this->queryHelper = $queryHelper;
        $this->resultCount = $queryHelper->getCount($qb);
        $this->pages = floor( $this->resultCount / $this->pageSize) + 1;
    }

    /**
     * @param int $page Starts at 1
     * @param bool $allResults
     * @return DataObjectInterface[]
     */
    public function getResults($page, $allResults = false)
    {
        if($page < 1 || $page > $this->getPages()) {
            throw new InvalidArgumentException("Page must be between 1 and {$this->getPages()}");
        }

        if(!$allResults) {
            $this->page = $page;
            $this->prev = $page > 1 ? $page - 1: 0;
            $this->next = $page < $this->pages ? $page + 1 : 0;

            $this->qb->setMaxResults($this->pageSize)
                ->setFirstResult(($page-1) * $this->pageSize);
        }

        $statement = $this->qb->execute();
        return $statement->fetchAll(\PDO::FETCH_CLASS, $this->class);
    }

    /**
     * Get the total number of result pages
     *
     * @return int
     */
    public function getPages()
    {
        return $this->pages;
    }

    function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['qb'], $vars['class'], $vars['queryHelper']);
        return (object) $vars;
    }
}