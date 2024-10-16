<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Security;

use Override;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory;
use Shopsys\FrameworkBundle\Controller\Admin\LoginController;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Security\LoginAdministratorAsUserUrlProvider as BaseLoginAdministratorAsUserUrlProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginAdministratorAsUserUrlProvider extends BaseLoginAdministratorAsUserUrlProvider
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory
     */
    public function __construct(
        protected readonly DomainRouterFactory $domainRouterFactory,
    ) {
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @return string
     */
    #[Override]
    public function getSsoLoginAsCustomerUserUrl(CustomerUser $customerUser): string
    {
        $customerDomainRouter = $this->domainRouterFactory->getRouter($customerUser->getDomainId());
        $loginAsUserUrl = $customerDomainRouter->generate(
            'admin_customeruser_loginascustomeruser',
            [
                'customerUserId' => $customerUser->getId(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $mainAdminDomainRouter = $this->domainRouterFactory->getRouter(Domain::MAIN_ADMIN_DOMAIN_ID);

        return $mainAdminDomainRouter->generate(
            'admin_login_sso',
            [
                LoginController::ORIGINAL_DOMAIN_ID_PARAMETER_NAME => $customerUser->getDomainId(),
                LoginController::ORIGINAL_REFERER_PARAMETER_NAME => $loginAsUserUrl,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
