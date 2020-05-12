<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'MainHeader' block.
 *
 * @Block(
 *  id = "main_header_block",
 *  admin_label = @Translation("Main header block"),
 * )
 */
class MainHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Set global message.
    $global_message = \Drupal::config('ol_main.admin_settings')->get('global_message');
    // Return empty if global message is.
    if(empty($global_message)) {
      return array();
    }

    // Build.
    $theme_vars = [
      'global_message' => $global_message,
    ];
    $build = [
      '#theme' => 'main_header_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }



}
