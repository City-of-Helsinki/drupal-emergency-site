<?php

declare(strict_types=1);

namespace Drupal\helfi_emergency_general\DTO;

use Psr\Http\Message\ResponseInterface;

/**
 * DTO for the announcements stored in the Azure blob storage.
 *
 * @code
 * {"fi": "Finnish announcement", "sv": "Swedish announcement"}
 * @endcode
 */
final readonly class Announcements implements \JsonSerializable {

  /**
   * Constructs a new instance.
   *
   * @param array<string, string> $messages
   *   The announcement messages, keyed by language code.
   * @param int|null $updated
   *   Unix timestamp of the last update.
   * @param string|null $etag
   *   The blob's ETag.
   */
  public function __construct(
    public array $messages = [],
    public ?int $updated = NULL,
    public ?string $etag = NULL,
  ) {
  }

  /**
   * Creates a new instance from a blob storage response.
   *
   * @throws \JsonException
   */
  public static function fromResponse(ResponseInterface $response): self {
    $data = json_decode((string) $response->getBody(), flags: JSON_THROW_ON_ERROR);

    $messages = $data instanceof \stdClass ? array_filter(
      get_object_vars($data),
      static fn ($message, $langcode) => is_string($langcode) && is_string($message),
      ARRAY_FILTER_USE_BOTH,
    ) : [];

    return (new self($messages))->withResponseMetadata($response);
  }

  /**
   * Returns a copy with the blob metadata read from a blob storage response.
   *
   * @return self
   *   The new instance.
   */
  public function withResponseMetadata(ResponseInterface $response): self {
    $updated = strtotime($response->getHeaderLine('Last-Modified'));
    $etag = $response->getHeaderLine('ETag');

    return new self(
      $this->messages,
      $updated === FALSE ? NULL : $updated,
      $etag === '' ? NULL : $etag,
    );
  }

  /**
   * Returns a copy with the announcement set for the given language.
   *
   * @param string $langcode
   *   The language code.
   * @param string $message
   *   The announcement.
   *
   * @return self
   *   The new instance.
   */
  public function with(string $langcode, string $message): self {
    return new self([$langcode => $message] + $this->messages, $this->updated, $this->etag);
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, string>
   *   The announcement messages, keyed by language code.
   */
  public function jsonSerialize(): array {
    return $this->messages;
  }

}
