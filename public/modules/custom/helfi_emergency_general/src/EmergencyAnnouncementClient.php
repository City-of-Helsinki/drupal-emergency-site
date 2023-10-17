<?php

namespace Drupal\helfi_emergency_general;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmergencyAnnouncementClient {

  /**
   * A fully-configured Guzzle client to pass to the client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * Config Factory var.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config,
  ) {
    $this->http_client = $http_client;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
    );
  }

  public function sendMessage($message) {
    $endpoint = $this->config->get('emergency_announcement_form.settings')->get('endpoint');

    // We need to do this because the "$web" gets escaped by getenv resulting in bad url.
    $urlParts = parse_url($endpoint);
    $path = implode('/$web/', explode('//', $urlParts['path'], 2));
    $url = $urlParts['scheme'] . '://' . $urlParts['host'] . $path . '?' . $urlParts['query'];

    try {
      $this->http_client->request('PUT', $url, [
        'headers' => [
          'Content-Type' => 'application/json',
          'x-ms-blob-type' => 'BlockBlob',
          'x-ms-blob-cache-control' => 'public, max-age-120, must-revalidate'
        ],
        'body' => json_encode($message),
      ]);
    }
     catch (GuzzleException $e) {
      // @todo log message.
      dump($e);
    }


  }
}
