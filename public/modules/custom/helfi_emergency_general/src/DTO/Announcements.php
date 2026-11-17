<?php

declare(strict_types=1);

namespace Drupal\helfi_emergency_general\DTO;

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
   */
  public function __construct(
    public array $messages = [],
  ) {
  }

  /**
   * Creates a new instance from the blob storage response body.
   *
   * @param string $json
   *   The JSON response body.
   *
   * @return self
   *   The announcements.
   */
  public static function fromJson(string $json): self {
    $data = json_decode($json);

    if (!$data instanceof \stdClass) {
      return new self();
    }

    $messages = array_filter(
      get_object_vars($data),
      static fn ($message, $langcode) => is_string($langcode) && is_string($message),
      ARRAY_FILTER_USE_BOTH,
    );

    return new self($messages);
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
