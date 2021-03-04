<?php

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\Handler\ReferenceSearchService;
use Prooph\EventStore\Pdo\Projection\MariaDbProjectionManager;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

class LazyProophProjectionManager implements ProjectionManager
{
    private EcotoneEventStoreProophWrapper $eventStore;
    private ?ProjectionManager $lazyInitializedProjectionManager = null;
    private EventSourcingConfiguration $eventSourcingConfiguration;
    private ReferenceSearchService $referenceSearchService;

    public function __construct(EventSourcingConfiguration $eventSourcingConfiguration, ReferenceSearchService $referenceSearchService)
    {
        $this->eventSourcingConfiguration = $eventSourcingConfiguration;
        $this->referenceSearchService = $referenceSearchService;
    }

    private function getProjectionManager() : ProjectionManager
    {
        if ($this->lazyInitializedProjectionManager) {
            return $this->lazyInitializedProjectionManager;
        }

        $eventStore = new LazyProophEventStore($this->eventSourcingConfiguration, $this->referenceSearchService);

        $this->lazyInitializedProjectionManager = match ($eventStore->getEventStoreType()) {
            LazyProophEventStore::EVENT_STORE_TYPE_POSTGRES => new PostgresProjectionManager($eventStore->getEventStore(), $eventStore->getWrappedConnection(), $this->eventSourcingConfiguration->getEventStreamTableName(), $this->eventSourcingConfiguration->getProjectionsTable()),
            LazyProophEventStore::EVENT_STORE_TYPE_MYSQL => new PostgresProjectionManager($eventStore->getEventStore(), $eventStore->getWrappedConnection(), $this->eventSourcingConfiguration->getEventStreamTableName(), $this->eventSourcingConfiguration->getProjectionsTable()),
            LazyProophEventStore::EVENT_STORE_TYPE_MARIADB => new MariaDbProjectionManager($eventStore->getEventStore(), $eventStore->getWrappedConnection(), $this->eventSourcingConfiguration->getEventStreamTableName(), $this->eventSourcingConfiguration->getProjectionsTable())
        };

        return $this->lazyInitializedProjectionManager;
    }

    public function createQuery(): Query
    {
        return $this->getProjectionManager()->createQuery();
    }

    public function createProjection(string $name, array $options = []): Projector
    {
        return $this->getProjectionManager()->createProjection($name, $options);
    }

    public function createReadModelProjection(string $name, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        return $this->getProjectionManager()->createReadModelProjection($name, $readModel, $options);
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        $this->getProjectionManager()->deleteProjection($name, $deleteEmittedEvents);
    }

    public function resetProjection(string $name): void
    {
        $this->getProjectionManager()->resetProjection($name);
    }

    public function stopProjection(string $name): void
    {
        $this->getProjectionManager()->stopProjection($name);
    }

    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        $this->getProjectionManager()->fetchProjectionNames($filter, $limit, $offset);
    }

    public function fetchProjectionNamesRegex(string $regex, int $limit = 20, int $offset = 0): array
    {
        return $this->getProjectionManager()->fetchProjectionNamesRegex($regex, $limit, $offset);
    }

    public function fetchProjectionStatus(string $name): ProjectionStatus
    {
        return $this->getProjectionManager()->fetchProjectionStatus($name);
    }

    public function fetchProjectionStreamPositions(string $name): array
    {
        return $this->getProjectionManager()->fetchProjectionStreamPositions($name);
    }

    public function fetchProjectionState(string $name): array
    {
        return $this->getProjectionManager()->fetchProjectionState($name);
    }
}