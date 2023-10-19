<?php

namespace Drupal\helfi_emergency_general;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Announcement client for dealing with requests to azure storage.
 */
class AnnouncementClient {
  use StringTranslationTrait;

  /**
   * A fully-configured Guzzle client to pass to the client.
   *
   * @var \GuzzleHttp\Client
   */
  protected ClientInterface $httpClient;

  /**
   * Config Factory var.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  /**
   * Announcement logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  /**
   * SAS url.
   *
   * @var string|null
   */
  protected $sasUrl = NULL;

  /**
   * Current message json.
   *
   * @var array|null
   */
  protected array|NULL $currentAnnouncement = NULL;

  /**
   * The AnnouncementClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   GuzzleHttp service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel service.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config,
    LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    $this->httpClient = $http_client;
    $this->config = $config;
    $this->loggerChannel = $loggerChannelFactory->get('helfi_emergency_general');
    $this->sasUrl = base64_decode(getenv('announcement_sas_url_base64_enc') ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Method that sends a new message.
   *
   * @param string $announcement
   *   The message to be sent.
   * @param string $langCode
   *   Lang code.
   *
   * @return bool
   *   If success or not.
   */
  public function setAnnouncement(string $announcement, string $langCode): bool {
    $currentAnnouncement = $this->currentAnnouncement ?? [];
    $currentAnnouncement[$langCode] = $announcement;
    try {
      $this->httpClient->request('PUT', $this->sasUrl, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
        'body' => json_encode($currentAnnouncement),
      ]);
      $this->currentAnnouncement = $currentAnnouncement;
    }
    catch (GuzzleException $e) {
      $this->loggerChannel->error($e->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Method that returns the current message.
   *
   * @param string $langCode
   *   Lang code.
   *
   * @return string|null
   *   Returns the current message or null if error.
   */
  public function getAnnouncement(string $langCode): string|null {
    if ($this->currentAnnouncement) {
      return $this->currentAnnouncement[$langCode] ?? '';
    }
    try {
      $response_content = $this->httpClient->request('GET', $this->sasUrl, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
      ])->getBody()->getContents();
      $this->currentAnnouncement = json_decode($response_content, TRUE) ?? NULL;

      return $this->currentAnnouncement[$langCode] ?? '';
    }
    catch (GuzzleException $e) {
      $this->loggerChannel->error($e->getMessage());
    }

    return NULL;
  }

}
