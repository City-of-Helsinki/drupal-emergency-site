<?php

/**
 * @file
 * Contains site specific overrides.
 */

if ($static_trigger_url = getenv('HELFI_STATIC_TRIGGER_URL')) {
  $config['helfi_static_trigger.settings']['url'] = $static_trigger_url;
}

$config['helfi_static_trigger.settings']['body'] = json_encode([
  'environment' => getenv('APP_ENV'),
]);
