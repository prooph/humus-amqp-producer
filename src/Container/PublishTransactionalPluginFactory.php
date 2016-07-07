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

use Interop\Container\ContainerInterface;
use Prooph\ServiceBus\Exception;
use Prooph\ServiceBus\Message\HumusAmqp\PublishTransactionalPlugin;

/**
 * Class PublishTransactionalPluginFactory
 * @package Prooph\ServiceBus\Message\HumusAmqp\Container
 */
final class PublishTransactionalPluginFactory
{
    /**
     * @var string
     */
    private $producerName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_publish_transactional_plugin' => [PublishTransactionalPluginFactory::class, 'your_producer_name'],
     * ];
     * </code>
     *
     * @param string $producerName
     * @param array $arguments
     * @return PublishTransactionalPlugin
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $producerName, array $arguments) : PublishTransactionalPlugin
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($producerName))->__invoke($arguments[0]);
    }

    /**
     * PublishTransactionalPluginFactory constructor.
     * @param string $producerName
     */
    public function __construct(string $producerName)
    {
        $this->producerName = $producerName;
    }

    /**
     * @param ContainerInterface $container
     * @return PublishTransactionalPlugin
     */
    public function __invoke(ContainerInterface $container) : PublishTransactionalPlugin
    {
        $producer = $container->get($this->producerName);

        return new PublishTransactionalPlugin($producer);
    }
}
