<?php

namespace Drupal\helfi_emergency_general;

use Drupal\helfi_emergency_general\DTO\Announcements;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Announcement client for dealing with requests to azure storage.
 */
final class AnnouncementClient {

  /**
   * Request timeout in seconds.
   */
  private const int TIMEOUT = 15;

  /**
   * Current announcements.
   *
   * @var \Drupal\helfi_emergency_general\DTO\Announcements|null
   */
  protected ?Announcements $currentAnnouncement = NULL;

  /**
   * The AnnouncementClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   GuzzleHttp service.
   * @param \Psr\Log\LoggerInterface $loggerChannel
   *   Announcement logger channel.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    #[Autowire(service: 'logger.channel.helfi_emergency_general')]
    private readonly LoggerInterface $loggerChannel,
  ) {
  }

  /**
   * Gets the blob storage SAS url.
   *
   * @return string
   *   The SAS url.
   */
  private function getSasUrl(): string {
    return base64_decode((string) getenv('announcement_sas_url_base64_enc'));
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
   * @throws \Drupal\helfi_emergency_general\AnnouncementException
   */
  public function setAnnouncement(string $announcement, string $langCode): void {
    $currentAnnouncement = ($this->currentAnnouncement ?? new Announcements())
      ->with($langCode, $announcement);
    try {
      $this->httpClient->request('PUT', $this->getSasUrl(), [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
        RequestOptions::BODY => json_encode($currentAnnouncement),
        RequestOptions::TIMEOUT => self::TIMEOUT,
      ]);
      $this->currentAnnouncement = $currentAnnouncement;
    }
    catch (GuzzleException $e) {
      $this->loggerChannel->error($e->getMessage());

      throw new AnnouncementException($e->getMessage(), $e->getCode(), previous: $e);
    }
  }

  /**
   * Method that returns the current message.
   *
   * @param string $langCode
   *   Lang code.
   *
   * @return string
   *   Returns the current message or null if error.
   *
   * @throws \Drupal\helfi_emergency_general\AnnouncementException
   */
  public function getAnnouncement(string $langCode): string {
    if ($this->currentAnnouncement) {
      return $this->currentAnnouncement->messages[$langCode] ?? '';
    }

    try {
      $response_content = $this->httpClient->request('GET', $this->getSasUrl(), [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
        RequestOptions::TIMEOUT => self::TIMEOUT,
      ])->getBody()->getContents();
      $this->currentAnnouncement = Announcements::fromJson($response_content);

      return $this->currentAnnouncement->messages[$langCode] ?? '';
    }
    catch (GuzzleException $e) {
      $this->loggerChannel->error($e->getMessage());

      throw new AnnouncementException($e->getMessage(), $e->getCode(), previous: $e);
    }
  }

}
