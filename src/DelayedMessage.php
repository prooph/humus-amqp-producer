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

namespace Prooph\ServiceBus\Message\HumusAmqp;

use Prooph\Common\Messaging\Message;

/**
 * Interface to represent delayed messages (aka messages that are processed in the future instead of now)
 * Usually you would implement these for commands that should be executed at a later time
 */
interface DelayedMessage extends Message
{
    /**
     * @return int the delay in milliseconds
     */
    public function delay(): int;
}
