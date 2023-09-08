<?php

namespace Drupal\helfi_emergency_general\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "filter_iframe",
 *   title = @Translation("Iframe Filter"),
 *   description = @Translation("Filter for applying a Social Feed. Use tokens: '[facebook-feed]' or '[twitter-feed]'
 * in the ckeditor in order to place social feeds in the page."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class IframeFilter extends FilterBase {

  public function process($text, $langcode): FilterProcessResult {
    if (str_contains($text, '[twitter-feed]')) {
      $twitter_iframe_wrapper = '<div class="social-media-wrapper social-twitter"></div>';
    }
    if (str_contains($text, '[facebook-feed]')) {
      $facebook_iframe_wrapper = '<div class="social-media-wrapper social-facebook"></div>';
    }
    $result_text = $twitter_iframe_wrapper ?? $facebook_iframe_wrapper;

    if ($result_text) {
      $result = new FilterProcessResult($result_text);
      $result->setAttachments([
        'library' => ['helfi_emergency_general/helfi_emergency_third_party_cookie'],
      ]);
      return $result;
    }

    return new FilterProcessResult($text);
  }

}
