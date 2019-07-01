<?php

/**
 * This file is part of the prooph/humus-amqp-producer.
 * (c) 2016-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2016-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\ServiceBus\Message\HumusAmqp\Container;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpEventConsumerCallback;
use Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpEventConsumerCallbackFactory;
use Psr\Container\ContainerInterface;

class AmqpEventConsumerCallbackFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_amqp_event_consumer_callback(): void
    {
        $eventBus = $this->prophesize(EventBus::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'humus-amqp-producer' => [
                    'event_consumer_callback' => [
                        'test-event-consumer-callback' => [
                            'event_bus' => 'test-event-bus',
                            'message_factory' => 'test-message-factory',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $container->get('test-event-bus')->willReturn($eventBus->reveal())->shouldBeCalled();
        $container->get('test-message-factory')->willReturn($messageFactory->reveal())->shouldBeCalled();

        $name = 'test-event-consumer-callback';
        $amqpEventConsumerCallback = AmqpEventConsumerCallbackFactory::$name($container->reveal());
        $this->assertInstanceOf(AmqpEventConsumerCallback::class, $amqpEventConsumerCallback);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(\Prooph\ServiceBus\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-event-consumer-callback';
        AmqpEventConsumerCallbackFactory::$name('invalid_container');
    }
}
