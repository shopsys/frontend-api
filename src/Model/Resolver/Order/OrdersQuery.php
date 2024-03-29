<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Resolver\Order;

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrontendApiBundle\Model\Order\OrderApiFacade;
use Shopsys\FrontendApiBundle\Model\Resolver\AbstractQuery;
use Shopsys\FrontendApiBundle\Model\Token\Exception\InvalidTokenUserMessageException;

class OrdersQuery extends AbstractQuery
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrontendApiBundle\Model\Order\OrderApiFacade $orderApiFacade
     */
    public function __construct(
        protected readonly CurrentCustomerUser $currentCustomerUser,
        protected readonly OrderApiFacade $orderApiFacade,
    ) {
    }

    /**
     * @param \Overblog\GraphQLBundle\Definition\Argument $argument
     * @return \Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface|object
     */
    public function ordersQuery(Argument $argument)
    {
        $this->setDefaultFirstOffsetIfNecessary($argument);

        $customerUser = $this->currentCustomerUser->findCurrentCustomerUser();

        if (!$customerUser) {
            throw new InvalidTokenUserMessageException();
        }

        $paginator = new Paginator(function ($offset, $limit) use ($customerUser) {
            return $this->orderApiFacade->getCustomerUserOrderLimitedList($customerUser, $limit, $offset);
        });

        return $paginator->auto($argument, $this->orderApiFacade->getCustomerUserOrderCount($customerUser));
    }
}
