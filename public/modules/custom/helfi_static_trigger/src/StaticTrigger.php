<?php

namespace Drupal\helfi_static_trigger;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

class StaticTrigger implements StaticTriggerInterface {

  use StringTranslationTrait;

  public ConfigFactoryInterface $configFactory;
  public ClientInterface $httpClient;
  public StateInterface $state;
  public LoggerChannelInterface $logger;
  public TimeInterface $time;
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $logger, ClientInterface $httpClient, StateInterface $state, TimeInterface $time) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $this->state = $state;
    $this->logger = $logger->get('helfi_static_trigger');
    $this->time = $time;
  }

  public function trigger($force = FALSE): bool|null {
    if (!$force && $this->getLastRun() + 30 > $this->time->getCurrentTime()) {
      return NULL;
    }
    $url = $this->configFactory
      ->get('helfi_static_trigger.settings')->get('url');
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

  public function getLastRun() {
    return $this->state->get('helfi_static_trigger.last_triggered');
  }

}
