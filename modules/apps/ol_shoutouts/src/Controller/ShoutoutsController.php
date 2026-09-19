<?php

namespace Drupal\ol_shoutouts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_shoutouts\Services\Shoutouts;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ShoutoutsController.
 */
class ShoutoutsController extends ControllerBase {

  /**
   * @var $shoutouts
   */
  protected $shoutouts;

  /**
   * @var $pager
   */
  protected $pager;

  /**
   * @var $pager_params
   */
  protected $pager_params;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $sections
   */
  protected $sections;

  /**
   * Constructor
   *
   * @param \Drupal\ol_shoutouts\Services\Shoutouts $shoutouts
   * @param \Drupal\Core\Pager\PagerManager $pager
   * @param \Drupal\Core\Pager\PagerParameters $pager_params
   * @param \Drupal\ol_main\Services\OlComments $comments
   * @param \Drupal\ol_members\Services\OlMembers $members
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_main\Services\OlSections $sections
   */
  public function __construct(Shoutouts $shoutouts, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlGroups $groups, OlSections $sections) {
    $this->shoutouts = $shoutouts;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
    $this->groups = $groups;
    $this->sections = $sections;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olshoutouts.shoutouts'),
      $container->get('pager.manager'),
      $container->get('pager.parameters'),
      $container->get('olmain.comments'),
      $container->get('olmembers.members'),
      $container->get('olmain.groups'),
      $container->get('olmain.sections')
    );
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getShoutoutsList(){

    // Get initial data.
    $shoutout_config = $this->shoutouts->getShoutoutsGroupSettingsData();
    $is_group_admin = $this->members->isGroupAdmin();
    $page_title = $this->sections->getSectionOverrideTitle('shoutouts', 'Shout-outs');

    // No shout-outs configured yet, show landing page.
    if(empty($shoutout_config)){

      // Get config form.
      $shoutout_settings_form = \Drupal::formBuilder()->getForm(\Drupal\ol_shoutouts\Form\ShoutoutsConfigForm::class);

      // Build theme vars.
      $theme_vars = [
        'shoutout_settings_form' => $shoutout_settings_form,
        'is_group_admin' => $is_group_admin,
        'page_title' => $page_title,
      ];

      // Return build.
      return [
        '#theme' => 'shoutouts_landing',
        '#vars' => $theme_vars,
      ];

    }

    // Shout-outs configured, show list page.
    else {

      // Pager initialize.
      $page = $this->pager_params->findPage();
      $num_per_page = 10;
      $offset = $num_per_page * $page;

      // Get data.
      $shoutout_list_data = $this->shoutouts->getShoutoutListData(NULL, $num_per_page, $offset);
      $shoutouts = $this->shoutouts->renderShoutoutList($shoutout_list_data, 'list');
      $shoutout_form = \Drupal::formBuilder()->getForm(\Drupal\ol_shoutouts\Form\AddShoutoutForm::class, NULL, NULL, TRUE);
      $shoutout_settings_form = \Drupal::formBuilder()->getForm(\Drupal\ol_shoutouts\Form\ShoutoutsConfigForm::class, $shoutout_config);

      // Pager, now that we have the total number of results.
      $total_result = $this->shoutouts->getShoutoutListData(NULL, NULL, NULL, TRUE);
      $this->pager->createPager($total_result, $num_per_page)->getCurrentPage();

      // Build it.
      $theme_vars = [
        //'group_id' => $group_id,
        'shoutout_form' => $shoutout_form,
        'shoutouts' => $shoutouts,
        'shoutout_settings_form' => $shoutout_settings_form,
        'is_group_admin' => $is_group_admin,
        'page_title' => $page_title,
      ];

      // Build render.
      $render = [];
      $render[] = [
        '#theme' => 'shoutouts_list',
        '#vars' => $theme_vars,
        '#type' => 'remote',
      ];

      // Finally, add the pager to the render array, and return.
      $render[] = ['#type' => 'pager'];
      return $render;

    }

  }

}
