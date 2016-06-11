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

use DateTimeImmutable;
use DateTimeZone;
use Prooph\Common\Messaging\Command;

/**
 * Class DelayedCommand
 * @package Prooph\ServiceBus\Message\HumusAmqp
 */
abstract class DelayedCommand extends Command implements DelayedMessage
{
    /**
     * @var DateTimeImmutable
     */
    protected $executeAt;

    /**
     * @param DateTimeImmutable $dateTime
     * @return DelayedCommand
     */
    public function executeAt(DateTimeImmutable $dateTime) : DelayedCommand
    {
        $delayedCommand = clone $this;
        $delayedCommand->executeAt = $dateTime;
        $delayedCommand->metadata['execute_at'] = $dateTime->format('Y-m-d\TH:i:s.u');

        return $delayedCommand;
    }

    /**
     * @return int the delay in milliseconds
     */
    public function delay() : int
    {
        return (int) floor(((float) $this->executeAt->format('U.u') - (float) $this->createdAt->format('U.u')) * 1000);
    }

    /**
     * Creates a new domain message from given array
     *
     * @param array $messageData
     * @return static
     */
    public static function fromArray(array $messageData) : DelayedCommand
    {
        $message = parent::fromArray($messageData);
        /** @var $message self */
        $message->executeAt = DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.u',
            $message->metadata['execute_at'],
            new DateTimeZone('UTC')
        );

        return $message;
    }
}
