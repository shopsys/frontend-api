<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Order;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\String\DatabaseSearching;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;

class OrderItemApiFacade
{
    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(
        protected readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param string[] $uuids
     * @return array<string, \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem>
     */
    public function findMappedByUuid(array $uuids): array
    {
        return $this->createOrderItemQueryBuilder()
            ->andWhere('oi.uuid IN (:uuids)')->setParameter(':uuids', $uuids)
            ->indexBy('oi', 'oi.uuid')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createOrderItemQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('oi')
            ->from(OrderItem::class, 'oi');
    }

    /**
     * @param string $search
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @param int $limit
     * @param int $offset
     * @param \Shopsys\FrontendApiBundle\Model\Order\OrderItemsFilter $filter
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem[]
     */
    public function getCustomerUserOrderItemsLimitedSearchList(
        string $search,
        CustomerUser $customerUser,
        int $limit,
        int $offset,
        OrderItemsFilter $filter,
    ): array {
        return $this->createCustomerUserOrderItemsLimitedSearchListQueryBuilder($customerUser, $search, $filter)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $search
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @param \Shopsys\FrontendApiBundle\Model\Order\OrderItemsFilter $filter
     * @return int
     */
    public function getCustomerUserOrderItemsLimitedSearchListCount(
        string $search,
        CustomerUser $customerUser,
        OrderItemsFilter $filter,
    ): int {
        return $this->createCustomerUserOrderItemsLimitedSearchListQueryBuilder($customerUser, $search, $filter)
            ->select('count(oi.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @param string $search
     * @param \Shopsys\FrontendApiBundle\Model\Order\OrderItemsFilter $filter
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createCustomerUserOrderItemsLimitedSearchListQueryBuilder(
        CustomerUser $customerUser,
        string $search,
        OrderItemsFilter $filter,
    ): QueryBuilder {
        $queryBuilder = $this->createOrderItemQueryBuilder()
            ->join('oi.order', 'o')
            ->andWhere('o.customerUser = :customerUser');

        $queryBuilder->setParameter(':customerUser', $customerUser)
            ->andWhere(
                $this->em->getExpressionBuilder()->orX(
                    'NORMALIZED(oi.name) LIKE NORMALIZED(:search)',
                    'NORMALIZED(oi.catnum) LIKE NORMALIZED(:search)',
                    'NORMALIZED(o.number) LIKE NORMALIZED(:search)',
                ),
            )
            ->setParameter(':search', DatabaseSearching::getFullTextLikeSearchString($search));

        $this->applyOrderItemsFilterToQueryBuilder($filter, $queryBuilder);

        return $queryBuilder;
    }

    /**
     * @param \Shopsys\FrontendApiBundle\Model\Order\OrderItemsFilter $filter
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    protected function applyOrderItemsFilterToQueryBuilder(OrderItemsFilter $filter, QueryBuilder $queryBuilder): void
    {
        if ($filter->getOrderUuid() !== null) {
            $queryBuilder->andWhere('o.uuid = :orderUuid')
                ->setParameter(':orderUuid', $filter->getOrderUuid());
        }

        if ($filter->getOrderCreatedAfter() !== null) {
            $queryBuilder->andWhere('o.createdAt >= :orderCreatedAfter')
                ->setParameter(':orderCreatedAfter', $filter->getOrderCreatedAfter());
        }

        if ($filter->getOrderStatus() !== null) {
            $queryBuilder->andWhere('o.status = :orderStatus')
                ->setParameter(':orderStatus', $filter->getOrderStatus());
        }

        if ($filter->getType() !== null) {
            $queryBuilder->andWhere('oi.type = :type')
                ->setParameter(':type', $filter->getType());
        }

        $orX = [];

        if ($filter->getCatnum() !== null) {
            $orX[] = 'oi.catnum = :catnum';
            $queryBuilder->setParameter(':catnum', $filter->getCatnum());
        }

        if ($filter->getProductUuid() !== null) {
            $orX[] = 'p.uuid = :productUuid';
            $queryBuilder->leftJoin('oi.product', 'p')
                ->setParameter(':productUuid', $filter->getProductUuid());
        }

        if (count($orX) > 1) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orX));
        } elseif (count($orX) > 0) {
            $queryBuilder->andWhere(reset($orX));
        }
    }
}
