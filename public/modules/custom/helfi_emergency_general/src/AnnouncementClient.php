<?php

namespace Drupal\helfi_emergency_general;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Announcement client for dealing with requests to azure storage.
 */
final class AnnouncementClient {

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
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   GuzzleHttp service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config service.
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Announcement logger channel.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $config,
    #[Autowire(service: 'logger.channel.helfi_emergency_general')]
    private readonly LoggerInterface $loggerChannel,
  ) {
    $this->sasUrl = base64_decode(getenv('announcement_sas_url_base64_enc') ?? '');
  }

  /**
   * Gets the expiration of the blob storage SAS token.
   *
   * @return string
   *   The expiration, or an empty string if it is not set.
   */
  public function getSasExpiration(): string {
    return (string) getenv('announcement_sas_expiration');
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
