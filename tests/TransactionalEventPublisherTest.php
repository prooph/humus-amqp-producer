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

namespace ProophTest\ServiceBus\Message\HumusAmqp;

use ArrayIterator;
use Humus\Amqp\Producer;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConcurrencyException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\EventStore\TransactionalEventStore;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\HumusAmqp\TransactionalEventPublisher;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use ProophTest\EventStore\Mock\TestDomainEvent;
use Prophecy\Argument;

class TransactionalEventPublisherTest extends TestCase
{
    /**
     * @test
     */
    public function it_starts_transactions_and_commits_on_event_store_commit_post(): void
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $iterator = new \ArrayIterator(['foo', 'bar']);
        $actionEvent->getParam('stream')->willReturn(null)->shouldBeCalled();
        $actionEvent->getParam('streamEvents', new ArrayIterator())->willReturn($iterator)->shouldBeCalled();
        $actionEvent->getParam('streamNotFound', false)->willReturn(null)->shouldBeCalled();
        $actionEvent->getParam('concurrencyException', false)->willReturn(null)->shouldBeCalled();

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
    public function it_confirms_select_one_action_event_after_the_other(): void
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $iterator = new \ArrayIterator(['foo', 'bar']);
        $actionEvent->getParam('stream')->willReturn(null)->shouldBeCalled();
        $actionEvent->getParam('streamEvents', new ArrayIterator())->willReturn($iterator)->shouldBeCalled();
        $actionEvent->getParam('streamNotFound', false)->willReturn(null)->shouldBeCalled();
        $actionEvent->getParam('concurrencyException', false)->willReturn(null)->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalledTimes(2);
        $producer->commitTransaction()->shouldBeCalledTimes(2);

        $eventBus = new EventBus();

        $plugin = new TransactionalEventPublisher($eventBus, $producer->reveal());

        $eventBusCalls = [];

        $eventRouter = new EventRouter();
        $eventRouter->route('foo')->to(function ($event) use ($plugin, &$eventBusCalls): void {
            $eventBusCalls[] = $event;
            $actionEvent = new DefaultActionEvent($event, null, [
                'streamEvents' => new \ArrayIterator(['baz', 'bam', 'bat']),
            ]);
            $plugin->onEventStoreCommitPost($actionEvent);
        });

        $eventRouter->route('bar')->to(function ($event) use (&$eventBusCalls): void {
            $eventBusCalls[] = $event;
        });
        $eventRouter->route('baz')->to(function ($event) use (&$eventBusCalls): void {
            $eventBusCalls[] = $event;
        });
        $eventRouter->route('bam')->to(function ($event) use (&$eventBusCalls): void {
            $eventBusCalls[] = $event;
        });
        $eventRouter->route('bat')->to(function ($event) use (&$eventBusCalls): void {
            $eventBusCalls[] = $event;
        });

        $eventRouter->attachToMessageBus($eventBus);

        $plugin->onEventStoreCommitPost($actionEvent->reveal());

        $this->assertEquals(
            [
                'foo',
                'bar',
                'baz',
                'bam',
                'bat',
            ],
            $eventBusCalls
        );
    }

    /**
     * @test
     */
    public function it_queues_events_appended_to_transactional_event_store(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);
        $eventStore = new TransactionalActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $streamName = new StreamName('test-stream');

        $eventBus = $this->prophesize(EventBus::class);
        $eventBus->dispatch(Argument::any())->shouldBeCalledTimes(2);

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalled();
        $producer->commitTransaction()->shouldBeCalled();

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());
        $plugin->attachToEventStore($eventStore);

        $eventStore->beginTransaction();

        $eventStore->create(
            new Stream($streamName, new ArrayIterator([new TestDomainEvent(['foo' => 'bar'])]))
        );

        $eventStore->appendTo($streamName, new ArrayIterator([new TestDomainEvent(['foo' => 'baz'])]));

        $eventStore->commit();
    }

    /**
     * @test
     */
    public function it_dispatches_events_appended_to_event_store(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $streamName = new StreamName('test-stream');

        $eventBus = $this->prophesize(EventBus::class);
        $eventBus->dispatch(Argument::any())->shouldBeCalledTimes(2);

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalledTimes(2);
        $producer->commitTransaction()->shouldBeCalledTimes(2);

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());
        $plugin->attachToEventStore($eventStore);

        $eventStore->create(
            new Stream($streamName, new ArrayIterator([new TestDomainEvent(['foo' => 'bar'])]))
        );

        $eventStore->appendTo($streamName, new ArrayIterator([new TestDomainEvent(['foo' => 'baz'])]));
    }

    /**
     * @test
     */
    public function it_dequeues_events_when_transactional_event_store_is_rolled_back(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);
        $eventStore = new TransactionalActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $streamName = new StreamName('test-stream');

        $eventBus = $this->prophesize(EventBus::class);
        $eventBus->dispatch(Argument::any())->shouldNotBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldNotBeCalled();
        $producer->commitTransaction()->shouldNotBeCalled();

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());
        $plugin->attachToEventStore($eventStore);

        $eventStore->beginTransaction();

        $eventStore->create(
            new Stream($streamName, new ArrayIterator([new TestDomainEvent(['foo' => 'bar'])]))
        );

        $eventStore->appendTo($streamName, new ArrayIterator([new TestDomainEvent(['foo' => 'baz'])]));

        $eventStore->rollback();
    }

    /**
     * @test
     */
    public function it_does_nothing_when_no_recorded_events(): void
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

    /**
     * @test
     */
    public function it_does_not_publish_when_non_transactional_event_store_throws_exception(): void
    {
        $event1 = $this->prophesize(Message::class)->reveal();
        $event2 = $this->prophesize(Message::class)->reveal();
        $event3 = $this->prophesize(Message::class)->reveal();
        $event4 = $this->prophesize(Message::class)->reveal();

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])))->willThrow(StreamExistsAlready::with(new StreamName('test')))->shouldBeCalled();
        $eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]))->willThrow(new ConcurrencyException())->shouldBeCalled();

        $eventStore = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1)->shouldNotBeCalled();
        $eventBus->dispatch($event2)->shouldNotBeCalled();
        $eventBus->dispatch($event3)->shouldNotBeCalled();
        $eventBus->dispatch($event4)->shouldNotBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldNotBeCalled();
        $producer->commitTransaction()->shouldNotBeCalled();

        $eventPublisher = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());

        $eventPublisher->attachToEventStore($eventStore);

        try {
            $eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * @test
     */
    public function it_publish_after_rollback(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);
        $eventStore = new TransactionalActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $streamName = new StreamName('test-stream');

        $event1 = new TestDomainEvent(['foo' => 'baz']);
        $event2 = new TestDomainEvent(['foo' => 'bar']);

        $eventBus = $this->prophesize(EventBus::class);
        $eventBus->dispatch($event1)->willThrow(\Exception::class);
        $eventBus->dispatch($event2)->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);

        $plugin = new TransactionalEventPublisher($eventBus->reveal(), $producer->reveal());
        $plugin->attachToEventStore($eventStore);

        $eventStore->beginTransaction();

        $eventStore->create(
            new Stream($streamName, new ArrayIterator())
        );

        $eventStore->appendTo($streamName, new ArrayIterator([$event1]));

        try {
            $eventStore->commit();
        } catch (\Throwable $exception) {
            $eventStore->rollback();
        }

        $eventStore->appendTo($streamName, new ArrayIterator([$event2]));

        $eventStore->commit();
    }
}
