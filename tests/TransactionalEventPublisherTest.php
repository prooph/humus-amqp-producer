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

namespace ProophTest\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\Producer;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\HumusAmqp\TransactionalEventPublisher;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Prophecy\Argument;

/**
 * Class TransactionalEventPublisherTest
 * @package ProophTest\ServiceBus\Message\HumusAmqp
 */
class TransactionalEventPublisherTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_up_event_store()
    {
        $eventBus = $this->prophesize(EventBus::class);
        $producer = $this->prophesize(Producer::class);

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());

        $actionEventEmitter = $this->prophesize(ActionEventEmitter::class);
        $actionEventEmitter->attachListener('commit.post', [$plugin, 'onEventStoreCommitPost'])->shouldBeCalled();

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->getActionEventEmitter()->willReturn($actionEventEmitter->reveal())->shouldBeCalled();

        $plugin->setUp($eventStore->reveal());
    }

    /**
     * @test
     */
    public function it_starts_transactions_and_commits_on_event_store_commit_post()
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $iterator = new \ArrayIterator(['foo', 'bar']);
        $actionEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn($iterator)->shouldBeCalled();

        $eventBus = $this->prophesize(EventBus::class);
        $eventBus->dispatch('foo')->shouldBeCalled();
        $eventBus->dispatch('bar')->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalled();
        $producer->commitTransaction()->shouldBeCalled();

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal(), 2.0);
        $plugin->onEventStoreCommitPost($actionEvent->reveal());
    }

    /**
     * @test
     */
    public function it_confirms_select_one_action_event_after_the_other()
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $iterator = new \ArrayIterator(['foo', 'bar']);
        $actionEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn($iterator)->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalledTimes(2);
        $producer->commitTransaction()->shouldBeCalledTimes(2);

        $eventBus = new EventBus();

        $plugin = new TransactionalEventPublisher($eventBus, $producer->reveal());

        $eventBusCalls = [];

        $eventRouter = new EventRouter();
        $eventRouter->route('foo')->to(function ($event) use ($plugin, &$eventBusCalls) {
            $eventBusCalls[] = $event;
            $actionEvent = new DefaultActionEvent($event, null, [
                'recordedEvents' => new \ArrayIterator(['baz', 'bam', 'bat'])
            ]) ;
            $plugin->onEventStoreCommitPost($actionEvent);
        });

        $eventRouter->route('bar')->to(function ($event) use (&$eventBusCalls) {
            $eventBusCalls[] = $event;
        });
        $eventRouter->route('baz')->to(function ($event) use (&$eventBusCalls) {
            $eventBusCalls[] = $event;
        });
        $eventRouter->route('bam')->to(function ($event) use (&$eventBusCalls) {
            $eventBusCalls[] = $event;
        });
        $eventRouter->route('bat')->to(function ($event) use (&$eventBusCalls) {
            $eventBusCalls[] = $event;
        });

        $eventBus->utilize($eventRouter);

        $plugin->onEventStoreCommitPost($actionEvent->reveal());

        $this->assertEquals(
            [
                'foo',
                'bar',
                'baz',
                'bam',
                'bat'
            ],
            $eventBusCalls
        );
    }

    /**
     * @test
     */
    public function it_does_nothing_when_no_recorded_events()
    {
        $actionEvent = new DefaultActionEvent('name');

        $eventBus = $this->prophesize(EventBus::class);
        $eventBus->dispatch(Argument::any())->shouldNotBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldNotBeCalled();
        $producer->commitTransaction()->shouldNotBeCalled();

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());
        $plugin->onEventStoreCommitPost($actionEvent);
    }
}
