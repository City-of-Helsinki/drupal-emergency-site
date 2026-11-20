<?php

declare(strict_types=1);

namespace Drupal\helfi_emergency_general\DTO;

use Psr\Http\Message\ResponseInterface;

/**
 * DTO for the announcements stored in the Azure blob storage.
 *
 * @code
 * {
 *   "fi": {"title": "Otsikko", "body": "Finnish announcement"},
 *   "sv": {"title": "Rubrik", "body": "Swedish announcement"}
 * }
 * @endcode
 */
final readonly class Announcements implements \JsonSerializable {

  /**
   * Constructs a new instance.
   *
   * @param array<string, array{title: string, body: string}> $messages
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

    $messages = [];

    if ($data instanceof \stdClass) {
      foreach (get_object_vars($data) as $langcode => $message) {
        if (!is_string($langcode) || !$message instanceof \stdClass) {
          continue;
        }

        $messages[$langcode] = [
          'title' => is_string($message->title ?? NULL) ? $message->title : '',
          'body' => is_string($message->body ?? NULL) ? $message->body : '',
        ];
      }
    }

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
   * @param string $title
   *   The announcement title.
   * @param string $body
   *   The announcement body.
   *
   * @return self
   *   The new instance.
   */
  public function with(string $langcode, string $title, string $body): self {
    return new self(
      [$langcode => ['title' => $title, 'body' => $body]] + $this->messages,
      $this->updated,
      $this->etag,
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, array{title: string, body: string}>
   *   The announcement messages, keyed by language code.
   */
  public function jsonSerialize(): array {
    return $this->messages;
  }

}
