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

use Prooph\Common\Messaging\Message;

/**
 * Interface to represent parallel messages (messages, executed at the same time, thus in parallel).
 * Usually you would implement these if you need to have multiple large query results,
 * so that calling them parallel improves overall performance
 */
interface ParallelMessage extends Message
{
    /**
     * @return Message[]
     */
    public function messages(): array;
}
