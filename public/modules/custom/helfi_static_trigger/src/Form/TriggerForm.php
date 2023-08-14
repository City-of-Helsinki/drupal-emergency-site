<?php

namespace Drupal\helfi_static_trigger\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\helfi_static_trigger\StaticTrigger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for triggering static site re-generation.
 */
class TriggerForm extends FormBase {

  /**
   * The Static Trigger service.
   *
   * @var \Drupal\helfi_static_trigger\StaticTrigger
   */
  protected StaticTrigger $staticTrigger;

  /**
   * Class constructor.
   *
   * @param \Drupal\helfi_static_trigger\StaticTrigger $staticTrigger
   *   The Static Trigger service.
   */
  public function __construct(StaticTrigger $staticTrigger) {
    $this->staticTrigger = $staticTrigger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): TriggerForm {
    return new static(
      $container->get('helfi_static_trigger.trigger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helfi_static_trigger_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Trigger static site re-generation.'),
    ];

    $lastTriggered = $this->staticTrigger->getLastRun();
    if ($lastTriggered) {
      $form['last_triggered'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Was last time triggered at @date.', [
          '@date' => date('Y-m-d H:i:s', $lastTriggered)
        ]),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Trigger'),
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->staticTrigger->trigger(TRUE)) {
      $this->messenger()->addStatus($this->t(
        'Static site re-generation triggered.'));
    }
    else {
      $this->messenger()->addError($this->t(
        'Failed to trigger static site re-generation.'));
    }
  }

}
