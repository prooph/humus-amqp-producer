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

namespace ProophTest\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\Constants;
use Humus\Amqp\Producer;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpMessageProducer;
use React\Promise\Deferred;

class AmqpMessageProducerTest extends TestCase
{
    /**
     * @test
     */
    public function it_publishes_messages(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $producer = $this->prophesize(Producer::class);
        $producer->publish(
            [
                'message_name' => 'test-message',
                'uuid' => 'ccefedef-85e1-4fd0-b247-ed13d378b050',
                'version' => 1,
                'payload' => [],
                'metadata' => [],
                'created_at' => $now->format('Y-m-d\TH:i:s.u'),
            ],
            'test-message',
            Constants::AMQP_NOPARAM,
            [
                'app_id' => 'test_app',
                'timestamp' => $now->getTimestamp(),
                'type' => 'test-message',
            ]
        )->shouldBeCalled();

        $message = $this->prophesize(Message::class);
        $message->createdAt()->willReturn($now)->shouldBeCalled();
        $message->messageName()->willReturn('test-message')->shouldBeCalled();

        $messageConverter = $this->prophesize(MessageConverter::class);
        $messageConverter->convertToArray($message)->willReturn([
            'message_name' => 'test-message',
            'uuid' => 'ccefedef-85e1-4fd0-b247-ed13d378b050',
            'version' => 1,
            'payload' => [],
            'metadata' => [],
            'created_at' => $now,
        ])->shouldBeCalled();

        $messageProducer = new AmqpMessageProducer($producer->reveal(), $messageConverter->reveal(), 'test_app');
        $messageProducer($message->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_deferred_passed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prooph\ServiceBus\Message\HumusAmqp\AmqpMessageProducer cannot handle query messages which require future responses.');

        $producer = $this->prophesize(Producer::class);
        $messageConverter = $this->prophesize(MessageConverter::class);
        $message = $this->prophesize(Message::class);
        $deferred = $this->prophesize(Deferred::class);

        $messageProducer = new AmqpMessageProducer($producer->reveal(), $messageConverter->reveal());
        $messageProducer($message->reveal(), $deferred->reveal());
    }
}
