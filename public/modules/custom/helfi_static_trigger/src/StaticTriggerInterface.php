<?php

namespace Drupal\helfi_static_trigger;

/**
 * Defines an interface for the StaticTrigger service.
 */
interface StaticTriggerInterface {

  /**
   * Triggers the regeneration of the static site.
   *
   * @param bool $force
   *   (optional) Whether to force the trigger, even if the minimum time interval
   *   has not passed. Defaults to FALSE.
   *
   * @return bool|null
   *   TRUE if the trigger was successful, FALSE if an error occurred, or NULL if
   *   the trigger was skipped due to time constraints.
   */
  public function trigger($force = FALSE): ?bool;

  /**
   * Get the timestamp of the last trigger run.
   *
   * @return int|null
   *   The timestamp of the last trigger run, or NULL if not available.
   */
  public function getLastRun(): ?int;
}
