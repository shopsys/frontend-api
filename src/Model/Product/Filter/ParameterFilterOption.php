<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;

class ParameterFilterOption
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @param \Shopsys\FrontendApiBundle\Model\Product\Filter\ParameterValueFilterOption[] $values
     */
    public function __construct(
        public readonly Parameter $parameter,
        public readonly array $values,
    ) {
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->parameter->getUuid();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->parameter->getName();
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->parameter->isVisible();
    }
}
