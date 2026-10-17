<?php

namespace Drupal\ol_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for user stream pages.
 */
class UserStreamController extends ControllerBase {

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

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
   * UserStreamController constructor.
   *
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\Core\Pager\PagerManager $pager
   *   Pager manager service.
   * @param \Drupal\Core\Pager\PagerParameters $pager_params
   *   Pager parameters service.
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
   * Get the user stream page.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Render array for the user stream page.
   */
  public function getUserStream($uid) {

    // @todo Build shared groups block.
    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 15;
    $offset = $num_per_page * $page;

    // Get and render data.
    $username = $this->members->getUserName($uid);
    $stream_data = $this->stream->getUserStreamList($uid, $num_per_page, $offset, NULL);
    $group_ids = $this->stream->getUserGroups(NULL, TRUE);
    $stream_html = $this->stream->renderStreamListMulti($stream_data, $group_ids);

    // Pager, now that we have the total number of results.
    $total_result = $this->stream->getUserStreamList($uid, NULL, NULL, TRUE);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Build it.
    $theme_vars = [
      'stream_html' => $stream_html,
      'user_stream' => TRUE,
      'username' => $username,
      'groups_block_heading' => t('Shared Groups'),
    ];

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
