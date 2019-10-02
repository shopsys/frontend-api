<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Routing;

use Overblog\GraphiQLBundle\Controller\GraphiQLController;
use Overblog\GraphQLBundle\Controller\GraphController;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Domain\Exception\NoDomainSelectedException;
use Shopsys\FrameworkBundle\Component\Environment\EnvironmentType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FrontendApiRouteLoader
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var int[]
     */
    protected $enabledDomainIds;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param string $environment
     * @param int[] $enabledDomainIds
     */
    public function __construct(Domain $domain, string $environment, array $enabledDomainIds = [])
    {
        $this->domain = $domain;
        $this->environment = $environment;
        $this->enabledDomainIds = $enabledDomainIds;
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function loadRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        if (!$this->isEnabledOnCurrentDomain()) {
            return $routes;
        }

        $routes->add('overblog_graphql_endpoint', new Route(
            '/',
            [
                '_controller' => GraphController::class . '::endpointAction',
                '_format' => 'json',
            ]
        ));

        $routes->add('overblog_graphql_batch_endpoint', new Route(
            '/batch',
            [
                '_controller' => GraphController::class . '::batchEndpointAction',
                '_format' => 'json',
            ]
        ));

        if ($this->environment === EnvironmentType::DEVELOPMENT) {
            $routes->add('overblog_graphiql_endpoint', new Route(
                '/graphiql',
                [
                    '_controller' => GraphiQLController::class . '::indexAction',
                    '_format' => 'json',
                ]
            ));

            $routes->add('overblog_graphiql_endpoint_multiple', new Route(
                '/graphiql/{schemaName}',
                [
                    '_controller' => GraphiQLController::class . '::indexAction',
                    '_format' => 'json',
                ]
            ));
        }

        return $routes;
    }

    /**
     * @return bool
     */
    public function isEnabledOnCurrentDomain(): bool
    {
        try {
            $checkedDomainId = $this->domain->getId();
        } catch (NoDomainSelectedException $exception) {
            $checkedDomainId = Domain::MAIN_ADMIN_DOMAIN_ID;
        }

        return in_array($checkedDomainId, $this->enabledDomainIds, true);
    }
}
