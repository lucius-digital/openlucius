<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SidebarHeader' block.
 *
 * @Block(
 *  id = "sidebar_header_block",
 *  admin_label = @Translation("Sidebar header block"),
 * )
 */
class SidebarHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get site name.
    $config = \Drupal::config('system.site');
    $site_name = $config->get('name');

    // Build.
    $theme_vars = [
      'site_name' => $site_name,
    ];
    return [
      '#theme' => 'sidebar_header_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
  }

}
