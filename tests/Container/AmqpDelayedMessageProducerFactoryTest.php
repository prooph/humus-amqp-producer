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

namespace ProophTest\ServiceBus\Message\HumusAmqpDelayed\Container;

use Humus\Amqp\Producer;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpDelayedMessageProducer;
use Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpDelayedMessageProducerFactory;
use Psr\Container\ContainerInterface;

class AmqpDelayedMessageProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_amqpDelayed_message_producer(): void
    {
        $producer = $this->prophesize(Producer::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'humus-amqp-producer' => [
                    'delayed_message_producer' => [
                        'test-delayed-message-producer' => [
                            'producer' => 'test-producer',
                            'message_converter' => 'test-message-converter',
                            'app_id' => 'test-app',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $container->get('test-producer')->willReturn($producer->reveal())->shouldBeCalled();
        $container->get('test-message-converter')->willReturn($messageConverter->reveal())->shouldBeCalled();

        $name = 'test-delayed-message-producer';
        $amqpDelayedMessageProducer = AmqpDelayedMessageProducerFactory::$name($container->reveal());
        $this->assertInstanceOf(AmqpDelayedMessageProducer::class, $amqpDelayedMessageProducer);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(\Prooph\ServiceBus\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-delayed-message-producer';
        AmqpDelayedMessageProducerFactory::$name('invalid_container');
    }
}
