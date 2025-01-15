<?php

namespace Drupal\helfi_emergency_general\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Removes iframe src so that is not loaded if cookies have not been accepted.
 *
 * @Filter(
 *   id = "filter_iframe_cookie",
 *   title = @Translation("Filter Iframe Cookie"),
 *   description = @Translation("Preventing iframe for being loaded if cookies have not been accepted."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterIframeCookie extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $html_dom = Html::load($text);
    $iframes = $html_dom->getElementsByTagName('iframe');
    foreach ($iframes as $iframe) {
      $srcAttribute = $iframe->attributes->getNamedItem('src');
      $iframe->removeAttribute('src');
      $iframe->setAttribute('class', 'social-media-iframe');
      $iframe->setAttribute('data-cookie-compliance-src', $srcAttribute->nodeValue);
    }

    $result = new FilterProcessResult(Html::serialize($html_dom));

    return $result;
  }

}
