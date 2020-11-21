<?php

namespace Drupal\ol_main\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class membersController.
 */
class MainController extends ControllerBase {

  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $renderer
   */
  protected $groups;


  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilder $form_builder, OlGroups $groups) {
    $this->form_builder = $form_builder;
    $this->groups = $groups;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('olmain.groups')
    );
  }

  /**
   * @return array
   */
  public function getGroupSettings(){
    // Get form.
    $config_form = $this->form_builder->getForm(\Drupal\ol_main\Form\GroupConfigForm::class);
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
