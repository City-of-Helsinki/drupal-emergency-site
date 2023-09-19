<?php

namespace Drupal\helfi_static_trigger;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Provides a service to trigger static site re-generation.
 */
class StaticTrigger implements StaticTriggerInterface {

  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a static trigger object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Http client service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $logger, ClientInterface $httpClient, StateInterface $state, TimeInterface $time) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $this->state = $state;
    $this->logger = $logger->get('helfi_static_trigger');
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function trigger($force = FALSE): bool|null {
    $config = $this->configFactory
      ->get('helfi_static_trigger.settings');

    if (!$force && !$this->isSafeToRun()) {
      // Set new next run only if one is not already set.
      if (!$this->getNextRun()) {
        $delay = $config->get('safe_delay');
        $this->setNextRun($this->getLastRun() + $delay);
      }
      return NULL;
    }

    $url = $config->get('url');
    if (array_key_exists('HELFI_STATIC_TRIGGER_URL', $_ENV) && isset($_ENV['HELFI_STATIC_TRIGGER_URL'])) {
      $url = $_ENV['HELFI_STATIC_TRIGGER_URL'];
    }

    $method = $config->get('method');
    $body = $config->get('body');
    $header_lines = explode(PHP_EOL, $config->get('headers'));
    $header_lines = array_filter($header_lines);
    $headers = [];
    foreach ($header_lines as $header_line) {
      $line = explode(':', $header_line, 2);
      if (count($line) == 2) {
        $headers[$line[0]] = trim($line[1]);
      }
    }
    $headers = array_filter($headers);
    try {
      $options = [];
      $options['verify'] = FALSE;
      if (!$force) {
        $options['timeout'] = 5;
      }
      $options['body'] = $body;
      $options['headers'] = $headers;
      $this->httpClient->request($method, $url, $options);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return FALSE;
    }

    $this->logger->info(
      $this->t('Triggered static site re-generation at @url.', [
        '@url' => $url,
      ]));

    $this->setLastRun($this->time->getCurrentTime());
    $this->deleteNextRun();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSafeToRun() :bool {
    $delay = $this->configFactory
      ->get('helfi_static_trigger.settings')
      ->get('safe_delay');
    $next_safe_trigger = $this->getLastRun() + $delay;
    return $next_safe_trigger <= $this->time->getCurrentTime();
  }

  /**
   * Sets last run state.
   *
   * @param int $timestamp
   *   Unix timestamp.
   */
  public function setLastRun(int $timestamp) :void {
    $this->state->set('helfi_static_trigger.last_triggered', $timestamp);
  }

  /**
   * Sets next run state.
   *
   * @param int $timestamp
   *   Unix timestamp.
   */
  public function setNextRun(int $timestamp) :void {
    $this->state->set('helfi_static_trigger.next_trigger', $timestamp);
  }

  /**
   * Deletes the next run state.
   */
  public function deleteNextRun() :void {
    $this->state->delete('helfi_static_trigger.next_trigger');
  }

  /**
   * {@inheritdoc}
   */
  public function getLastRun() : ?int {
    return $this->state->get('helfi_static_trigger.last_triggered');
  }

  /**
   * {@inheritdoc}
   */
  public function getNextRun() : ?int {
    return $this->state->get('helfi_static_trigger.next_trigger');
  }

}
