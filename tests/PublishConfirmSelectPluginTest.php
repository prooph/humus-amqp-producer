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
use Prooph\ServiceBus\Message\HumusAmqp\PublishConfirmSelectPlugin;
use Prophecy\Argument;

/**
 * Class PublishConfirmSelectPluginTest
 * @package ProophTest\ServiceBus\Message\HumusAmqp
 */
class PublishConfirmSelectPluginTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_up_event_store()
    {
        $producer = $this->prophesize(Producer::class);

        $plugin = new PublishConfirmSelectPlugin($producer->reveal());

        $actionEventEmitter = $this->prophesize(ActionEventEmitter::class);
        $actionEventEmitter->attachListener('commit.post', [$plugin, 'onEventStoreCommitPostConfirmSelect'], 1000)->shouldBeCalled();
        $actionEventEmitter->attachListener('commit.post', [$plugin, 'onEventStoreCommitPostWaitForConfirm'], -1000)->shouldBeCalled();

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->getActionEventEmitter()->willReturn($actionEventEmitter->reveal())->shouldBeCalled();

        $plugin->setUp($eventStore->reveal());
    }

    /**
     * @test
     */
    public function it_confirms_select_and_waits_for_confirm_on_event_store_commit_post_with_countable_events()
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $iterator = new \ArrayIterator(['foo', 'bar']);
        $actionEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn($iterator)->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->confirmSelect()->shouldBeCalled();
        $producer->setConfirmCallback(Argument::type('callable'), Argument::type('callable'))->shouldBeCalled();
        $producer->waitForConfirm(2)->shouldBeCalled();

        $plugin = new PublishConfirmSelectPlugin($producer->reveal());
        $plugin->onEventStoreCommitPostConfirmSelect($actionEvent->reveal());
        $plugin->onEventStoreCommitPostWaitForConfirm($this->prophesize(ActionEvent::class)->reveal());
    }

    /**
     * @test
     */
    public function it_confirms_select_and_waits_for_confirm_on_event_store_commit_post_with_non_countable_events()
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $iterator = new \AppendIterator();
        $iterator->append(new \ArrayIterator(['foo', 'bar']));
        $actionEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn($iterator)->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->confirmSelect()->shouldBeCalled();
        $producer->setConfirmCallback(Argument::type('callable'), Argument::type('callable'))->shouldBeCalled();
        $producer->waitForConfirm(2)->shouldBeCalled();

        $plugin = new PublishConfirmSelectPlugin($producer->reveal());
        $plugin->onEventStoreCommitPostConfirmSelect($actionEvent->reveal());
        $plugin->onEventStoreCommitPostWaitForConfirm($this->prophesize(ActionEvent::class)->reveal());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_no_recorded_events()
    {
        $actionEvent = $this->prophesize(ActionEvent::class);
        $actionEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn(new \ArrayIterator())->shouldBeCalled();

        $producer = $this->prophesize(Producer::class);
        $producer->confirmSelect()->shouldNotBeCalled();
        $producer->setConfirmCallback(Argument::type('callable'), Argument::type('callable'))->shouldNotBeCalled();
        $producer->waitForConfirm(2)->shouldNotBeCalled();

        $plugin = new PublishConfirmSelectPlugin($producer->reveal());
        $plugin->onEventStoreCommitPostConfirmSelect($actionEvent->reveal());
        $plugin->onEventStoreCommitPostWaitForConfirm($this->prophesize(ActionEvent::class)->reveal());
    }
}
