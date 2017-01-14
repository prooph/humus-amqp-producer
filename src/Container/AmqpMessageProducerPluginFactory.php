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

namespace Prooph\ServiceBus\Message\HumusAmqp\Container;

use Interop\Container\ContainerInterface;
use Prooph\ServiceBus\Exception;
use Prooph\ServiceBus\Plugin\MessageProducerPlugin;

final class AmqpMessageProducerPluginFactory
{
    /**
     * @var string
     */
    private $amqpMessageProducerName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_amqp_message_producer' => [AmqpMessageProducerFactory::class, 'your_amqp_message_producer_name'],
     * ];
     * </code>
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $amqpMessageProducerName, array $arguments): MessageProducerPlugin
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($amqpMessageProducerName))->__invoke($arguments[0]);
    }

    public function __construct(string $amqpMessageProducerName)
    {
        $this->amqpMessageProducerName = $amqpMessageProducerName;
    }

    public function __invoke(ContainerInterface $container): MessageProducerPlugin
    {
        return new MessageProducerPlugin($container->get($this->amqpMessageProducerName));
    }
}
