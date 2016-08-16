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

namespace Prooph\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\JsonRpc\JsonRpcRequest;
use Humus\Amqp\Producer;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\ServiceBus\Async\MessageProducer;
use Prooph\ServiceBus\Exception\RuntimeException;
use React\Promise\Deferred;

/**
 * Class AmqpQueryProducer
 * @package Prooph\ServiceBus\Message\HumusAmqp
 */
final class AmqpQueryProducer implements MessageProducer
{
    /**
     * @var JsonRpcClient
     */
    private $client;

    /**
     * @var MessageConverter
     */
    private $messageConverter;

    /**
     * @var float
     */
    private $timeout;

    /**
     * AmqpQueryProducer constructor.
     * @param JsonRpcClient $client
     * @param MessageConverter $messageConverter
     * @param float $timeout in seconds
     */
    public function __construct(JsonRpcClient $client, MessageConverter $messageConverter, float $timeout = 0.0)
    {
        $this->client = $client;
        $this->messageConverter = $messageConverter;
        $this->timeout = $timeout;
    }

    /**
     * @param Message $message
     * @param Deferred|null $deferred
     * @throws RuntimeException If a $deferred is passed but producer can not handle it
     * @return void
     */
    public function __invoke(Message $message, Deferred $deferred = null)
    {
        if (null === $deferred) {
            throw new RuntimeException('Deferred expected, null given');
        }

        if ($message instanceof ParallelMessage) {
            foreach ($message->messages() as $parallelMessage) {
                $data = $this->arrayFromMessage($parallelMessage);

                $this->client->addRequest(new JsonRpcRequest(
                    'server',
                    $message->messageName(),
                    $data,
                    $message->uuid()->toString(),
                    $message->messageName(),
                    0,
                    $message->createdAt()->getTimestamp()
                ));
            }
        } else {
            $data = $this->arrayFromMessage($message);

            $this->client->addRequest(new JsonRpcRequest(
                'server',
                $message->messageName(),
                $data,
                $message->uuid()->toString(),
                $message->messageName(),
                0,
                $message->createdAt()->getTimestamp()
            ));
        }

        $deferred->resolve($this->client->getResponseCollection($this->timeout));
    }

    /**
     * @param Message $message
     * @return array
     */
    private function arrayFromMessage(Message $message) : array
    {
        $messageData = $this->messageConverter->convertToArray($message);

        MessageDataAssertion::assert($messageData);

        $messageData['created_at'] = $message->createdAt()->format('Y-m-d\TH:i:s.u');

        return $messageData;
    }
}
