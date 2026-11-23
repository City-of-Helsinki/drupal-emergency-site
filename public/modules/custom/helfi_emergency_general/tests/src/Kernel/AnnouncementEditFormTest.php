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
use GuzzleHttp\Exception\ClientException;
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

  private const string SAS_URL = 'https://helfibackupsdc.blob.core.windows.net/$web/emergency_announcement.json?sig=123';

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

    putenv('announcement_sas_url_base64_enc=' . base64_encode('sig=123'));
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
   * @param string $title
   *   The announcement title.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  private function submitForm(string $announcement, string $title = 'Title'): FormStateInterface {
    $formState = (new FormState())
      ->setValues(['title' => $title, 'announcement' => $announcement]);

    $this->container->get(FormBuilderInterface::class)
      ->submitForm(AnnouncementEditForm::class, $formState);

    return $formState;
  }

  /**
   * Tests that the form shows the existing announcement.
   */
  public function testBuildForm(): void {
    $this->setupMockHttpClient([
      new Response(200, [], Json::encode([
        'en' => ['title' => 'Existing title', 'body' => 'Existing announcement'],
        'fi' => ['title' => 'Otsikko', 'body' => 'Tiedote'],
      ])),
    ]);

    $form = $this->container->get(FormBuilderInterface::class)->getForm(AnnouncementEditForm::class);

    $this->assertEquals('Existing title', $form['title']['#default_value']);
    $this->assertEquals('Existing announcement', $form['announcement']['#default_value']);
    $this->assertStringContainsString('2030-01-01', (string) $form['expiry']['#markup']);
    $this->assertStringContainsString('English', (string) $form['announcement']['#description']);
  }

  /**
   * Tests that the form shows an error when fetching fails.
   */
  public function testBuildFormFetchError(): void {
    $this->setupMockHttpClient([
      new RequestException('Fetch failed', new Request('GET', self::SAS_URL)),
    ]);

    $form = $this->container->get(FormBuilderInterface::class)->getForm(AnnouncementEditForm::class);

    $this->assertArrayNotHasKey('announcement', $form);
    $errors = $this->container->get(MessengerInterface::class)->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertCount(1, $errors);
    $this->assertStringContainsString('Unable to fetch existing message', (string) $errors[0]);
  }

  /**
   * Tests that submitting the form updates the announcement.
   */
  public function testSubmitForm(): void {
    $history = [];
    $this->container->set('http_client', $this->createMockHistoryMiddlewareHttpClient($history, [
      new Response(200, ['ETag' => '"0x8D1"'], Json::encode([
        'en' => ['title' => 'Old title', 'body' => 'Old announcement'],
        'fi' => ['title' => 'Otsikko', 'body' => 'Tiedote'],
      ])),
      new Response(201, ['ETag' => '"0x8D2"']),
    ]));

    $formState = $this->submitForm('New announcement', 'New title');

    $this->assertEmpty($formState->getErrors());
    $this->assertCount(2, $history);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $history[1]['request'];
    $this->assertEquals('PUT', $request->getMethod());
    // The write must only succeed if the blob is unchanged since it was read.
    $this->assertEquals('"0x8D1"', $request->getHeaderLine('If-Match'));
    // Announcements for other languages must be preserved.
    $this->assertEquals(
      [
        'en' => ['title' => 'New title', 'body' => 'New announcement'],
        'fi' => ['title' => 'Otsikko', 'body' => 'Tiedote'],
      ],
      Json::decode((string) $request->getBody()),
    );
  }

  /**
   * Tests that the announcement can be removed by emptying the fields.
   */
  public function testSubmitFormEmpty(): void {
    $history = [];
    $this->container->set('http_client', $this->createMockHistoryMiddlewareHttpClient($history, [
      new Response(200, ['ETag' => '"0x8D1"'], Json::encode([
        'en' => ['title' => 'Old title', 'body' => 'Old announcement'],
      ])),
      new Response(201, ['ETag' => '"0x8D2"']),
    ]));

    $formState = $this->submitForm('', '');

    $this->assertEmpty($formState->getErrors());
    $this->assertCount(2, $history);
    $this->assertEquals(
      ['en' => ['title' => '', 'body' => '']],
      Json::decode((string) $history[1]['request']->getBody()),
    );
  }

  /**
   * Tests that a save fails if the blob was changed after it was read.
   */
  public function testSubmitFormConcurrentModification(): void {
    $this->setupMockHttpClient([
      new Response(200, ['ETag' => '"0x8D1"'], Json::encode([
        'en' => ['title' => 'Old title', 'body' => 'Old announcement'],
      ])),
      new ClientException(
        'Precondition failed',
        new Request('PUT', self::SAS_URL),
        new Response(412),
      ),
    ]);

    $formState = $this->submitForm('New announcement');

    $errors = $formState->getErrors();
    $this->assertArrayHasKey('announcement', $errors);
    $this->assertStringContainsString('Someone else saved an announcement at the same time', (string) $errors['announcement']);
    $this->assertEmpty($this->container->get(MessengerInterface::class)->messagesByType(MessengerInterface::TYPE_STATUS));
  }

  /**
   * Tests that a failed save is reported as a form error.
   */
  public function testSubmitFormSaveError(): void {
    $this->setupMockHttpClient([
      new Response(200, [], Json::encode(['en' => ['title' => 'Old title', 'body' => 'Old announcement']])),
      new RequestException('Save failed', new Request('PUT', self::SAS_URL)),
    ]);

    $formState = $this->submitForm('New announcement');

    $errors = $formState->getErrors();
    $this->assertArrayHasKey('announcement', $errors);
    $this->assertStringContainsString('Error while saving announcement', (string) $errors['announcement']);
    $this->assertEmpty($this->container->get(MessengerInterface::class)->messagesByType(MessengerInterface::TYPE_STATUS));
  }

}
