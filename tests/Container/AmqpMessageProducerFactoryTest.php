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
use Prooph\Common\Messaging\MessageConverter;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpMessageProducer;
use Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpMessageProducerFactory;

/**
 * Class AmqpMessageProducerFactoryTest
 * @package ProophTest\ServiceBus\Message\HumusAmqp\Container
 */
class AmqpMessageProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_amqp_message_producer()
    {
        $producer = $this->prophesize(Producer::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'humus-amqp-producer' => [
                    'message_producer' => [
                        'test-message-producer' => [
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

        $name = 'test-message-producer';
        $amqpMessageProducer = AmqpMessageProducerFactory::$name($container->reveal());
        $this->assertInstanceOf(AmqpMessageProducer::class, $amqpMessageProducer);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static()
    {
        $this->expectException(\Prooph\ServiceBus\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-message-producer';
        AmqpMessageProducerFactory::$name('invalid_container');
    }
}
