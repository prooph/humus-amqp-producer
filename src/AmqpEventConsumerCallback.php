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

namespace Prooph\ServiceBus\Message\HumusAmqp;

use DateTimeImmutable;
use DateTimeZone;
use Humus\Amqp\DeliveryResult;
use Humus\Amqp\Envelope;
use Humus\Amqp\Queue;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\Exception\ConcurrencyException;
use Prooph\ServiceBus\EventBus;

final class AmqpEventConsumerCallback
{
    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    public function __construct(EventBus $eventBus, MessageFactory $messageFactory)
    {
        $this->eventBus = $eventBus;
        $this->messageFactory = $messageFactory;
    }

    public function __invoke(Envelope $envelope, Queue $queue): DeliveryResult
    {
        $data = \json_decode($envelope->getBody(), true);

        if (! isset($data['created_at'])) {
            return DeliveryResult::MSG_REJECT();
        }

        $data['created_at'] = DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.u',
            $data['created_at'],
            new DateTimeZone('UTC')
        );

        if (false === $data['created_at']) {
            return DeliveryResult::MSG_REJECT();
        }

        try {
            $event = $this->messageFactory->createMessageFromArray($envelope->getType(), $data);
            $this->eventBus->dispatch($event);
        } catch (\Throwable $e) {
            while ($e = $e->getPrevious()) {
                if ($e instanceof ConcurrencyException) {
                    return DeliveryResult::MSG_REJECT_REQUEUE();
                }
            }

            return DeliveryResult::MSG_REJECT();
        }

        return DeliveryResult::MSG_ACK();
    }
}
