<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Transport;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Store\StoreFacade;
use Shopsys\FrameworkBundle\Model\Transport\Exception\TransportPriceNotFoundException;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceProvider;
use Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation;
use Shopsys\FrontendApiBundle\Model\Cart\CartApiFacade;
use Shopsys\FrontendApiBundle\Model\Transport\Exception\InvalidTransportPaymentCombinationException;
use Shopsys\FrontendApiBundle\Model\Transport\Exception\MissingPickupPlaceIdentifierException;
use Shopsys\FrontendApiBundle\Model\Transport\Exception\TransportPriceChangedException;
use Shopsys\FrontendApiBundle\Model\Transport\Exception\TransportUnavailableForProductsInCartException;
use Shopsys\FrontendApiBundle\Model\Transport\Exception\TransportWeightLimitExceededException;

class TransportValidationFacade
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Store\StoreFacade $storeFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrontendApiBundle\Model\Cart\CartApiFacade $cartApiFacade
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceProvider $transportPriceProvider
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation $transportVisibilityCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFacade $transportPriceFacade
     */
    public function __construct(
        protected readonly StoreFacade $storeFacade,
        protected readonly Domain $domain,
        protected readonly CurrentCustomerUser $currentCustomerUser,
        protected readonly CartApiFacade $cartApiFacade,
        protected readonly TransportPriceProvider $transportPriceProvider,
        protected readonly TransportVisibilityCalculation $transportVisibilityCalculation,
        protected readonly TransportPriceFacade $transportPriceFacade,
    ) {
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param string|null $pickupPlaceIdentifier
     */
    public function checkPersonalPickupStoreAvailability(Transport $transport, ?string $pickupPlaceIdentifier): void
    {
        if ($pickupPlaceIdentifier === null || $transport->isPacketery()) {
            return;
        }

        $this->storeFacade->getByUuidAndDomainId(
            $pickupPlaceIdentifier,
            $this->domain->getId(),
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     */
    public function checkTransportWeightLimit(Transport $transport, Cart $cart): void
    {
        try {
            $this->transportPriceFacade->getTransportPriceOnDomainByTransportAndClosestWeight(
                $this->domain->getId(),
                $transport,
                $cart->getTotalWeight(),
            );
        } catch (TransportPriceNotFoundException) {
            throw new TransportWeightLimitExceededException();
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     */
    public function checkTransportAvailabilityForProductsInCart(Transport $transport, Cart $cart): void
    {
        if ($this->transportVisibilityCalculation->filterTransportsByProductsInCart([$transport], $cart) === []) {
            throw new TransportUnavailableForProductsInCartException();
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     */
    public function checkTransportPriceAndWeightLimit(Transport $transport, Cart $cart): void
    {
        $calculatedTransportPrice = $this->transportPriceProvider->getTransportPrice(
            $cart,
            $transport,
            $this->domain->getCurrentDomainConfig(),
        );

        $transportWatchedPrice = $cart->getTransportWatchedPrice();

        if ($transportWatchedPrice === null || !$calculatedTransportPrice->getPriceWithVat()->equals($transportWatchedPrice)) {
            throw new TransportPriceChangedException($calculatedTransportPrice);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param string|null $pickupPlaceIdentifier
     */
    public function checkRequiredPickupPlaceIdentifier(Transport $transport, ?string $pickupPlaceIdentifier): void
    {
        if (($transport->isPersonalPickup() || $transport->isPacketery()) && $pickupPlaceIdentifier === null) {
            throw new MissingPickupPlaceIdentifierException();
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param string|null $cartUuid
     */
    public function checkTransportPaymentRelation(Transport $transport, ?string $cartUuid): void
    {
        $customerUser = $this->currentCustomerUser->findCurrentCustomerUser();
        $cart = $this->cartApiFacade->getCartCreateIfNotExists($customerUser, $cartUuid);
        $payment = $cart->getPayment();

        if ($payment === null || in_array($payment, $transport->getPayments(), true)) {
            return;
        }

        throw new InvalidTransportPaymentCombinationException();
    }
}
