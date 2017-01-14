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

namespace ProophTest\ServiceBus\Message\HumusAmqp\Container;

use Humus\Amqp\Producer;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\HumusAmqp\ConfirmSelectEventPublisher;
use Prooph\ServiceBus\Message\HumusAmqp\Container\ConfirmSelectEventPublisherFactory;

class ConfirmSelectEventPublisherFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_plugin()
    {
        $eventBus = $this->prophesize(EventBus::class);

        $producer = $this->prophesize(Producer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container
            ->get('config')
            ->willReturn([
                'prooph' => [
                    'humus-amqp-producer' => [
                        'confirm_select_event_publisher' => [
                            'default' => [
                                'event_bus' => 'my_event_bus',
                                'producer' => 'my_producer',
                            ],
                        ],
                    ],
                ],
            ])
            ->shouldBeCalled();

        $container
            ->get('my_event_bus')
            ->willReturn($eventBus->reveal())
            ->shouldBeCalled();

        $container
            ->get('my_producer')
            ->willReturn($producer->reveal())
            ->shouldBeCalled();

        $factory = new ConfirmSelectEventPublisherFactory('default');
        $plugin = $factory($container->reveal());

        $this->assertInstanceOf(ConfirmSelectEventPublisher::class, $plugin);
    }

    /**
     * @test
     */
    public function it_creates_plugin_via_call_static()
    {
        $eventBus = $this->prophesize(EventBus::class);

        $producer = $this->prophesize(Producer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container
            ->get('config')
            ->willReturn([
                'prooph' => [
                    'humus-amqp-producer' => [
                        'confirm_select_event_publisher' => [
                            'default' => [
                                'event_bus' => 'my_event_bus',
                                'producer' => 'my_producer',
                            ],
                        ],
                    ],
                ],
            ])
            ->shouldBeCalled();

        $container
            ->get('my_event_bus')
            ->willReturn($eventBus->reveal())
            ->shouldBeCalled();

        $container
            ->get('my_producer')
            ->willReturn($producer->reveal())
            ->shouldBeCalled();

        $eventPublisherName = 'default';

        $plugin = ConfirmSelectEventPublisherFactory::$eventPublisherName($container->reveal());

        $this->assertInstanceOf(ConfirmSelectEventPublisher::class, $plugin);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_given()
    {
        $this->expectException(\Prooph\ServiceBus\Exception\InvalidArgumentException::class);

        $eventPublisherName = 'default';

        ConfirmSelectEventPublisherFactory::$eventPublisherName('invalid');
    }
}
