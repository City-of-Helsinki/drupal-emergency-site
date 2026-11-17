<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_emergency_general\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\helfi_emergency_general\Form\AnnouncementEditForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the announcement edit form.
 */
#[Group('helfi_emergency_general')]
#[RunTestsInSeparateProcesses]
final class AnnouncementEditFormTest extends KernelTestBase {

  use ApiTestTrait;

  private const string SAS_URL = 'https://example.blob.core.windows.net/container/announcement.json?sig=123';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_emergency_general',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    putenv('announcement_sas_url_base64_enc=' . base64_encode(self::SAS_URL));
    putenv('announcement_sas_expiration=2030-01-01');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    putenv('announcement_sas_url_base64_enc');
    putenv('announcement_sas_expiration');

    parent::tearDown();
  }

  /**
   * Submits the form.
   *
   * @param string $announcement
   *   The announcement.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  private function submitForm(string $announcement): FormStateInterface {
    $formState = (new FormState())
      ->setValues(['announcement' => $announcement]);
    $this->container->get(FormBuilderInterface::class)->submitForm(AnnouncementEditForm::class, $formState);

    return $formState;
  }

  /**
   * Tests that the form shows the existing announcement.
   */
  public function testBuildForm(): void {
    $this->container->set('http_client', $this->createMockHttpClient([
      new Response(200, [], Json::encode(['en' => 'Existing announcement', 'fi' => 'Tiedote'])),
    ]));

    $form = $this->container->get(FormBuilderInterface::class)->getForm(AnnouncementEditForm::class);

    $this->assertEquals('Existing announcement', $form['announcement']['#default_value']);
    $this->assertStringContainsString('2030-01-01', (string) $form['expiry']['#markup']);
    $this->assertStringContainsString('English', (string) $form['announcement']['#description']);
  }

  /**
   * Tests that the form handles a missing announcement for current language.
   */
  public function testBuildFormMissingLanguage(): void {
    $this->container->set('http_client', $this->createMockHttpClient([
      new Response(200, [], Json::encode(['fi' => 'Tiedote'])),
    ]));

    $form = $this->container->get(FormBuilderInterface::class)->getForm(AnnouncementEditForm::class);

    $this->assertSame('', $form['announcement']['#default_value']);
  }

  /**
   * Tests that the form shows an error when fetching fails.
   */
  public function testBuildFormFetchError(): void {
    $this->container->set('http_client', $this->createMockHttpClient([
      new RequestException('Fetch failed', new Request('GET', self::SAS_URL)),
    ]));

    $form = $this->container->get(FormBuilderInterface::class)->getForm(AnnouncementEditForm::class);

    $this->assertArrayNotHasKey('announcement', $form);
    $errors = $this->container->get('messenger')->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertCount(1, $errors);
    $this->assertStringContainsString('Unable to fetch existing message', (string) $errors[0]);
  }

  /**
   * Tests that submitting the form updates the announcement.
   */
  public function testSubmitForm(): void {
    $history = [];
    $this->container->set('http_client', $this->createMockHistoryMiddlewareHttpClient($history, [
      new Response(200, [], Json::encode(['en' => 'Old announcement', 'fi' => 'Tiedote'])),
      new Response(201),
    ]));

    $formState = $this->submitForm('New announcement');

    $this->assertEmpty($formState->getErrors());
    $this->assertCount(2, $history);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $history[1]['request'];
    $this->assertEquals('PUT', $request->getMethod());
    $this->assertEquals(self::SAS_URL, (string) $request->getUri());
    $this->assertEquals('BlockBlob', $request->getHeaderLine('x-ms-blob-type'));
    // Announcements for other languages must be preserved.
    $this->assertEquals(
      ['en' => 'New announcement', 'fi' => 'Tiedote'],
      Json::decode((string) $request->getBody()),
    );

    $messenger = $this->container->get('messenger');
    $status = $messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertCount(1, $status);
    $this->assertStringContainsString('Announcement saved for English language.', (string) $status[0]);
    $this->assertCount(1, $messenger->messagesByType(MessengerInterface::TYPE_WARNING));
  }

  /**
   * Tests that a failed save is reported as a form error.
   */
  public function testSubmitFormSaveError(): void {
    $this->container->set('http_client', $this->createMockHttpClient([
      new Response(200, [], Json::encode(['en' => 'Old announcement'])),
      new RequestException('Save failed', new Request('PUT', self::SAS_URL)),
    ]));

    $formState = $this->submitForm('New announcement');

    $errors = $formState->getErrors();
    $this->assertArrayHasKey('announcement', $errors);
    $this->assertStringContainsString('Error while saving announcement', (string) $errors['announcement']);
    $this->assertEmpty($this->container->get('messenger')->messagesByType(MessengerInterface::TYPE_STATUS));
  }

}
