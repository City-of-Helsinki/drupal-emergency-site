<?php

declare(strict_types=1);

namespace Drupal\helfi_emergency_general\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\helfi_platform_config\DTO\ParagraphTypeCollection;

/**
 * Platform config hooks.
 *
 * @codeCoverageIgnore
 */
final readonly class PlatformConfig {

  /**
   * Implements hook_platform_config_grant_permissions().
   *
   * @return array<string, string[]>
   *   Permissions to grant, keyed by role ID.
   */
  #[Hook('platform_config_grant_permissions')]
  public function grantPermissions(): array {
    return [
      'admin' => [
        'modify announcement message',
      ],
    ];
  }

  /**
   * Implements hook_helfi_paragraph_types().
   *
   * @return \Drupal\helfi_platform_config\DTO\ParagraphTypeCollection[]
   *   The enabled paragraph types.
   */
  #[Hook('helfi_paragraph_types')]
  public function paragraphTypes(): array {
    $entities = [
      'node' => [
        'landing_page' => [
          'field_content' => [
            'crisis_news_paragraph',
          ],
        ],
      ],
      'paragraph' => [
        'columns' => [
          'field_columns_right_column' => [
            'crisis_news_paragraph',
          ],
        ],
      ],
    ];

    $enabled = [];
    foreach ($entities as $entityTypeId => $bundles) {
      foreach ($bundles as $bundle => $fields) {
        foreach ($fields as $field => $paragraphTypes) {
          foreach ($paragraphTypes as $paragraphType) {
            $enabled[] = new ParagraphTypeCollection($entityTypeId, $bundle, $field, $paragraphType);
          }
        }
      }
    }
    return $enabled;
  }

}
