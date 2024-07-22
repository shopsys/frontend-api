<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Customer\User\LoginType;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;

class CustomerUserLoginTypeFacade
{
    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\FrontendApiBundle\Model\Customer\User\LoginType\CustomerUserLoginTypeFactory $customerUserLoginTypeFactory
     * @param \Shopsys\FrontendApiBundle\Model\Customer\User\LoginType\CustomerUserLoginTypeRepository $customerUserLoginTypeRepository
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly CustomerUserLoginTypeFactory $customerUserLoginTypeFactory,
        protected readonly CustomerUserLoginTypeRepository $customerUserLoginTypeRepository,
    ) {
    }

    /**
     * @param \Shopsys\FrontendApiBundle\Model\Customer\User\LoginType\CustomerUserLoginTypeData $customerUserLoginTypeData
     */
    public function updateCustomerUserLoginTypes(
        CustomerUserLoginTypeData $customerUserLoginTypeData,
    ): void {
        $existingCustomerUserLoginType = $this->customerUserLoginTypeRepository->findByCustomerUserAndType(
            $customerUserLoginTypeData->customerUser,
            $customerUserLoginTypeData->loginType,
        );

        if ($existingCustomerUserLoginType !== null) {
            $existingCustomerUserLoginType->setLastLoggedInAt($customerUserLoginTypeData->lastLoggedInAt);
            $this->entityManager->flush();

            return;
        }

        $newCustomerUserLoginType = $this->customerUserLoginTypeFactory->create($customerUserLoginTypeData);
        $this->entityManager->persist($newCustomerUserLoginType);

        $this->entityManager->flush();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @return \Shopsys\FrontendApiBundle\Model\Customer\User\LoginType\CustomerUserLoginType
     */
    public function getMostRecentLoginType(CustomerUser $customerUser): CustomerUserLoginType
    {
        return $this->customerUserLoginTypeRepository->getMostRecentLoginType($customerUser);
    }
}
