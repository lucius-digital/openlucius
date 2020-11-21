<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MainHeader' block.
 *
 * @Block(
 *  id = "main_header_block",
 *  admin_label = @Translation("Main header block"),
 * )
 */
class MainHeaderBlock extends BlockBase  implements ContainerFactoryPluginInterface{

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('olmain.groups'),
      $container->get('olmain.files')

    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlGroups $groups, OlFiles $files) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groups = $groups;
    $this->files = $files;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Set global message.
    $global_message = \Drupal::config('ol_main.admin_settings')->get('global_message');
    // Handle header image.
    $header_image_url = '';
    $header_fid = $this->groups->getHeaderImage();
    if(!empty($header_fid)) {
      $header_uri = $this->files->getFileUri($header_fid);
      $header_image_url = Url::fromUri(file_create_url($header_uri));
    }
    // Build.
    $theme_vars = [
      'global_message' => $global_message,
      'header_image_url' => $header_image_url,
    ];
    $build = [
      '#theme' => 'main_header_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }



}
