<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_board\Services\OlTasks;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class GeneralSettingsForm.
 */
class GeneralSettingsForm extends FormBase {

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlGroups $groups
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_group_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all homepage tabs.
    \Drupal::moduleHandler()->invokeAll('add_home_tab', [&$tabs]);
    // Get manual tabs order.
    $tabs_order = $this->groups->getHomeTabsPositions();
    // Order tabs when manually ordered before.
    if(is_array($tabs_order)) {
      $tabs = array_merge(array_flip($tabs_order), $tabs);
    }

    // Build form.
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<ul id="sortable-tab-settings" class="list-unstyled">',
      '#allowed_tags' => ['div','ul'],
    ];
    // Loop though tabs
    foreach ($tabs as $key => $tab) {
      $form['tab_'.$key] = [
        '#type' => 'markup',
        '#markup' => '<li data-id="'.$key.'"><button class="btn btn-light mb-3" type="button"> <i class="lni lni-arrows-vertical"></i> '.$tab['label'] .'</button></li>',
        '#allowed_tags' => ['li','i','button'],
      ];
    }
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</ul>',
      '#allowed_tags' => ['ul'],
    ];
    $form['markup_message'] = [
      '#type' => 'markup',
      '#markup' => '<span id="sorted-message" class="text-success"></span>',
      '#allowed_tags' => ['span'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
