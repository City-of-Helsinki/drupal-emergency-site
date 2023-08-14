<?php

namespace Drupal\helfi_static_trigger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TriggerSettingsForm.
 *
 * @package Drupal\helfi_static_trigger\Form
 */
class TriggerSettingsForm extends ConfigFormBase {

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      'helfi_static_trigger.settings',
    ];
  }

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'helfi_static_trigger_settings_form';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The URL to trigger.'),
      '#default_value' =>
        $this->config('helfi_static_trigger.settings')->get('url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('helfi_static_trigger.settings')
      ->set('url', $form_state->getValue('url'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
