# Message Producers

There are four types of message producers shipped with the library.
The command producer, delayed command producer, event producer and query producer.

First, an example configuration (the configuration of connection, exchanges and queues is skipped):

```php
return [
    'humus' => [
        'amqp' => [
            'producer' => [
                'command_producer' => [
                    'type' => 'json',
                    'exchange' => 'command_bus_exchange',
                ],
                'delayed_command_producer' => [
                    'type' => 'json',
                    'exchange' => 'delayed_command_bus_exchange',
                ],
                'event_producer' => [
                    'type' => 'json',
                    'exchange' => 'event_bus_exchange',
                ],
                'query_producer' => [
                    'type' => 'json',
                    'exchange' => 'query_bus_exchange',
                ],
            ],
        ],
    ],
    'prooph' => [
        'humus-amqp-producer' => [
            'message_producer' => [
                'amqp_command_producer' => [
                    'producer'          => 'command_producer',
                    'app_id'            => 'my app',
                    'message_converter' => \Prooph\Common\Messaging\NoOpMessageConverter::class,
                ],
                'amqp_event_producer' => [
                    'producer'          => 'event_producer',
                    'app_id'            => 'my app',
                    'message_converter' => \Prooph\Common\Messaging\NoOpMessageConverter::class,
                ],
                'amqp_query_producer' => [
                    'producer'          => 'query_producer',
                    'app_id'            => 'my app',
                    'message_converter' => \Prooph\Common\Messaging\NoOpMessageConverter::class,
                ],
            ],
            'delayed_message_producer' => [
                'amqp_delayed_command_producer' => [
                    'producer'          => 'delayed_command_producer',
                    'app_id'            => 'dimabay partner portal',
                    'message_converter' => \Prooph\Common\Messaging\NoOpMessageConverter::class,
                ],
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'command_producer' => [
                \Humus\Amqp\Container\ProducerFactory::class,
                'command_producer'
            ],
            'delayed_command_producer' => [
                \Humus\Amqp\Container\ProducerFactory::class,
                'delayed_command_producer'
            ],
            'event_producer' => [
                \Humus\Amqp\Container\ProducerFactory::class,
                'event_producer'
            ],
            'query_producer' => [
                \Humus\Amqp\Container\ProducerFactory::class,
                'query_producer'
            ],
            'amqp_command_producer' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpMessageProducerFactory::class,
                'amqp_command_producer'
            ],
            'amqp_delayed_command_producer' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpDelayedMessageProducerFactory::class,
                'amqp_delayed_command_producer'
            ],
            'amqp_event_producer' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpMessageProducerFactory::class,
                'amqp_event_producer'
            ],
            'amqp_query_producer' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpMessageProducerFactory::class,
                'amqp_query_producer'
            ],
        ],
    ],
];
```

In the `humus` section the bare metal producers are configured.
The the `prooph` - `humus-amqp-producer` the producers are wrapped with some additional stuff needed.
The most important is the used `message_converter`. The app_id is an id that is passed to AMQP, so you can see
from which application a message was coming.

You can then use the message producer directly:

```php
$producer = $container->get('amqp_command_producer');
$producer($command);

$producer = $container->get('amqp_query_producer');
$producer($query, $deferred);
```

You can also route a specific message to the required producer, f.e. an command bus here:

```php
return [
    'prooph' => [
        'service_bus' => [
            'command_bus' => [
                'router' => [
                    'routes' => [
                        \Prooph\Snapshotter\TakeSnapshot::class => 'amqp_command_producer',
                    ],
                ],
            ],
        ],
    ],
];
```

You can also route all messages to the required producer, f.e. an event bus here:

```php
return [
    'prooph' => [
        'service_bus' => [
            'event_bus' => [
                'plugins' => [
                    'amqp_event_producer_plugin',
                ],
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'amqp_event_producer_plugin' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpMessageProducerFactory,
                'amqp_event_producer'
            ],
        ],
    ],
];
```
