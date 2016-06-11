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

namespace Prooph\ServiceBus\Message\HumusAmqp\Container;

use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Container\ContainerInterface;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Exception;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpCommandConsumerCallback;

/**
 * Class AmqpCommandConsumerCallbackFactory
 * @package Prooph\ServiceBus\Message\HumusAmqp\Container
 */
final class AmqpCommandConsumerCallbackFactory implements ProvidesDefaultOptions, RequiresConfigId
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $amqpCommandConsumerCallbackName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_amqp_command_consumer_callback' => [AmqpCommandConsumerCallbackFactory::class, 'your_amqp_command_consumer_callback_name'],
     * ];
     * </code>
     *
     * @param string $amqpCommandConsumerCallbackName
     * @param array $arguments
     * @return AmqpCommandConsumerCallback
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $amqpCommandConsumerCallbackName, array $arguments) : AmqpCommandConsumerCallback
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($amqpCommandConsumerCallbackName))->__invoke($arguments[0]);
    }

    /**
     * AmqpCommandConsumerCallbackFactory constructor.
     * @param string $amqpCommandConsumerCallbackName
     */
    public function __construct(string $amqpCommandConsumerCallbackName)
    {
        $this->amqpCommandConsumerCallbackName = $amqpCommandConsumerCallbackName;
    }

    /**
     * @param ContainerInterface $container
     * @return AmqpCommandConsumerCallback
     */
    public function __invoke(ContainerInterface $container) : AmqpCommandConsumerCallback
    {
        $options = $this->options($container->get('config'), $this->amqpCommandConsumerCallbackName);

        return new AmqpCommandConsumerCallback(
            $container->get($options['command_bus']),
            $container->get($options['message_factory'])
        );
    }

    /**
     * @return array
     */
    public function dimensions()
    {
        return ['prooph', 'humus-amqp-producer', 'command_consumer_callback'];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'command_bus' => CommandBus::class,
            'message_factory' => FQCNMessageFactory::class
        ];
    }
}
