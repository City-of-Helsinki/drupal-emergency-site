<?php

namespace Drupal\helfi_static_trigger;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
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
   * Constructs a StaticTrigger object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
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
    if (!$force && $this->getLastRun() + 30 > $this->time->getCurrentTime()) {
      return NULL;
    }

    $url = $this->configFactory
      ->get('helfi_static_trigger.settings')->get('url');
    if ($_ENV['HELFI_STATIC_TRIGGER_URL']) {
      $url = $_ENV['HELFI_STATIC_TRIGGER_URL'];
    }

    try {
      $options = [];
      $options['verify'] = FALSE;
      if (!$force) {
        $options['timeout'] = 5;
      }
      $this->httpClient->request('GET', $url, $options);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return FALSE;
    }

    $this->logger->info(
      $this->t('Triggered static site re-generation at @url.', [
      '@url' => $url,
    ]));

    $this->state->set('helfi_static_trigger.last_triggered',
      $this->time->getCurrentTime());

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastRun() {
    return $this->state->get('helfi_static_trigger.last_triggered');
  }

}
