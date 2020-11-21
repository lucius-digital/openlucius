<?php

namespace Drupal\ol_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ol_main\Services\OlGroups;

/**
 * Class UserStreamController.
 */
class UserStreamController extends ControllerBase {

  /**
   * @var $members
   */
  protected $members;

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
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, OlStream $stream, PagerManager $pager, PagerParameters $pager_params) {
    $this->members = $members;
    $this->stream = $stream;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('olstream.stream'),
      $container->get('pager.manager'),
      $container->get('pager.parameters')
    );
  }

  /**
   * @param $uid
   *
   * @return array
   */
  public function getUserStream($uid){

    // Todo: build shared groups block.

    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 15;
    $offset = $num_per_page * $page;

    // Get -and render data.
    $username = $this->members->getUserName($uid);
    $stream_data = $this->stream->getUserStreamList($uid, $num_per_page, $offset, null);
    $group_ids = $this->stream->getUserGroups(null, true);
    $stream_html = $this->stream->renderStreamListMulti($stream_data, $group_ids);

    // Pager, now that we have the total number of results.
    $total_result = $this->stream->getUserStreamList($uid, null, null, true);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Build it.
    $theme_vars = [
      'stream_html' => $stream_html,
      'user_stream' => true,
      'username' => $username,
      'groups_block_heading' => t('Shared Groups'),
    ];
    $render = [];
    $render[] = [
      '#theme' => 'stream_uber_wrapper',
      '#attached' => [
        'library' => [
          'ol_stream/stream_user',
        ],
      ],
       '#vars' => $theme_vars,
    ];
    // Add pager and return.
    $render[] = ['#type' => 'pager'];
    return $render;
  }
}
