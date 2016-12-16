# Event Consumer

An event consumer is a worker that waits for incoming events via AMQP. When a new event arrives, it will be
dispatched to the event bus to be handled.

The simplest way to explain how to configure a event consumer, is to show by example.
We will not cover the details of the HumusAmqp configuration, look at the
[HumusAmqp Docs](https://humusamqp.readthedocs.io/en/latest/) instead.

In the following section a event consumer is configured, we cover the details afterwards.

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
                'event_bus_error_exchange' => [
                    'name' => 'event_bus_error_exchange',
                    'type' => 'topic',
                    'connection' => 'default-amqp-connection',
                ],
                'event_bus_exchange' => [
                    'name' => 'event_bus_exchange',
                    'type' => 'topic',
                    'connection' => 'default-amqp-connection',
                ],
            ],
            'queue' => [
                'event_bus_error_queue' => [
                    'name' => 'event_bus_error_queue',
                    'exchanges' => [
                        'event_bus_error_exchange' => [
                            [
                                'routing_keys' => [
                                    '#'
                                ],
                            ],
                        ],
                    ],
                    'connection' => 'default-amqp-connection',
                ],
                'event_bus_queue' => [
                    'name' => 'event_bus_queue',
                    'exchanges' => [
                        'event_bus_exchange' => [
                            [
                                'routing_keys' => [
                                    '#'
                                ],
                            ],
                        ],
                    ],
                    'arguments' => [
                        'x-dead-letter-exchange' => 'event_bus_error_exchange',
                    ],
                    'connection' => 'default-amqp-connection',
                ],
            ],
            'event_consumer' => [
                'queue' => 'event_bus_queue',
                'delivery_callback' => 'event_consumer_callback',
                'idle_timeout' => 3,
                'qos'          => [
                    'prefetch_count' => 2
                ],
                'logger' => 'event_consumer_logger',
            ],
        ],
    ],
    'prooph' => [
        'humus-amqp-producer' => [
            'event_consumer_callback' => [
                'event_consumer_callback' => [
                    'event_bus' => 'amqp_event_bus',
                    'message_factory' => App\Event\EventFactory::class,
                ],
            ],
        ],
        'service_bus' => [
            'amqp_event_bus' => [
                // amqp event bus settings
            ],
            'event_bus' => [
                // event bus settings, all or at least some events are send to the amqp_event_bus  
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'default-amqp-connection' => [
                \Humus\Amqp\Container\ConnectionFactory::class,
                'default-amqp-connection'
            ],
            'event_consumer' => [
                \Humus\Amqp\Container\CallbackConsumerFactory::class,
                'event_consumer'
            ],
            'event_consumer_callback' => [
                \Prooph\ServiceBus\Message\HumusAmqp\Container\AmqpEventConsumerCallbackFactory::class,
                'event_consumer_callback'
            ],
        ],
    ],
];
```

What do we see here? In the `humus` section, a connection, used driver, some exchanges and queues and the
callback consumer are defined and configured. In the `dependencies` section the used `event_consumer_callback`
is registered in the container via the provided factory. The important section for this library, is the section
`prooph` - `humus-amqp-producer`: what we have here is a event consumer callback, that is passed the
used message factory as well as the used event bus to handle the incoming events.
Whenever an error occurs, the event will be routed to the event_bus_error_queue, according to the settings
in the `humus` section.

You can then start the event consumer with

```
$ ./vendor/bin/humus-amqp consumer -c event_consumer
```
