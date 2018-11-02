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

namespace ProophTest\ServiceBus\Message\HumusAmqpDelayed\Container;

use PHPUnit\Framework\TestCase;
use Prooph\ServiceBus\Async\MessageProducer;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpMessageProducerPluginFactory;
use Prooph\ServiceBus\Plugin\MessageProducerPlugin;
use Psr\Container\ContainerInterface;

class AmqpMessageProducerPluginFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_message_producer_plugin(): void
    {
        $producer = $this->prophesize(MessageProducer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('producer')->willReturn($producer->reveal())->shouldBeCalled();

        $factory = new AmqpMessageProducerPluginFactory('producer');
        $plugin = $factory($container->reveal());
        $this->assertInstanceOf(MessageProducerPlugin::class, $plugin);
    }

    /**
     * @test
     */
    public function it_creates_message_producer_plugin_via_callstatic(): void
    {
        $producer = $this->prophesize(MessageProducer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('producer')->willReturn($producer->reveal())->shouldBeCalled();

        $name = 'producer';
        $plugin = AmqpMessageProducerPluginFactory::$name($container->reveal());
        $this->assertInstanceOf(MessageProducerPlugin::class, $plugin);
    }

    /**
     * @test
     */
    public function it_throws_exception_given_invalid_container_to_callstatic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $name = 'producer';
        AmqpMessageProducerPluginFactory::$name('invalid container');
    }
}
