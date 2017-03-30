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

namespace Prooph\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\JsonRpc\Error;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\JsonRpc\JsonRpcRequest;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\ServiceBus\Async\MessageProducer;
use Prooph\ServiceBus\Exception\RuntimeException;
use React\Promise\Deferred;

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
     * @var array
     */
    private $messageNameToServerNameMap;

    /**
     * @var string|null
     */
    private $defaultServerName;

    /**
     * @var float
     */
    private $timeout;

    public function __construct(
        JsonRpcClient $client,
        MessageConverter $messageConverter,
        array $messageNameToServerNameMap,
        string $defaultServerName = null,
        float $timeout = 0.0
    ) {
        $this->client = $client;
        $this->messageConverter = $messageConverter;
        $this->messageNameToServerNameMap = $messageNameToServerNameMap;
        $this->defaultServerName = $defaultServerName;
        $this->timeout = $timeout;
    }

    /**
     * Resolves the deferred for a given message.
     * If an error occurs, the deferred is rejected and a reason will be given.
     *
     * If a message is parallel message (instance of \Prooph\ServiceBus\Message\HumusAmqp\ParallelMessage)
     * and instance of \Humus\Amqp\JsonRpc\JsonRpcResponseCollection, which can be queried for given
     * message ids.
     *
     * @throws RuntimeException If a $deferred is not passed
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
                    $this->serverName($parallelMessage->messageName()),
                    $parallelMessage->messageName(),
                    $data,
                    $parallelMessage->uuid()->toString(),
                    $parallelMessage->messageName(),
                    0,
                    $parallelMessage->createdAt()->getTimestamp()
                ));
            }

            $responseCollection = $this->client->getResponseCollection($this->timeout);

            $deferred->resolve($responseCollection);

            return;
        }

        $data = $this->arrayFromMessage($message);

        $messageId = $message->uuid()->toString();

        $this->client->addRequest(new JsonRpcRequest(
            $this->serverName($message->messageName()),
            $message->messageName(),
            $data,
            $messageId,
            $message->messageName(),
            0,
            $message->createdAt()->getTimestamp()
        ));

        $responseCollection = $this->client->getResponseCollection($this->timeout);

        $response = $responseCollection->getResponse($messageId);

        if ($response->isError()) {
            $deferred->reject($response->error()->message());
        }

        $deferred->resolve($response->result());
    }

    private function arrayFromMessage(Message $message): array
    {
        $messageData = $this->messageConverter->convertToArray($message);

        MessageDataAssertion::assert($messageData);

        $messageData['created_at'] = $message->createdAt()->format('Y-m-d\TH:i:s.u');

        return $messageData;
    }

    private function serverName(string $messageName): string
    {
        if (isset($this->messageNameToServerNameMap[$messageName])) {
            return $this->messageNameToServerNameMap[$messageName];
        } elseif (null !== $this->defaultServerName) {
            return $this->defaultServerName;
        }

        throw new RuntimeException('No server found for ' . $messageName);
    }
}
