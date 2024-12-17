<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Cart;

class PromoCodeData
{
    /**
     * @param string $code
     * @param string $type
     */
    public function __construct(
        public readonly string $code,
        public readonly string $type,
    ) {
    }
}
