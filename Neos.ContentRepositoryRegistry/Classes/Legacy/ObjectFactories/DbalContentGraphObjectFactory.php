<?php

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;

class DbalContentGraphObjectFactory
{
    public function __construct(
        protected readonly DbalClientInterface $dbalClient
    ) {}

    public function buildProjectionContentGraph(): ProjectionContentGraph {
        return new ProjectionContentGraph($this->dbalClient);
    }
}
