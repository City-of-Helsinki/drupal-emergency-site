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

    $result = new FilterProcessResult(_filter_iframe_process($text));
    $result->setAttachments([
      'library' => ['helfi_emergency_general/helfi_emergency_third_party_cookie'],
    ]);

    return $result;
  }

}
