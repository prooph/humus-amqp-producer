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

use Humus\Amqp\Producer;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\ServiceBus\EventBus;

final class TransactionalEventPublisher implements Plugin
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

    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPost']);
    }

    public function onEventStoreCommitPost(ActionEvent $actionEvent)
    {
        $this->queuedActionEvents[] = $actionEvent;

        if (! $this->inTransaction) {
            $this->inTransaction = true;

            while ($actionEvent = array_shift($this->queuedActionEvents)) {
                $fallback = new \ArrayIterator();
                $recordedEvents = $actionEvent->getParam('recordedEvents', $fallback);

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
