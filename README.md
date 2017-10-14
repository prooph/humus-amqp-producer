# Prooph ServiceBus HumusAmqp Producer

Use [HumusAmqp](https://github.com/prolic/HumusAmqp) as message producer for [Prooph Service Bus](https://github.com/prooph/service-bus).

[![Build Status](https://travis-ci.org/prooph/humus-amqp-producer.svg?branch=master)](https://travis-ci.org/prooph/humus-amqp-producer)
[![Coverage Status](https://coveralls.io/repos/prooph/humus-amqp-producer/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/humus-amqp-producer?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

## Differences betweeen v1 and v2

v1 is compatible with [Prooph\ServiceBus](http://github.com/prooph/service-bus) 5.0 while the newer v2
supports 6.0 of `Prooph\ServiceBus`.

## Installation

You can install prooph/humus-amqp-producer via composer by adding `"prooph/humus-amqp-producer": "^1.0"` as requirement to your composer.json.

## Documentation

Documentation is [in the docs tree](docs/), and can be compiled using [bookdown](http://bookdown.io).

```console
$ php ./vendor/bin/bookdown docs/bookdown.json
$ php -S 0.0.0.0:8080 -t docs/html/
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

Note: HumusAmqp is not covered in the documentation. If you're new to AMQP and/ or RabbitMQ and you don't know
how to configure HumusAmqp, take a look at the [HumusAmqp Docs](https://humusamqp.readthedocs.io/en/latest/) first.

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- File issues at [https://github.com/prooph/humus-amqp-producer/issues](https://github.com/prooph/humus-amqp-producer/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## License

Released under the [New BSD License](LICENSE).
