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
   *   Unix timestamp of the last update, read from the blob's Last-Modified
   *   header. NULL if unknown or not yet saved.
   */
  public function __construct(
    public array $messages = [],
    public ?int $updated = NULL,
  ) {
  }

  /**
   * Creates a new instance from a blob storage response.
   *
   * @throws \JsonException
   */
  public static function fromResponse(ResponseInterface $response): self {
    $updated = self::getLastModified($response);
    $data = json_decode((string) $response->getBody(), flags: JSON_THROW_ON_ERROR);

    if (!$data instanceof \stdClass) {
      return new self(updated: $updated);
    }

    $messages = array_filter(
      get_object_vars($data),
      static fn ($message, $langcode) => is_string($langcode) && is_string($message),
      ARRAY_FILTER_USE_BOTH,
    );

    return new self($messages, $updated);
  }

  /**
   * Gets the Last-Modified header of a blob storage response.
   *
   * @return int|null
   *   Unix timestamp, or NULL if the header is missing or invalid.
   */
  private static function getLastModified(ResponseInterface $response): ?int {
    $timestamp = strtotime($response->getHeaderLine('Last-Modified'));

    return $timestamp === FALSE ? NULL : $timestamp;
  }

  /**
   * Returns a copy with the update time read from a blob storage response.
   *
   * @return self
   *   The new instance.
   */
  public function withLastModified(ResponseInterface $response): self {
    return new self($this->messages, self::getLastModified($response));
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
    return new self([$langcode => $message] + $this->messages);
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
