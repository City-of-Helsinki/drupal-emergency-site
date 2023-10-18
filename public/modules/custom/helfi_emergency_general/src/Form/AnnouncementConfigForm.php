<?php

namespace Drupal\helfi_emergency_general\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\helfi_emergency_general\AnnouncementClient;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Messenger\Messenger;

/**
 * Form class for emergency announcement message.
 */
class AnnouncementConfigForm extends ConfigFormBase {

  /**
   * Emergency announcement client.
   *
   * @var \Drupal\helfi_emergency_general\AnnouncementClient
   */
  protected AnnouncementClient $announcementClient;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected LanguageManager $languageManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Announcement form constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\helfi_emergency_general\AnnouncementClient $announcementClient
   *   The announcement client.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AnnouncementClient $announcementClient, LanguageManager $languageManager, Messenger $messenger) {
    parent::__construct($config_factory);
    $this->announcementClient = $announcementClient;
    $this->languageManager = $languageManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('helfi_emergency_general.announcement_client'),
      $container->get('language_manager'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): string {
    return 'helfi_emergency_general.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'emergency_announcement_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // We set the current message in form state to use it in validate form.
    $message = $this->currentLangMessage();
    $form_state->set('current_message', $message);

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('You are currently editing the announcement for the @lang version',
        [
          '@lang' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getName(),
        ]),

    ];
    $form['fieldset']['current_message'] = [
      '#type' => 'textarea',
      '#title' => 'Current message',
      '#disabled' => TRUE,
      '#default_value' => $message['current_lang_message'] ?? "",
    ];

    $form['fieldset']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('New message'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $message = $this->announcementClient->sendMessage(
      $this->prepareMessage($form, $form_state)
    );

    if ($message instanceof GuzzleException) {
      $form_state->setError($form['fieldset'], 'Error while sending message.');
    }
    else {
      $this->messenger->addStatus('Successfully sent message in ' .
      $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getName() . ' language');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Method that extracts the message in the current language of the page.
   *
   * @return array
   *   Returns the current message.
   */
  public function currentLangMessage(): array {
    $current_message_response = $this->announcementClient->getCurrentMessage();
    $is_decoded = $current_message_response ? json_decode($current_message_response) : 'Error fetching current message.';
    if ($is_decoded) {
      foreach ($is_decoded as $lang_code => $value) {
        if ($lang_code === $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId()) {
          return [
            'current_lang_message' => $value,
            'current_message' => $is_decoded,
          ];
        }
      }
    }
    return [];
  }

  /**
   * Method that prepares the new message to be sent.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   Returns the json content to be sent.
   */
  public function prepareMessage(array &$form, FormStateInterface $form_state): mixed {
    $current_message = $form_state->get('current_message')['current_message'];
    $new_message = [];
    foreach ($current_message as $lang_code => $value) {
      $new_message[$lang_code] = $value;
      if ($lang_code === $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId()) {
        $new_message[$lang_code] = $form_state->getValue('message');
      }
    }

    return $new_message;
  }

}
