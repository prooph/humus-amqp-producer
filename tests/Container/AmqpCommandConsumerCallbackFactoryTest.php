<?php
/**
 * This file is part of the prooph/humus-amqp-producer.
 * (c) 2016-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\ServiceBus\Message\HumusAmqp\Container;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpCommandConsumerCallback;
use Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpCommandConsumerCallbackFactory;

class AmqpCommandConsumerCallbackFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_amqp_command_consumer_callback(): void
    {
        $commandBus = $this->prophesize(CommandBus::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'humus-amqp-producer' => [
                    'command_consumer_callback' => [
                        'test-command-consumer-callback' => [
                            'command_bus' => 'test-command-bus',
                            'message_factory' => 'test-message-factory',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $container->get('test-command-bus')->willReturn($commandBus->reveal())->shouldBeCalled();
        $container->get('test-message-factory')->willReturn($messageFactory->reveal())->shouldBeCalled();

        $name = 'test-command-consumer-callback';
        $amqpCommandConsumerCallback = AmqpCommandConsumerCallbackFactory::$name($container->reveal());
        $this->assertInstanceOf(AmqpCommandConsumerCallback::class, $amqpCommandConsumerCallback);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(\Prooph\ServiceBus\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-command-consumer-callback';
        AmqpCommandConsumerCallbackFactory::$name('invalid_container');
    }
}
