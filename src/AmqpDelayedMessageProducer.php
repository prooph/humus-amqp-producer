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

use Humus\Amqp\Producer;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\ServiceBus\Exception\RuntimeException;
use React\Promise\Deferred;

/**
 * Class AmqpDelayedMessageProducer
 * @package Prooph\ServiceBus\Message\HumusAmqp
 */
final class AmqpDelayedMessageProducer
{
    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var MessageConverter
     */
    private $messageConverter;

    /**
     * @var string
     */
    private $appId;

    /**
     * AmqpDelayedMessageProducer constructor.
     * @param Producer $producer
     * @param MessageConverter $messageConverter
     * @param string $appId
     */
    public function __construct(Producer $producer, MessageConverter $messageConverter, string $appId = '')
    {
        $this->producer = $producer;
        $this->messageConverter = $messageConverter;
    }

    /**
     * @param Message $message
     * @param Deferred|null $deferred
     * @return void
     * @throws RuntimeException
     */
    public function __invoke(Message $message, Deferred $deferred = null)
    {
        if (! $message instanceof DelayedMessage) {
            throw new RuntimeException(sprintf(
                'Message is not a delayed message (instance of %s)',
                DelayedMessage::class
            ));
        }

        if (null !== $deferred) {
            throw new RuntimeException(__CLASS__ . ' cannot handle query messages which require future responses.');
        }

        $data = $this->arrayFromMessage($message);

        $attributes = [
            'headers' => [
                'x-delay' => $message->delay(),
            ],
            'app_id' => $this->appId,
            'timestamp' => $message->createdAt()->getTimestamp(),
            'type' => $message->messageName()
        ];

        $this->producer->publish($data, $message->messageName(), $attributes);
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
