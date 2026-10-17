<?php

namespace Drupal\ol_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for stream pages.
 */
class StreamController extends ControllerBase {

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * Stream service.
   *
   * @var \Drupal\ol_stream\Services\OlStream
   */
  protected $stream;

  /**
   * Pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManager
   */
  protected $pager;

  /**
   * Pager parameters service.
   *
   * @var \Drupal\Core\Pager\PagerParameters
   */
  protected $pager_params;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * StreamController constructor.
   *
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\Core\Pager\PagerManager $pager
   *   Pager manager service.
   * @param \Drupal\Core\Pager\PagerParameters $pager_params
   *   Pager parameters service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   */
  public function __construct(OlGroups $groups, OlStream $stream, PagerManager $pager, PagerParameters $pager_params, OlFiles $files) {
    $this->groups = $groups;
    $this->stream = $stream;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->files = $files;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.groups'),
      $container->get('olstream.stream'),
      $container->get('pager.manager'),
      $container->get('pager.parameters'),
      $container->get('olmain.files')
    );
  }

  /**
   * Get the stream page.
   *
   * @param int|null $gid
   *   Optional group ID.
   *
   * @return array
   *   Render array for the stream page.
   */
  public function getStream($gid = NULL) {

    // @todo Date from-to filters instead of pager?
    // Pager caps activity in days, if 'rest items' of that day don't fall
    // in their queries range. Test this in live situation.
    // @todo Cache.
    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 30;
    $offset = $num_per_page * $page;

    // If $gid is provided, the request came from 'Stream section' in a group.
    if (is_numeric($gid)) {
      $group_ids = [$gid];
    }
    else {
      $group_ids = $this->stream->getUserGroups(NULL, TRUE);
    }

    // Get and render data.
    $stream_data = $this->stream->getUserStreamList(NULL, $num_per_page, $offset, NULL, $gid);
    $stream_html = $this->stream->renderStreamListMulti($stream_data, $group_ids);

    // Pager, now that we have the total number of results.
    $total_result = $this->stream->getUserStreamList(NULL, NULL, NULL, TRUE, $gid);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Groups right.
    $groups_data = $this->groups->getGroups(1);
    $groups = $this->groups->addActivityBadge($groups_data);

    // Get group image.
    $header_fid = $this->groups->getHeaderImage();
    $header_image_url = NULL;
    if (!empty($header_fid)) {
      $header_uri = $this->files->getFileUri($header_fid);
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('post_image');
      $header_image_url = $style->buildUrl($header_uri);
    }

    // Build.
    $theme_vars = [
      'stream_html' => $stream_html,
      'groups' => $groups,
      'groups_block_heading' => t('Your Groups'),
      'group_id' => $gid,
      'header_image_url' => $header_image_url,
    ];

    // Build render array.
    $render = [];
    $render[] = [
      '#theme' => 'stream_uber_wrapper',
      '#vars' => $theme_vars,
    ];

    // Add pager and return.
    $render[] = ['#type' => 'pager'];
    return $render;
  }

}
