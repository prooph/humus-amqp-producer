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
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Exception;
use Prooph\ServiceBus\Message\HumusAmqp\ConfirmSelectEventPublisher;

/**
 * Class ConfirmSelectEventPublisherFactory
 * @package Prooph\ServiceBus\Message\HumusAmqp\Container
 */
final class ConfirmSelectEventPublisherFactory implements
    ProvidesDefaultOptions,
    RequiresConfigId,
    RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $eventPublisherName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_publish_confirm_select_plugin' => [ConfirmSelectEventPublisherFactory::class, 'your_event_publisher_name'],
     * ];
     * </code>
     *
     * @param string $eventPublisherName
     * @param array $arguments
     * @return ConfirmSelectEventPublisher
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $eventPublisherName, array $arguments) : ConfirmSelectEventPublisher
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($eventPublisherName))->__invoke($arguments[0]);
    }

    /**
     * ConfirmSelectEventPublisherFactory constructor.
     * @param string $eventPublisherName
     */
    public function __construct(string $eventPublisherName)
    {
        $this->eventPublisherName = $eventPublisherName;
    }

    /**
     * @param ContainerInterface $container
     * @return ConfirmSelectEventPublisher
     */
    public function __invoke(ContainerInterface $container) : ConfirmSelectEventPublisher
    {
        $options = $this->options($container->get('config'), $this->eventPublisherName);

        $eventBus = $container->get($options['event_bus']);
        $producer = $container->get($options['producer']);

        return new ConfirmSelectEventPublisher($eventBus, $producer, $options['timeout']);
    }

    /**
     * @return array
     */
    public function dimensions()
    {
        return ['prooph', 'humus-amqp-producer', 'confirm_select_event_publisher'];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'event_bus' => EventBus::class,
            'timeout' => 2.0
        ];
    }

    /**
     * @return array
     */
    public function mandatoryOptions()
    {
        return [
            'producer'
        ];
    }
}
