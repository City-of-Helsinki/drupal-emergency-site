<?php

namespace Drupal\Tests\helfi_static_trigger\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\helfi_static_trigger\StaticTrigger;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the StaticTrigger service.
 *
 * @group helfi_static_trigger
 */
class StaticTriggerTest extends UnitTestCase {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * The StaticTrigger service.
   *
   * @var \Drupal\helfi_static_trigger\StaticTrigger
   */
  protected $staticTrigger;

  /**
   * The immutable config service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $immutableConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerFactory = $this->createConfiguredMock(LoggerChannelFactoryInterface::class,
      [
        'get' => new LoggerChannel('test'),
      ]);

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->httpClient->method('request')->willReturn(new Response(200, ['Content-Type' => 'application/json']));
    $this->state = $this->createMock(StateInterface::class);

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->immutableConfig = $this->createConfiguredMock(ImmutableConfig::class, ['get' => 300]);
    $this->configFactory->method('get')->willReturn($this->immutableConfig);

    $this->time = $this->createMock(TimeInterface::class);

    $this->staticTrigger = new StaticTrigger($this->configFactory, $this->loggerFactory, $this->httpClient, $this->state, $this->time);

    $this->staticTrigger->setStringTranslation($this->getStringTranslationStub());

  }

  /**
   * Tests the trigger method.
   *
   * @dataProvider triggerDataProvider
   */
  public function testTrigger($force, $getLastRun, $time, $expectedResult) {
    $this->time->method('getCurrentTime')->willReturn($time);

    $this->state->method('get')->willReturn($getLastRun);

    $result = $this->staticTrigger->trigger($force);

    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Data provider for testTrigger.
   */
  public function triggerDataProvider() {
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
