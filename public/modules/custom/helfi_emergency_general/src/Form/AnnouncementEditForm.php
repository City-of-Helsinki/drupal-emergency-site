<?php

namespace Drupal\helfi_emergency_general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_emergency_general\AnnouncementClient;
use Drupal\helfi_emergency_general\AnnouncementException;

/**
 * Form for the emergency announcement message.
 */
final class AnnouncementEditForm extends FormBase {

  public function __construct(
    protected readonly AnnouncementClient $announcementClient,
    protected readonly LanguageManagerInterface $languageManager,
  ) {
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

    try {
      $announcement = $this->announcementClient->getAnnouncement($currentLanguage->getId());
    }
    catch (AnnouncementException) {
      $this->messenger()->addError($this->t('Unable to fetch existing message. Please check logs for more information'));

      return [];
    }

    $form['expiry'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The blob storage token will expire in @expiration', ['@expiration' => $this->announcementClient->getSasExpiration()]),
    ];

    $form['announcement'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Announcement'),
      '#default_value' => $announcement,
      '#description' => $this->t('You are currently editing the announcement for the @lang version of https://kopio.hel.fi/.',
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    try {
      $this->announcementClient->setAnnouncement(
        $form_state->getValue('announcement'),
        $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId()
      );
    }
    catch (AnnouncementException $e) {
      // The blob was changed or created by someone else after it was read:
      // 412 if If-Match failed, 409 if If-None-Match failed.
      $message = in_array($e->getCode(), [409, 412], TRUE)
        ? $this->t('Someone else saved an announcement at the same time, so your announcement was not saved. Please save it again.')
        : $this->t('Error while saving announcement. Please check logs for more information.');

      $form_state->setError($form['announcement'], $message);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()
      ->addStatus($this->t('Announcement saved for @lang language.', [
        '@lang' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getName(),
      ]))
      ->addWarning($this->t('The updates may take up to 2 minutes to become visible.'));
  }

}
