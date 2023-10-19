<?php

namespace Drupal\helfi_emergency_general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\helfi_emergency_general\AnnouncementClient;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Messenger\Messenger;

/**
 * Form class for emergency announcement message.
 */
class AnnouncementEditForm extends FormBase {

  use StringTranslationTrait;

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
   * @param \Drupal\helfi_emergency_general\AnnouncementClient $announcementClient
   *   The announcement client.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(AnnouncementClient $announcementClient, LanguageManager $languageManager, Messenger $messenger) {
    $this->announcementClient = $announcementClient;
    $this->languageManager = $languageManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
    $currentLanguage = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $announcement = $this->announcementClient->getAnnouncement($currentLanguage->getId());
    if (is_null($announcement)) {
      $this->messenger->addError($this->t('Unable to fetch existing message. Please check logs for more information'));

      return [];
    }

    $form['expiry'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The blob storage token will expire in @expiration', ['@expiration' => getenv('announcement_sas_expiration')]),
    ];

    $form['announcement'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Announcement'),
      '#default_value' => $announcement,
      '#description' => $this->t('You are currently editing the announcement for the @lang version of hel.fi static copy.',
        [
          '@lang' => $currentLanguage->getName(),
        ]),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save announcement'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $message = $this->announcementClient->setAnnouncement(
      $form_state->getValue('announcement'),
      $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId()
    );

    if (!$message) {
      $form_state->setError($form['announcement'],
        $this->t('Error while saving announcement. Please check logs for more information.')
      );
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger->addStatus($this->t('Announcement saved for @lang language.', [
      '@lang' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getName(),
    ]));
  }

}
