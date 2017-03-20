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

use Humus\Amqp\Connection;
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Constants;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exchange;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\JsonRpc\JsonRpcResponse;
use Humus\Amqp\JsonRpc\ResponseCollection;
use Humus\Amqp\Producer;
use Humus\Amqp\Queue;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpQueryProducer;
use Prooph\ServiceBus\Message\HumusAmqp\ParallelMessage;
use ProophTest\ServiceBus\Mock\FetchSomething;
use Prophecy\Argument;
use React\Promise\Deferred;

class AmqpQueryProducerTest extends TestCase
{
    /**
     * @test
     */
    public function it_queries(): void
    {
        $message = new FetchSomething(['foo' => 'bar']);

        $response = ['some' => 'result'];

        $result = [
            'result' => $response,
        ];

        $envelope = $this->prophesize(Envelope::class);
        $envelope->getHeader('jsonrpc')->willReturn(JsonRpcResponse::JSONRPC_VERSION)->shouldBeCalled();
        $envelope->getContentEncoding()->willReturn('UTF-8')->shouldBeCalled();
        $envelope->getContentType()->willReturn('application/json')->shouldBeCalled();
        $envelope->getBody()->willReturn(json_encode($result))->shouldBeCalled();
        $envelope->getCorrelationId()->willReturn($message->uuid()->toString())->shouldBeCalled();

        $options = $this->prophesize(ConnectionOptions::class);
        $options->getLogin()->willReturn('sasa')->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->getOptions()->willReturn($options->reveal());

        $queue = $this->prophesize(Queue::class);
        $queue->getName()->willReturn('test-queue')->shouldBeCalled();
        $queue->get(Constants::AMQP_AUTOACK)->willReturn($envelope->reveal())->shouldBeCalled();
        $queue->getConnection()->willReturn($connection->reveal())->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->publish(Argument::any(), Argument::any(), Constants::AMQP_NOPARAM, Argument::any())->shouldBeCalled();

        $client = new JsonRpcClient($queue->reveal(), ['test-server' => $exchange->reveal()]);

        $producer = new AmqpQueryProducer($client, new NoOpMessageConverter(), [FetchSomething::class => 'test-server']);

        $deferred = new Deferred();

        $producer($message, $deferred);

        $hit = false;

        $promise = $deferred->promise();
        $promise->then(function (array $res) use (&$response, &$hit): void {
            $this->assertEquals($response, $res);
            $hit = true;
        });

        $promise->otherwise(function () {
            $this->fail('Promise rejected');
        });

        $this->assertTrue($hit);
    }

