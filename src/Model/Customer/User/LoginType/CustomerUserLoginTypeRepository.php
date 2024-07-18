<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Customer\User\LoginType;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;

class CustomerUserLoginTypeRepository
{
    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @param string $loginType
     * @return \Shopsys\FrontendApiBundle\Model\Customer\User\LoginType\CustomerUserLoginType|null
     */
    public function findByCustomerUserAndType(
        CustomerUser $customerUser,
        string $loginType,
    ): ?CustomerUserLoginType {
        return $this->entityManager->getRepository(CustomerUserLoginType::class)
            ->findOneBy([
                'customerUser' => $customerUser,
                'loginType' => $loginType,
            ]);
    }
}
