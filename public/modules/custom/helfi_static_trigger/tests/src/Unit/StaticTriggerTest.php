<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_static_trigger\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;
use Drupal\helfi_static_trigger\StaticTrigger;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the StaticTrigger service.
 *
 * @group helfi_static_trigger
 */
class StaticTriggerTest extends UnitTestCase {

  /**
   * The HTTP client.
   */
  protected MockObject $httpClient;

  /**
   * The state.
   */
  protected MockObject $state;

  /**
   * The time service.
   */
  protected MockObject $time;

  /**
   * The StaticTrigger service.
   */
  protected StaticTrigger $staticTrigger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->httpClient->method('request')->willReturn(new Response(200, ['Content-Type' => 'application/json']));
    $this->state = $this->createMock(StateInterface::class);

    $configFactory = $this->getConfigFactoryStub([
      'helfi_static_trigger.settings' => [
        'url' => 'https://example.com',
        'body' => '{}',
        'headers' => ['Content-Type' => 'application/json'],
        'method' => 'POST',
        'safe_delay' => 300,
      ],
    ]);

    $this->time = $this->createMock(TimeInterface::class);

    $this->staticTrigger = new StaticTrigger($configFactory, $this->httpClient, $this->state, $this->time);

    $this->staticTrigger->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests the trigger method.
   *
   * @dataProvider triggerDataProvider
   */
  public static function testTrigger($force, $getLastRun, $time, $expectedResult): void {
    $test = new static('test');
    $test->setUp();

    $test->time->method('getCurrentTime')->willReturn($time);
    $test->state->method('get')->willReturn($getLastRun);

    $result = $test->staticTrigger->trigger($force);
    $test->assertEquals($expectedResult, $result);
  }

  /**
   * Data provider for testTrigger.
   */
  public function triggerDataProvider(): array {
    return [
      // Test case 1: Force is TRUE, should always return TRUE.
      [TRUE, NULL, 0, TRUE],

      // Test case 2: Force is FALSE, and it's safe to run.
      [FALSE, time() - 301, time(), TRUE],

      // Test case 3: Force is FALSE, not safe to run, and next run is not set.
      [FALSE, NULL, 0, NULL],

      // Test case 4: Force is FALSE, not safe to run, and next run is set.
      [FALSE, time() - 29, time(), NULL],

      // Test case 5: Force is FALSE, and it's not safe to run because of delay.
      [FALSE, time() - 31, time(), NULL],
    ];
  }

}
