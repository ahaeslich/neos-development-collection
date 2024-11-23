<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Service\SubscriptionService;
use Neos\ContentRepository\Core\Service\SubscriptionServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionAndProjectionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\TestSuite\Fakes\FakeCatchUpHookFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractSubscriptionEngineTestCase extends TestCase // we don't use Flows functional test case as it would reset the database afterwards
{
    protected ContentRepository $contentRepository;

    protected SubscriptionService $subscriptionService;

    protected SubscriptionEngine $subscriptionEngine;

    protected EventStoreInterface $eventStore;

    protected ProjectionInterface&MockObject $fakeProjection;

    protected DebugEventProjection $secondFakeProjection;

    protected CatchUpHookInterface&MockObject $catchupHookForFakeProjection;

    public function setUp(): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString('t_subscription');

        $this->resetDatabase(
            $this->getObject(Connection::class),
            $contentRepositoryId,
            keepSchema: true
        );

        $this->fakeProjection = $this->getMockBuilder(ProjectionInterface::class)->disableAutoReturnValueGeneration()->getMock();
        $this->fakeProjection->method('getState')->willReturn(new class implements ProjectionStateInterface {});

        FakeProjectionFactory::setProjection(
            'default',
            $this->fakeProjection
        );

        $this->secondFakeProjection = new DebugEventProjection(
            'cr_t_subscription_debug_projection',
            $this->getObject(Connection::class)
        );

        FakeProjectionFactory::setProjection(
            'second',
            $this->secondFakeProjection
        );

        $this->catchupHookForFakeProjection = $this->getMockBuilder(CatchUpHookInterface::class)->getMock();

        FakeCatchUpHookFactory::setCatchupHook(
            $this->secondFakeProjection->getState(),
            $this->catchupHookForFakeProjection
        );

        FakeNodeTypeManagerFactory::setConfiguration([]);
        FakeContentDimensionSourceFactory::setWithoutDimensions();

        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance($contentRepositoryId);
        $originalSettings = $this->getObject(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepositoryRegistry');
        $this->getObject(ContentRepositoryRegistry::class)->injectSettings($originalSettings);

        $this->setupContentRepositoryDependencies($contentRepositoryId);
    }

    final protected function setupContentRepositoryDependencies(ContentRepositoryId $contentRepositoryId)
    {
        $this->contentRepository = $this->getObject(ContentRepositoryRegistry::class)->get(
            $contentRepositoryId
        );

        $this->subscriptionService = $this->getObject(ContentRepositoryRegistry::class)->buildService($contentRepositoryId, new SubscriptionServiceFactory());

        $subscriptionEngineAndEventStoreAccessor = new class implements ContentRepositoryServiceFactoryInterface {
            public EventStoreInterface|null $eventStore;
            public SubscriptionEngine|null $subscriptionEngine;
            public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
            {
                $this->eventStore = $serviceFactoryDependencies->eventStore;
                $this->subscriptionEngine = $serviceFactoryDependencies->subscriptionEngine;
                return new class implements ContentRepositoryServiceInterface
                {
                };
            }
        };
        $this->getObject(ContentRepositoryRegistry::class)->buildService($contentRepositoryId, $subscriptionEngineAndEventStoreAccessor);
        $this->eventStore = $subscriptionEngineAndEventStoreAccessor->eventStore;
        $this->subscriptionEngine = $subscriptionEngineAndEventStoreAccessor->subscriptionEngine;
    }

    final protected function resetDatabase(Connection $connection, ContentRepositoryId $contentRepositoryId, bool $keepSchema): void
    {
        $connection->prepare('SET FOREIGN_KEY_CHECKS = 0;')->executeStatement();
        foreach ($connection->createSchemaManager()->listTableNames() as $tableNames) {
            if (!str_starts_with($tableNames, sprintf('cr_%s_', $contentRepositoryId->value))) {
                // speedup deletion, only delete current cr
                continue;
            }
            if ($keepSchema) {
                // truncate is faster
                $sql = 'TRUNCATE TABLE ' . $tableNames;
            } else {
                $sql = 'DROP TABLE ' . $tableNames;
            }
            $connection->prepare($sql)->executeStatement();
        }
        $connection->prepare('SET FOREIGN_KEY_CHECKS = 1;')->executeStatement();
    }

    final protected function subscriptionStatus(string $subscriptionId): ?SubscriptionAndProjectionStatus
    {
        return $this->subscriptionService->subscriptionEngine->subscriptionStatuses(SubscriptionCriteria::create(ids: [SubscriptionId::fromString($subscriptionId)]))->first();
    }

    final protected function commitExampleContentStreamEvent(): void
    {
        $this->eventStore->commit(
            ContentStreamEventStreamName::fromContentStreamId($cs = ContentStreamId::create())->getEventStreamName(),
            new Event(
                Event\EventId::create(),
                Event\EventType::fromString('ContentStreamWasCreated'),
                Event\EventData::fromString(json_encode(['contentStreamId' => $cs->value]))
            ),
            ExpectedVersion::NO_STREAM()
        );
    }

    final protected function expectOkayStatus($subscriptionId, SubscriptionStatus $status, SequenceNumber $sequenceNumber): void
    {
        $actual = $this->subscriptionStatus($subscriptionId);
        self::assertEquals(
            SubscriptionAndProjectionStatus::create(
                subscriptionId: SubscriptionId::fromString($subscriptionId),
                subscriptionStatus: $status,
                subscriptionPosition: $sequenceNumber,
                subscriptionError: null,
                projectionStatus: ProjectionStatus::ok(),
            ),
            $actual
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    final protected function getObject(string $className): object
    {
        return Bootstrap::$staticObjectManager->get($className);
    }
}
