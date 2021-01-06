<?php

namespace Drupal\ol_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\Core\Url;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ol_main\Services\OlGroups;

/**
 * Class StreamController.
 */
class StreamController extends ControllerBase {

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * @var $pager
   */
  protected $pager;

  /**
   * @var $pager_params
   */
  protected $pager_params;

  /**
   * @var $files
   */
  protected $files;

  /**
   * {@inheritdoc}
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
   * @param null $gid
   *
   * @return array
   */
  public function getStream($gid = null){

    // TODO: Date from-to filters instead of pager?
    // Pager caps activity in days, if 'rest items' of that day don't fall in their queries range.
    // Test this in live situation.

    // TODO: cache.

    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 30;
    $offset = $num_per_page * $page;

    // If $gid is provided, the request came from 'Stream section' in a group.
    if(is_numeric($gid)){
      $group_ids = array($gid);
    } else {
      $group_ids = $this->stream->getUserGroups(null, true);
    }
    // Get -and render data.
    $stream_data = $this->stream->getUserStreamList(null, $num_per_page, $offset, null, $gid);
    $stream_html = $this->stream->renderStreamListMulti($stream_data, $group_ids);

    // Pager, now that we have the total number of results.
    $total_result = $this->stream->getUserStreamList(null, null, null, true, $gid);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Groups right.
    $groups_data = $this->groups->getGroups(1);
    $groups = $this->groups->addActivityBadge($groups_data);

    // Get group image.
    $header_fid = $this->groups->getHeaderImage();
    $header_image_url = null;
    if(!empty($header_fid)) {
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
