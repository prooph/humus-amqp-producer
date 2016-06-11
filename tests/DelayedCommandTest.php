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

namespace ProophTest\ServiceBus\Message\HumusAmqp;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\ServiceBus\Message\HumusAmqp\DelayedCommand;

/**
 * Class DelayedCommandTest
 * @package ProophTest\ServiceBus\Message\HumusAmqp
 */
class DelayedCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_from_and_to_array()
    {
        $time = (string) microtime(true);
        if (false === strpos($time, '.')) {
            $time .= '.0000';
        }
        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $messageData = [
            'message_name' => 'test-delayed-command',
            'uuid' => 'ccefedef-85e1-4fd0-b247-ed13d378b050',
            'version' => 1,
            'payload' => [],
            'metadata' => [
                'execute_at' => $now->modify('+10 seconds')->format('Y-m-d\TH:i:s.u'),
            ],
            'created_at' => $now,
        ];

        $command = $this->delayedComamnd();

        $delayedCommand = $command::fromArray($messageData);

        $this->assertEquals(10000, $delayedCommand->delay());

        $this->assertEquals($messageData, $delayedCommand->toArray());
    }

    /**
     * @test
     */
    public function it_can_call_execute_at()
    {
        $command = $this->delayedComamnd();
        $later = $command->createdAt()->modify('+5 seconds');
        $command = $command->executeAt($later);

        $this->assertEquals(5000, $command->delay());
    }

    /**
     * @return DelayedCommand
     */
    private function delayedComamnd()
    {
        return new class extends DelayedCommand {

            protected $messageName = 'test-delayed-command';

            protected $payload;

            public function __construct()
            {
                $this->init();
            }

            public function payload() : array
            {
                return $this->payload;
            }

            protected function setPayload(array $payload)
            {
                $this->payload = $payload;
            }
        };
    }
}
