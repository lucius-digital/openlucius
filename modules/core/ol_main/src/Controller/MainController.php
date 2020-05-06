<?php

namespace Drupal\ol_main\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Render\Renderer;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class membersController.
 */
class MainController extends ControllerBase {

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * @var $renderer
   */
  protected $groups;

  /**
   * @var $renderer
   */
  protected $stream;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, FormBuilder $form_builder, Renderer $renderer, OlGroups $groups, OlStream $stream) {
    $this->members = $members;
    $this->form_builder = $form_builder;
    $this->renderer = $renderer;
    $this->groups = $groups;
    $this->stream = $stream;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('form_builder'),
      $container->get('renderer'),
      $container->get('olmain.groups'),
      $container->get('olstream.stream')
    );
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getHome(){
    $this->groups->redirectToTopGroup();
    return null;
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

}
