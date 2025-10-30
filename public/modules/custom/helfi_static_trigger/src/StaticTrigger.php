<?php

declare(strict_types=1);

namespace Drupal\helfi_static_trigger;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Provides a service to trigger static site re-generation.
 */
class StaticTrigger implements StaticTriggerInterface, LoggerAwareInterface {

  use LoggerAwareTrait;
  use StringTranslationTrait;

  public function __construct(protected ConfigFactoryInterface $configFactory, protected ClientInterface $httpClient, protected StateInterface $state, protected TimeInterface $time) {
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

    try {
      $options = [];
      $options['verify'] = FALSE;
      if (!$force) {
        $options['timeout'] = 5;
      }
      $options['body'] = $config->get('body');
      $options['headers'] = $config->get('headers');
      $this->httpClient->request($config->get('method'), $config->get('url'), $options);
    }
    catch (\Exception $e) {
      $this->logger?->error($e->getMessage());
      return FALSE;
    }

    $this->logger?->info(
      $this->t('Triggered static site re-generation at @url.', [
        '@url' => $config->get('url'),
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
