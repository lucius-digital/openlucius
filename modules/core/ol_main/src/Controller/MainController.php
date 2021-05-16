<?php

namespace Drupal\ol_main\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class membersController.
 */
class MainController extends ControllerBase {


  /**
   * @var $groups
   */
  protected $groups;


  /**
   * {@inheritdoc}
   */
  public function __construct(OlGroups $groups) {
    $this->groups = $groups;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.groups')
    );
  }

  /**
   * @return array
   */
  public function getHome(){
    // Get user role.
    $user = \Drupal::currentUser();
    if($user->hasPermission('access all openlucius content')) {
      return $this->getHomeStandard();
    } else {
      return $this->getHomeCollaborator();
    }
  }


  /**
   * @return array
   */
  private function getHomeCollaborator(){

    // Groups block right.
    $groups_data = $this->groups->getGroups(1);
    $groups = $this->groups->addActivityBadge($groups_data);

    // Only stream on homepage for role collaborators for now.
    $tabs[] = [
      'label' => 'Group activity',
      'weight' => 20,
      'query_link' => 'activity',
      'new_badge' => null,
      'icon' => 'lni lni-users',
    ];
    // Set active tab static for role collaborators.
    $active_tab = 'activity';

    // Invoke hook to let module with active tab provide tab content.
    $tab_content = \Drupal::moduleHandler()->invokeAll('provide_home_tab_content', [$active_tab]);

    // Build it.
    $theme_vars = [
      'groups' => $groups,
      'tabs' => $tabs,
      'tab_content' => $tab_content[0],
      'active_tab' => $active_tab,
    ];
    return [
      '#theme' => 'home_wrapper',
      '#vars' => $theme_vars,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' => [
          'ol_main/ol_home',
        ],
      ],
    ];
  }

  /**
   * @return array
   */
  private function getHomeStandard(){

    // Groups block right.
    $groups_data = $this->groups->getGroups(1);
    $groups = $this->groups->addActivityBadge($groups_data);

    // Invoke hook to get data from modules that provide a tab.
    \Drupal::moduleHandler()->invokeAll('add_home_tab', [&$tabs]);
    // Get manual tabs order.
    $tabs_order = $this->groups->getHomeTabsPositions();
    // Order tabs when manually ordered before.
    if(is_array($tabs_order)) {
      $tabs = array_merge(array_flip($tabs_order), $tabs);
    }
    // Content in tabs
    $active_tab = \Drupal::request()->query->get('tab');
    $active_tab = (empty($active_tab)) ? $tabs[array_key_first($tabs)]['query_link'] : $active_tab;

    // Invoke hook to let module with active tab provide tab content.
    $tab_content = \Drupal::moduleHandler()->invokeAll('provide_home_tab_content', [$active_tab]);

    // Build it.
    $theme_vars = [
      'groups' => $groups,
      'tabs' => $tabs,
      'tab_content' => $tab_content[0],
      'active_tab' => $active_tab,
    ];
    return [
      '#theme' => 'home_wrapper',
      '#vars' => $theme_vars,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' => [
          'ol_main/ol_home',
        ],
      ],
    ];
  }


  /**
   * @return array
   */
  public function getGroupSettings(){
    // Get form.
    $config_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\GroupConfigForm::class);
    // Build it.
    $theme_vars = [
      'config_form' => $config_form,
    ];
    return [
      '#theme' => 'group_config_page',
      '#vars' => $theme_vars,
    ];
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getArchivedGroups(){
    // Get data.
    $groups_data = $this->groups->getGroups(0);
    $groups = $this->groups->renderArchivedGroupsCards($groups_data);
    // Build it.
    $theme_vars = [
      'groups' => $groups,
    ];
    return [
      '#theme' => 'groups_archived_page',
      '#vars' => $theme_vars,
    ];
  }
}
