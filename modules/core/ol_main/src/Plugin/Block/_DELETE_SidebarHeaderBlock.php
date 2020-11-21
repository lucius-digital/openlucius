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
    // Handle site name.
    $site_name = $config->get('name');
    \Drupal::moduleHandler()->invokeAll('alter_site_name', [&$site_name]);
    // Handle site logo.
    $site_logo = '<i class="fas fa-circle-notch"></i>';
    \Drupal::moduleHandler()->invokeAll('alter_site_logo', [&$site_logo]);

    // Build.
    $theme_vars = [
      'site_name' => $site_name,
      'site_logo' => $site_logo,
    ];
    return [
      '#theme' => 'sidebar_header_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
  }

}
