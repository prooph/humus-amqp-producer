<?php
/*
 * This file is part of the prooph/humus-amqp-producer.
 * (c) 2016 prooph software GmbH <contact@prooph.de>
 * (c) 2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Prooph\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\Producer;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;

/**
 * Class PublishTransactionalPlugin
 * @package Prooph\ServiceBus\Message\HumusAmqp
 */
final class PublishTransactionalPlugin implements Plugin
{
    /**
     * @var Producer
     */
    private $producer;

    /**
     * @param Producer $producer
     */
    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPostStartTransaction'], 1000);
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPostCommitTransaction'], -1000);
    }

    /**
     * Start confirm select
     *
     * @param ActionEvent $actionEvent
     */
    public function onEventStoreCommitPostStartTransaction(ActionEvent $actionEvent)
    {
        $this->producer->startTransaction();
    }

    /**
     * Publish recorded events on the event bus
     *
     * @param ActionEvent $actionEvent
     */
    public function onEventStoreCommitPostCommitTransaction(ActionEvent $actionEvent)
    {
        $this->producer->commitTransaction();
    }
}
