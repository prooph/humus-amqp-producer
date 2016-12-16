# Command Consumer

A command consumer is a worker that waits for incoming commands via AMQP. When a new command arrives, it will be
dispatched to the command bus to be handled.

The simplest way to explain how to configure a command consumer, is to show by example.
We will not cover the details of the HumusAmqp configuration, look at the
[HumusAmqp Docs](https://humusamqp.readthedocs.io/en/latest/) instead.

In the following section a command consumer and delayed command consumer is configured, we cover the details
afterwards.

```php
return [
    'humus' => [
        'amqp' => [
            'connection' => [
                'default-amqp-connection' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'login' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/app',
                    'persistent' => false,
                    'read_timeout' => 600,
                    'write_timeout' => 1,
                    'heartbeat' => 300,
                ],
            ],
            'driver' => 'amqp-extension',
            'exchange' => [
                'command_bus_exchange' => [
                    'name' => 'command_bus_exchange',
                    'type' => 'topic',
                    'connection' => 'default-amqp-connection',
                ],
                'command_bus_error_exchange' => [
                    'name' => 'command_bus_error_exchange',
                    'type' => 'topic',
                    'connection' => 'default-amqp-connection',
                ],
                'delayed_command_bus_exchange' => [
                    'name' => 'delayed_command_bus_exchange',
                    'type' => 'x-delayed-message',
                    'arguments' => [
                        'x-delayed-type' => 'topic'
                    ],
                    'connection' => 'default-amqp-connection',
                ],
            ],
            'queue' => [
                'command_bus_error_queue' => [
                    'name' => 'command_bus_error_queue',
                    'exchanges' => [
                        'command_bus_error_exchange' => [
                            [
                                'routing_keys' => [
                                    '#'
                                ],
                            ],
                        ],
                    ],
                    'connection' => 'default-amqp-connection',
                ],
                'command_bus_queue' => [
                    'name' => 'command_bus_queue',
                    'exchanges' => [
                        'command_bus_exchange' => [
                            [
                                'routing_keys' => [
                                    '#'
                                ],
                            ],
                        ],
                        'delayed_command_bus_exchange' => [
                            [
                                'routing_keys' => [
                                    '#'
                                ],
                            ],
                        ],
                    ],
                    'arguments' => [
                        'x-dead-letter-exchange' => 'command_bus_error_exchange',
                    ],
                    'connection' => 'default-amqp-connection',
                ],
            ],
            'callback_consumer' => [
                'command_consumer' => [
                    'queue' => 'command_bus_queue',
                    'delivery_callback' => 'command_consumer_callback',
                    'idle_timeout' => 3,
                    'qos'          => [
                        'prefetch_count' => 2
                    ],
                    'logger' => 'command_consumer_logger',
                ],
            ],
        ],
    ],
    'prooph' => [
        'humus-amqp-producer' => [
            'command_consumer_callback' => [
                'command_consumer_callback' => [
                    'command_bus' => 'amqp_command_bus',
                    'message_factory' => \Prooph\Common\Messaging\FQCNMessageFactory::class,
                ],
            ],
        ],
        'service_bus' => [
            'amqp_command_bus' => [
                // amqp command bus settings
            ],
            'command_bus' => [
                // command bus settings, all or at least some commands are send to the amqp_command_bus  
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'default-amqp-connection' => [
                \Humus\Amqp\Container\ConnectionFactory::class,
                'default-amqp-connection'
            ],
            'command_consumer' => [
                \Humus\Amqp\Container\CallbackConsumerFactory::class,
                'command_consumer'
            ],
            'command_consumer_callback' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpCommandConsumerCallbackFactory::class,
                'command_consumer_callback'
            ],
        ],
    ],
];
```

What do we see here? In the `humus` section, a connection, used driver, some exchanges and queues and the
callback consumer are defined and configured. In the `dependencies` section the required factories are
registered in the container via the provided factory. The important section for this library, is the section
`prooph` - `humus-amqp-producer`: what we have here is a command consumer callback, that is passed the
used message factory as well as the used command bus to handle the incoming commands.
Whenever an error occurs, the command will be routed to the command_bus_error_queue, according to the settings
in the `humus` section.

You can then start the command consumer with

```
$ ./vendor/bin/humus-amqp consumer -c command_consumer
```

This command consumer is configured to also handle delayed commands (commands that are executed at a later
point in time).
