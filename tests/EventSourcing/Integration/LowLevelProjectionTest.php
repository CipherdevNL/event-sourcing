<?php


namespace Test\Ecotone\EventSourcing\Integration;


use Ecotone\EventSourcing\Event;
use Ecotone\EventSourcing\LazyProophEventStore;
use Ecotone\EventSourcing\LazyProophProjectionManager;
use Ecotone\EventSourcing\ProophMessage;
use Ecotone\EventSourcing\EcotoneEventStoreProophWrapper;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\StreamName;
use Test\Ecotone\EventSourcing\EventSourcingMessagingTest;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;

class LowLevelProjectionTest extends EventSourcingMessagingTest
{
    public function __test_building_projection()
    {
        $eventStore = EcotoneEventStoreProophWrapper::prepareWithNoConversions(LazyProophEventStore::startWithDefaults($this->getConnectionFactory(), LazyProophEventStore::SINGLE_STREAM_PERSISTENCE));
        $projectionManager = new LazyProophProjectionManager($eventStore);

        $eventName = "ticketWasRegistered";
        $ticketWasRegisteredEvent = Event::createWithType($eventName, ["ticketId" => 1], [LazyProophEventStore::AGGREGATE_ID => 1, LazyProophEventStore::AGGREGATE_VERSION => 1, LazyProophEventStore::AGGREGATE_TYPE => Ticket::class]);
        $eventStore->appendTo("tickets", [$ticketWasRegisteredEvent]);

        $projectionManager
            ->createProjection("tickets_list")
            ->fromStream("tickets")
            ->when([
                $eventName => function($state, ProophMessage $event) {
                    var_dump($event);
                    die("test");
                }
            ])
            ->run(false);
    }
}