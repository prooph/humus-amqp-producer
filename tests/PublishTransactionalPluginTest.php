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

namespace ProophTest\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\Producer;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\EventStore;
use Prooph\ServiceBus\Message\HumusAmqp\PublishTransactionalPlugin;

/**
 * Class PublishTransactionalPluginTest
 * @package ProophTest\ServiceBus\Message\HumusAmqp
 */
class PublishTransactionalPluginTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_up_event_store()
    {
        $producer = $this->prophesize(Producer::class);

        $plugin = new PublishTransactionalPlugin($producer->reveal());

        $actionEventEmitter = $this->prophesize(ActionEventEmitter::class);
        $actionEventEmitter->attachListener('commit.post', [$plugin, 'onEventStoreCommitPostStartTransaction'], 1000)->shouldBeCalled();
        $actionEventEmitter->attachListener('commit.post', [$plugin, 'onEventStoreCommitPostCommitTransaction'], -1000)->shouldBeCalled();

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->getActionEventEmitter()->willReturn($actionEventEmitter->reveal())->shouldBeCalled();

        $plugin->setUp($eventStore->reveal());
    }

    /**
     * @test
     */
    public function it_starts_transactions_and_commits_on_event_store_commit_post()
    {
        $actionEvent = $this->prophesize(ActionEvent::class)->reveal();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalled();
        $producer->commitTransaction()->shouldBeCalled();

        $plugin = new PublishTransactionalPlugin($producer->reveal());
        $plugin->onEventStoreCommitPostStartTransaction($actionEvent);
        $plugin->onEventStoreCommitPostCommitTransaction($actionEvent);
    }
}
