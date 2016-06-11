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
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpDelayedMessageProducer;

/**
 * Class AmqpDelayedMessageProducerFactory
 * @package Prooph\ServiceBus\Message\HumusAmqp\Container
 */
final class AmqpDelayedMessageProducerFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $amqpDelayedMessageProducerName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_amqp_delayed_message_producer' => [AmqpDelayedMessageProducerFactory::class, 'your_amqp_delayed_message_producer_name'],
     * ];
     * </code>
     *
     * @param string $amqpDelayedMessageProducerName
     * @param array $arguments
     * @return AmqpDelayedMessageProducer
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $amqpDelayedMessageProducerName, array $arguments) : AmqpDelayedMessageProducer
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($amqpDelayedMessageProducerName))->__invoke($arguments[0]);
    }

    /**
     * AmqpCommandConsumerCallbackFactory constructor.
     * @param string $amqpDelayedMessageProducerName
     */
    public function __construct(string $amqpDelayedMessageProducerName)
    {
        $this->amqpDelayedMessageProducerName = $amqpDelayedMessageProducerName;
    }

    /**
     * @param ContainerInterface $container
     * @return AmqpDelayedMessageProducer
     */
    public function __invoke(ContainerInterface $container) : AmqpDelayedMessageProducer
    {
        $options = $this->options($container->get('config'), $this->amqpDelayedMessageProducerName);

        return new AmqpDelayedMessageProducer(
            $container->get($options['producer']),
            $container->get($options['message_converter']),
            $options['app_id']
        );
    }

    /**
     * @return array
     */
    public function dimensions()
    {
        return ['prooph', 'humus-amqp-producer', 'delayed_message_producer'];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'message_converter' => NoOpMessageConverter::class,
            'app_id' => '',
        ];
    }

    /**
     * @return array
     */
    public function mandatoryOptions()
    {
        return [
            'producer',
        ];
    }
}
