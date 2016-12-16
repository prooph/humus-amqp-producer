<?php
/*
 * This file is part of the prooph/humus-amqp-producer.
 * (c) 2016 prooph software GmbH <contact@prooph.de>
 * (c) 2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
use Prooph\ServiceBus\Exception\RuntimeException;

final class ConfirmSelectEventPublisher implements Plugin
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
     * @var float
     */
    private $timeout;

    /**
     * @var ActionEvent[]
     */
    private $queuedActionEvents = [];

    /**
     * @var bool
     */
    private $inConfirmSelectMode = false;

    public function __construct(EventBus $eventBus, Producer $producer, float $timeout = 2.0)
    {
        $this->eventBus = $eventBus;
        $this->producer = $producer;
        $this->timeout = $timeout;
    }

    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPost']);
    }

    public function onEventStoreCommitPost(ActionEvent $actionEvent)
    {
        $this->queuedActionEvents[] = $actionEvent;

        if (! $this->inConfirmSelectMode) {
            $this->inConfirmSelectMode = true;

            while ($actionEvent = array_shift($this->queuedActionEvents)) {
                $fallback = new \ArrayIterator();
                $recordedEvents = $actionEvent->getParam('recordedEvents', $fallback);

                if ($fallback !== $recordedEvents) {
                    $this->producer->confirmSelect();
                }

                $countRecordedEvents = 0;

                foreach ($recordedEvents as $recordedEvent) {
                    $this->eventBus->dispatch($recordedEvent);
                    $countRecordedEvents++;
                }

                if ($fallback !== $recordedEvents) {
                    $this->producer->setConfirmCallback(
                        function (int $deliveryTag, bool $multiple) use ($countRecordedEvents) {
                            return ($deliveryTag !== $countRecordedEvents);
                        },
                        function (int $deliveryTag, bool $multiple, bool $requeue) use (&$result) {
                            throw new RuntimeException('Could not publish all events');
                        }
                    );

                    $this->producer->waitForConfirm($this->timeout);
                };
            }

            $this->inConfirmSelectMode = false;
        }
    }
}
