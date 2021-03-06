<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter\YamlConverter;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class OrmDatasource implements DatasourceInterface
{
    const TYPE = 'orm';

    /** @var QueryBuilder */
    protected $qb;

    /** @var EntityManager */
    protected $em;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(EntityManager $em, AclHelper $aclHelper)
    {
        $this->em = $em;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        if (isset($config['query'])) {
            $queryConfig = array_intersect_key($config, array_flip(['query']));
            $converter = new YamlConverter();
            $this->qb  = $converter->parse($queryConfig, $this->em->createQueryBuilder());

        } elseif (isset($config['entity']) and isset($config['repository_method'])) {
            $entity = $config['entity'];
            $method = $config['repository_method'];
            $repository = $this->em->getRepository($entity);
            if (method_exists($repository, $method)) {
                $qb = $repository->$method();
                if ($qb instanceof QueryBuilder) {
                    $this->qb = $qb;
                } else {
                    throw new \Exception(
                        sprintf(
                            '%s::%s() must return an instance of Doctrine\ORM\QueryBuilder, %s given',
                            get_class($repository),
                            $method,
                            is_object($qb) ? get_class($qb) : gettype($qb)
                        )
                    );
                }
            } else {
                throw new \Exception(sprintf('%s has no method %s', get_class($repository), $method));
            }

        } else {
            throw new \Exception(get_class($this).' expects to be configured with query or repository method');
        }

        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $results = $this->getResultQuery()->execute();
        $rows    = [];
        foreach ($results as $result) {
            $rows[] = new ResultRecord($result);
        }

        return $rows;
    }

    /**
     * Returns query is used to retrieve grid data
     *
     * @return Query
     */
    public function getResultQuery()
    {
        return $this->aclHelper->apply($this->qb->getQuery());
    }

    /**
     * Returns query builder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * Set QueryBuilder
     *
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;

        return $this;
    }
}
