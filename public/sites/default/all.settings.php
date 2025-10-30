<?php

/**
 * @file
 * Contains site specific overrides.
 */

if (getenv('SMTP_HOST')) {
  $config['smtp.settings']['smtp_host'] = getenv('SMTP_HOST');
  $config['smtp.settings']['smtp_port'] = getenv('SMTP_PORT');
  $config['smtp.settings']['smtp_username'] = getenv('MAILGUN_SMTP_USER');
  $config['smtp.settings']['smtp_password'] = getenv('MAILGUN_SMTP_PASSWORD');
  $config['smtp.settings']['smtp_from'] = getenv('MAILGUN_SMTP_USER');
  $config['smtp.settings']['smtp_fromname'] = getenv('MAILGUN_SMTP_USER');
  $config['smtp.settings']['smtp_on'] = true;
  $config['smtp.settings']['smtp_protocol'] = 'tls';
  $config['smtp.settings']['smtp_autotls'] = true;
  $config['smtp.settings']['smtp_allowhtml'] = 1;
}

if ($static_trigger_url = getenv('HELFI_STATIC_TRIGGER_URL')) {
  $config['helfi_static_trigger.settings']['url'] = $static_trigger_url;
}

$config['helfi_static_trigger.settings']['body'] = json_encode([
  'environment' => getenv('APP_ENV'),
]);
