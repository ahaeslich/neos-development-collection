<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The workspace creation feature trait for behavioral tests
 */
trait WorkspaceCreation
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the event RootWorkspaceWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventRootWorkspaceWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $newContentStreamId = ContentStreamId::fromString($eventPayload['newContentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($newContentStreamId);
        $this->publishEvent('RootWorkspaceWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }
}
