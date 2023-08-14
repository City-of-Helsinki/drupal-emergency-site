<?php

namespace Drupal\Tests\helfi_static_trigger\Unit;

use Drupal\helfi_static_trigger\StaticTrigger;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->time = $this->createMock(TimeInterface::class);

    $this->staticTrigger = new StaticTrigger($this->configFactory, $this->loggerFactory, $this->httpClient, $this->state, $this->time);
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
      [FALSE, NULL, 0, TRUE],
      [TRUE, NULL, 0, TRUE],
      [FALSE, time() - 29, time(), NULL],
      [FALSE, time() - 31, time(), TRUE],
    ];
  }

}
