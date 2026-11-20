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
   * Announcement blob URL without the SAS token.
   */
  private const string BLOB_URL = 'https://helfibackupsdc.blob.core.windows.net/$web/emergency_announcement.json';

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
    $token = base64_decode((string) getenv('announcement_sas_url_base64_enc'));

    return self::BLOB_URL . '?' . ltrim(trim($token), '?');
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
   * @param string $title
   *   The announcement title.
   * @param string $body
   *   The announcement body.
   * @param string $langCode
   *   Lang code.
   *
   * @throws \Drupal\helfi_emergency_general\AnnouncementException
   */
  public function setAnnouncement(string $title, string $body, string $langCode): void {
    $currentAnnouncement = $this->loadAnnouncements()
      ->with($langCode, $title, $body);

    // Only write if the blob is unchanged since it was read.
    // Concurrent writes fail instead of silently overwriting data.
    $condition = $currentAnnouncement->etag !== NULL
      ? ['If-Match' => $currentAnnouncement->etag]
      : ['If-None-Match' => '*'];

    try {
      $response = $this->httpClient->request('PUT', $this->getSasUrl(), [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ] + $condition,
        RequestOptions::BODY => json_encode($currentAnnouncement),
        RequestOptions::TIMEOUT => 15,
      ]);

      $this->currentAnnouncement = $currentAnnouncement
        ->withResponseMetadata($response);
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
   * @return array{title: string, body: string}
   *   Returns the current message. Values are empty strings if none is set.
   *
   * @throws \Drupal\helfi_emergency_general\AnnouncementException
   */
  public function getAnnouncement(string $langCode): array {
    return $this->loadAnnouncements()->messages[$langCode] ?? ['title' => '', 'body' => ''];
  }

  /**
   * Loads the announcements from blob storage.
   *
   * @throws \Drupal\helfi_emergency_general\AnnouncementException
   */
  private function loadAnnouncements(): Announcements {
    if ($this->currentAnnouncement) {
      return $this->currentAnnouncement;
    }

    try {
      $response = $this->httpClient->request('GET', $this->getSasUrl(), [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
        RequestOptions::TIMEOUT => 15,
      ]);

      return $this->currentAnnouncement = Announcements::fromResponse($response);
    }
    catch (GuzzleException | \JsonException $e) {
      $this->loggerChannel->error($e->getMessage());

      throw new AnnouncementException($e->getMessage(), $e->getCode(), previous: $e);
    }
  }

}
