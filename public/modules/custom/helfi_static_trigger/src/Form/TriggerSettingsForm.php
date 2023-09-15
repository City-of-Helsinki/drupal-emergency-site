<?php

namespace Drupal\helfi_static_trigger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for static copy of the website.
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
      '#description' =>
      $this->t('The URL to trigger static site re-generation.
      You can set this via HELFI_STATIC_TRIGGER_URL environment variable.'),
      '#default_value' =>
      $this->config('helfi_static_trigger.settings')->get('url'),
      '#required' => TRUE,
    ];

    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => [
        'GET' => 'GET',
        'POST' => 'POST',
      ],
      '#description' => $this
        ->t('The method to trigger static site re-generation.'),
      '#default_value' => $this
        ->config('helfi_static_trigger.settings')->get('method'),
      '#required' => TRUE,
    ];

    $form['headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Headers'),
      '#description' => $this
        ->t('The headers to trigger static site re-generation.'),
      '#default_value' => $this
        ->config('helfi_static_trigger.settings')->get('headers'),
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#description' => $this
        ->t('The body to trigger static site re-generation.'),
      '#default_value' => $this
        ->config('helfi_static_trigger.settings')->get('body'),
      '#required' => TRUE,
    ];

    $form['safe_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Safety delay'),
      '#description' => $this
        ->t('Seconds to wait before re-triggering.'),
      '#default_value' => $this
        ->config('helfi_static_trigger.settings')->get('safe_delay'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('helfi_static_trigger.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('method', $form_state->getValue('method'))
      ->set('headers', $form_state->getValue('headers'))
      ->set('body', $form_state->getValue('body'))
      ->set('safe_delay', $form_state->getValue('safe_delay'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
