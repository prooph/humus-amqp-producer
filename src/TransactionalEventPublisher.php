<?php
/**
 * This file is part of the prooph/humus-amqp-producer.
 * (c) 2016-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\ServiceBus\Message\HumusAmqp;

use ArrayIterator;
use Humus\Amqp\Producer;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\AbstractPlugin;
use Prooph\EventStore\Stream;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\ServiceBus\EventBus;

final class TransactionalEventPublisher extends AbstractPlugin
{
    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var ActionEvent[]
     */
    private $queuedActionEvents = [];

    /**
     * @var bool
     */
    private $inTransaction = false;

    public function __construct(EventBus $eventBus, Producer $producer)
    {
        $this->eventBus = $eventBus;
        $this->producer = $producer;
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use ($eventStore): void {
                if (! $eventStore instanceof TransactionalActionEventEmitterEventStore) {
                    $this->onEventStoreCommitPost($event);
                } else {
                    $this->queuedActionEvents[] = $event;
                }
            }
        );
        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event) use ($eventStore): void {
                if (! $eventStore instanceof TransactionalActionEventEmitterEventStore) {
                    $this->onEventStoreCommitPost($event);
                } else {
                    $this->queuedActionEvents[] = $event;
                }
            }
        );
        if ($eventStore instanceof TransactionalActionEventEmitterEventStore) {
            $this->listenerHandlers[] = $eventStore->attach(
                TransactionalActionEventEmitterEventStore::EVENT_COMMIT,
                function (ActionEvent $event): void {
                    $this->onEventStoreCommitPost($event);
                    $this->queuedActionEvents = [];
                }
            );
            $this->listenerHandlers[] = $eventStore->attach(
                TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK,
                function (ActionEvent $event): void {
                    $this->queuedActionEvents = [];
                }
            );
        }
    }

    public function onEventStoreCommitPost(ActionEvent $actionEvent)
    {
        $this->queuedActionEvents[] = $actionEvent;

        if (! $this->inTransaction) {
            $this->inTransaction = true;

            while ($actionEvent = array_shift($this->queuedActionEvents)) {
                $fallback = new ArrayIterator();
                $recordedEvents = $actionEvent->getParam('stream');

                if ($recordedEvents instanceof Stream) {
                    // stream was created
                    if ($actionEvent->getParam('streamExistsAlready', false)) {
                        return;
                    }

                    $recordedEvents = $recordedEvents->streamEvents();
                } else {
                    // events were appended
                    if ($actionEvent->getParam('streamNotFound', false)
                        || $actionEvent->getParam('concurrencyException', false)
                    ) {
                        return;
                    }

                    $recordedEvents = $actionEvent->getParam('streamEvents', $fallback);
                }

                if ($fallback !== $recordedEvents) {
                    $this->producer->startTransaction();
                }

                foreach ($recordedEvents as $recordedEvent) {
                    $this->eventBus->dispatch($recordedEvent);
                }

                if ($fallback !== $recordedEvents) {
                    $this->producer->commitTransaction();
                }
            }

            $this->inTransaction = false;
        }
    }
}
