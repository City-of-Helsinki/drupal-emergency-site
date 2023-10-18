<?php

namespace Drupal\helfi_emergency_general;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Announcement client for dealing with requests to azure storage.
 */
class AnnouncementClient {

  /**
   * A fully-configured Guzzle client to pass to the client.
   *
   * @var \GuzzleHttp\Client
   */
  protected ClientInterface $httpClient;

  /**
   * Config Factory var.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  /**
   * Announcement logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  /**
   * The AnnouncementClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   GuzzleHttp service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel service.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config,
    LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    $this->httpClient = $http_client;
    $this->config = $config;
    $this->loggerChannel = $loggerChannelFactory->get('helfi_emergency_general');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Method that sends a new message.
   *
   * @param array $message
   *   The message to be sent.
   *
   * @return \GuzzleHttp\Exception\GuzzleException|\Psr\Http\Message\ResponseInterface
   *   Returns response or exception.
   */
  public function sendMessage(array $message): GuzzleException|ResponseInterface {

    $url = base64_decode(getenv('ENDPOINT_URL'));

    try {
      $response = $this->httpClient->request('PUT', $url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
        'body' => json_encode($message),
      ]);
    }
    catch (GuzzleException $e) {
      $this->loggerChannel->error($e->getMessage());
      $response = $e;
    }

    return $response;
  }

  /**
   * Method that returns the current message.
   *
   * @return string|null
   *   Returns the current message or null if error.
   */
  public function getCurrentMessage() {

    $url = base64_decode(getenv('ENDPOINT_URL'));

    try {
      $message = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age=120, must-revalidate',
        ],
      ])->getBody()->getContents();
    }
    catch (GuzzleException $e) {
      $this->loggerChannel->error($e->getMessage());
      $message = NULL;
    }

    return $message;
  }

}