    /**
     * @test
     */
    public function it_handles_json_rpc_errors(): void
    {
        $message = new FetchSomething(['foo' => 'bar']);

        $envelope = $this->prophesize(Envelope::class);
        $envelope->getHeader('jsonrpc')->willReturn(JsonRpcResponse::JSONRPC_VERSION)->shouldBeCalled();
        $envelope->getContentEncoding()->willReturn('UTF-16')->shouldBeCalled();
        $envelope->getCorrelationId()->willReturn($message->uuid()->toString());

        $options = $this->prophesize(ConnectionOptions::class);
        $options->getLogin()->willReturn('sasa')->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->getOptions()->willReturn($options->reveal());

        $queue = $this->prophesize(Queue::class);
        $queue->getName()->willReturn('test-queue')->shouldBeCalled();
        $queue->get(Constants::AMQP_AUTOACK)->willReturn($envelope->reveal())->shouldBeCalled();
        $queue->getConnection()->willReturn($connection->reveal())->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->publish(Argument::any(), Argument::any(), Constants::AMQP_NOPARAM, Argument::any())->shouldBeCalled();

        $client = new JsonRpcClient($queue->reveal(), ['test-server' => $exchange->reveal()]);

        $producer = new AmqpQueryProducer($client, new NoOpMessageConverter(), [], 'test-server');

        $deferred = new Deferred();

        $producer($message, $deferred);

        $hit = false;

        $promise = $deferred->promise();
        $promise->then(function (): void {
            $this->fail('Result returned');
        });

        $promise->otherwise(function (string $error) use (&$hit): void {
            $this->assertEquals('Invalid JSON-RPC response', $error);
            $hit = true;
        });

        $this->assertTrue($hit);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_suitable_server_found_for_message(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No server found for ProophTest\ServiceBus\Mock\FetchSomething');

        $message = new FetchSomething(['foo' => 'bar']);

        $queue = $this->prophesize(Queue::class);

        $exchange = $this->prophesize(Exchange::class);

        $client = new JsonRpcClient($queue->reveal(), ['test-server' => $exchange->reveal()]);

        $producer = new AmqpQueryProducer($client, new NoOpMessageConverter(), []);

        $deferred = new Deferred();

        $producer($message, $deferred);
    }

    /**
     * @test
     */
    public function it_queries_parallel_messages(): void
    {
        $message1 = new FetchSomething(['foo' => 'bar']);
        $message2 = new FetchSomething(['foo' => 'baz']);

        $message = $this->prophesize(ParallelMessage::class);
        $message->messages()->willReturn([$message1, $message2])->shouldBeCalled();

        $response1 = ['some' => 'result'];
        $response2 = ['some' => 'other result'];

        $result1 = [
            'result' => $response1,
        ];

        $result2 = [
            'result' => $response2,
        ];

        $envelope1 = $this->prophesize(Envelope::class);
        $envelope1->getHeader('jsonrpc')->willReturn(JsonRpcResponse::JSONRPC_VERSION)->shouldBeCalled();
        $envelope1->getContentEncoding()->willReturn('UTF-8')->shouldBeCalled();
        $envelope1->getContentType()->willReturn('application/json')->shouldBeCalled();
        $envelope1->getBody()->willReturn(json_encode($result1))->shouldBeCalled();
        $envelope1->getCorrelationId()->willReturn($message1->uuid()->toString())->shouldBeCalled();

        $envelope2 = $this->prophesize(Envelope::class);
        $envelope2->getHeader('jsonrpc')->willReturn(JsonRpcResponse::JSONRPC_VERSION)->shouldBeCalled();
        $envelope2->getContentEncoding()->willReturn('UTF-8')->shouldBeCalled();
        $envelope2->getContentType()->willReturn('application/json')->shouldBeCalled();
        $envelope2->getBody()->willReturn(json_encode($result2))->shouldBeCalled();
        $envelope2->getCorrelationId()->willReturn($message2->uuid()->toString())->shouldBeCalled();

        $options = $this->prophesize(ConnectionOptions::class);
        $options->getLogin()->willReturn('sasa')->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->getOptions()->willReturn($options->reveal());

        $queue = $this->prophesize(Queue::class);
        $queue->getName()->willReturn('test-queue')->shouldBeCalled();
        $queue->get(Constants::AMQP_AUTOACK)->willReturn($envelope1->reveal(), $envelope2->reveal())->shouldBeCalledTimes(2);
        $queue->getConnection()->willReturn($connection->reveal())->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->publish(Argument::any(), Argument::any(), Constants::AMQP_NOPARAM, Argument::any())->shouldBeCalledTimes(2);

        $client = new JsonRpcClient($queue->reveal(), ['test-server' => $exchange->reveal()]);

        $producer = new AmqpQueryProducer($client, new NoOpMessageConverter(), [], 'test-server');

        $deferred = new Deferred();

        $producer($message->reveal(), $deferred);

        $hitCounter = 0;

        $promise = $deferred->promise();
        $promise->then(function (ResponseCollection $collection) use (&$response1, &$response2, &$hitCounter): void {
            foreach ($collection as $result) {
                ++$hitCounter;
                if (1 === $hitCounter) {
                    $this->assertEquals($response1, $result->result());
                }
                if (2 === $hitCounter) {
                    $this->assertEquals($response2, $result->result());
                }
            }
        });

        $promise->otherwise(function () {
            $this->fail('Promise rejected');
        });

        $this->assertEquals(2, $hitCounter);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_deferred_passed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deferred expected, null given');

        $queue = $this->prophesize(Queue::class);
        $exchange = $this->prophesize(Exchange::class);
        $client = new JsonRpcClient($queue->reveal(), [$exchange->reveal()]);
        $messageConverter = $this->prophesize(MessageConverter::class);
        $message = $this->prophesize(Message::class);

        $messageProducer = new AmqpQueryProducer($client, $messageConverter->reveal(), []);
        $messageProducer($message->reveal());
    }
}
