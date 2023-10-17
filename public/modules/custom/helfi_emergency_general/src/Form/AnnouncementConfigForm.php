<?php

namespace Drupal\helfi_emergency_general\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\helfi_emergency_general\EmergencyAnnouncementClient;

class AnnouncementConfigForm extends ConfigFormBase {

  /**
   * Emergency announcement client.
   *
   * @var \Drupal\helfi_emergency_general\EmergencyAnnouncementClient
   */
  protected $announcement_client;


  /**
   * AnnouncementForm constructor.
   *
   * @param \Drupal\helfi_emergency_general\EmergencyAnnouncementClient $announcement_client
   *   Http client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EmergencyAnnouncementClient $announcement_client) {
    parent::__construct($config_factory);
    $this->announcement_client = $announcement_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('helfi_emergency_general.announcement_client'),
    );
  }

  protected function getEditableConfigNames() {
    return 'emergency_announcement_form.settings';
  }


  public function getFormId() {
    return 'emergency_announcement_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => 'Message',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Send message',
    ];

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    // @todo take the language in consideration.

    $this->announcement_client->sendMessage($form_state->getValue('message'));
    parent::submitForm($form, $form_state);
  }



}

