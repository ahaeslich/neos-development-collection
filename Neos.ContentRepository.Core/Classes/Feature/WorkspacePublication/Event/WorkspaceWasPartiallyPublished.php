<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceWasPartiallyPublished implements EventInterface
{
    public function __construct(
        /**
         * From which workspace have changes been partially published?
         */
        public WorkspaceName $sourceWorkspaceName,
        /**
         * The target workspace where the changes have been published to.
         */
        public WorkspaceName $targetWorkspaceName,
        /**
         * The new content stream for the $sourceWorkspaceName
         */
        public ContentStreamId $newSourceContentStreamId,
        /**
         * The old content stream, which contains ALL the data (discarded and non-discarded)
         */
        public ContentStreamId $previousSourceContentStreamId,
        public NodeAggregateIds $publishedNodes,
    ) {
    }

    public static function fromArray(array $values): self
    {
        $publishedNodes = [];
        foreach ($values['publishedNodes'] as $publishedNode) {
            if (is_array($publishedNode)) {
                // legacy case:
                $publishedNodes[] = $publishedNode['nodeAggregateId'];
                continue;
            }
            $publishedNodes[] = $publishedNode;
        }
        return new self(
            WorkspaceName::fromString($values['sourceWorkspaceName']),
            WorkspaceName::fromString($values['targetWorkspaceName']),
            ContentStreamId::fromString($values['newSourceContentStreamId']),
            ContentStreamId::fromString($values['previousSourceContentStreamId']),
            NodeAggregateIds::fromArray($publishedNodes),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
