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

    // Build build.
    $theme_vars = [
      'image' => 'test',
    ];
    $build = [
      '#theme' => 'main_header_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }



}
