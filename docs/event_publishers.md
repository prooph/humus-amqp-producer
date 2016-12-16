# Event Publishers

While you can publish events with a message producer plugin, you may want to use amqp transactions or using
RabbitMQ the confirm-select-extension. This is also achievable very easy.

## Transactional Event Publisher

Again, let's start with an example configuration (amqp connection, exchange and queue settings skipped):

```php
return [
    'humus' => [
        'amqp' => [
            'producer' => [
                'event_producer' => [
                    'type' => 'json',
                    'exchange' => 'event_bus_exchange',
                ],
            ],
        ],
    ],
    'prooph' => [
        'humus-amqp-producer' => [
            'transactional_event_publisher' => [
                'transactional_event_publisher' => [
                    'producer' => 'event_producer',
                    'event_bus' => EventBus::class,
                    'timeout' => 3.0
                ]
            ],
        ],
        'event_store' => [
            'plugins' => [
                \Prooph\ServiceBus\Message\HumusAmqp\TransactionalEventPublisher::class,
            ],
        ],
    ],
    'dependencies' => [
        \Prooph\ServiceBus\Message\HumusAmqp\TransactionalEventPublisher::class => [
            \Prooph\ServiceBus\Message\HumusAmqp\Container\TransactionalEventPublisherFactory::class,
            'transactional_event_publisher'
        ],        
    ],
];
```

This will catch all events from the event store after commit and publish them transactional to the 
`event_bus_exchange`.

## Confirm Select Event Publisher

Again, let's start with an example configuration (amqp connection, exchange and queue settings skipped):

```php
return [
    'humus' => [
        'amqp' => [
            'producer' => [
                'event_producer' => [
                    'type' => 'json',
                    'exchange' => 'event_bus_exchange',
                ],
            ],
        ],
    ],
    'prooph' => [
        'humus-amqp-producer' => [
            'confirm_select_event_publisher' => [
                'confirm_select_event_publisher' => [
                    'producer' => 'event_producer',
                    'event_bus' => EventBus::class,
                    'timeout' => 3.0
                ]
            ],
        ],
        'event_store' => [
            'plugins' => [
                \Prooph\ServiceBus\Message\HumusAmqp\ConfirmSelectEventPublisher::class,
            ],
        ],
    ],
    'dependencies' => [
        \Prooph\ServiceBus\Message\HumusAmqp\ConfirmSelectEventPublisher::class => [
            \Prooph\ServiceBus\Message\HumusAmqp\Container\ConfirmSelectEventPublisherFactory::class,
            'confirm_select_event_publisher'
        ],        
    ],
];
```

This will catch all events from the event store after commit and publish them with confirm select mode to the 
`event_bus_exchange`.
