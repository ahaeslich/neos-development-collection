<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::findClosestNode()}
 *
 * Example:
 *
 * FindClosestAncestorNodeFilter::create(nodeTypes: 'Some.Included:NodeType,!Some.Excluded:NodeType');
 *
 * @api for the factory methods; NOT for the inner state.
 */
final readonly class FindClosestNodeFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public NodeTypeCriteria $nodeTypes,
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        NodeTypeCriteria|string $nodeTypes,
    ): self {
        if (is_string($nodeTypes)) {
            $nodeTypes = NodeTypeCriteria::fromFilterString($nodeTypes);
        }
        return new self($nodeTypes);
    }
}
