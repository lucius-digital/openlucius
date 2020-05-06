<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_main\Services\OlSections;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class CheckinConfigForm.
 */
class GroupConfigForm extends FormBase {

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $sections
   */
  protected $sections;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_main\Services\OlSections $sections
   */
  public function __construct(OlGroups $groups, OlSections $sections) {
    $this->groups = $groups;
    $this->sections = $sections;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.groups'),
      $container->get('olmain.sections')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get group name.
    $gid = $this->groups->getCurrentGroupId();
    $name = $this->groups->getGroupName($gid);
    $sections = $this->sections->getSectionsData();
    // Build usable array from $sections.
    $options = $this->buildOptionsFromSections($sections);
    // Get enabled sections, for default_value.
    $enabled_sections = $this->sections->getEnabledSections($gid);

    $form['name'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'textfield',
      '#title' => t('Group Name'),
      '#default_value' => $name ,
      '#required' => true,
      '#attributes' => array('placeholder' => t('Add a group name...'), 'class' => array('form-control')),
      '#weight' => '10',
      '#suffix' => '</div>'
    ];
    $form['sections'] = [
      '#prefix' => '<div class="form-group">',
      '#type' => 'checkboxes',
      '#title' => t('Enabled Sections'),
      '#default_value' => $enabled_sections,
      '#options' => $options,
      '#required' => true,
      '#weight' => '20',
      '#suffix' => '</div>'
    ];
    $form['homepage'] = [
      '#prefix' => '<div class="form-group">',
      '#type' => 'select',
      '#title' => t('Group Homepage'),
      '#default_value' => array($this->groups->getGroupHome($gid)),
      '#options' => $options,
      '#attributes' => array('class' => array('form-control')),
      '#weight' => '30',
      '#suffix' => '</div>'
    ];
    $on_top_title = array( '1' => t('Show on top in group list (in left sidebar)'));
    $on_top_default = array($this->groups->isOnTop());
    $form['on_top'] = array(
      '#prefix' => '<div class="form-group">',
      '#title' => t('Show on top'),
      '#type' => 'checkboxes',
      '#options' => $on_top_title,
      '#default_value' => $on_top_default,
      '#weight' => '40',
      '#suffix' => '</div>'
    );
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '100',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $this->t('Submit'),
      '#suffix' => '</div>'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Get value.
    $name = Html::escape($form_state->getValue('name'));
    // Set an error for the form element with a key of "title".
    if (strlen($name) < 2) {
      $form_state->setErrorByName('name', $this->t('Name not changed: it must be at least 2 characters long.'));
    }
    if (strlen($name) > 100) {
      $form_state->setErrorByName('name', $this->t('Group not added: name can not be more then 100 characters long.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form values.
    $name = Html::escape($form_state->getValue('name'));
    $sections = $form_state->getValue('sections');
    $homepage = $form_state->getValue('homepage');
    $on_top = $form_state->getValue('on_top')[1];
    // Save group settings.
    $this->groups->saveGroupSettings($name,$sections, $homepage, null,  $on_top);
  }

  /**
   * @param $sections
   * @return array
   */
  private function buildOptionsFromSections($sections){
    $options = array();
    foreach ($sections as $section){
      $label = (string) $section['label']; // Casting to string is needed here.
      $key = (string) $section['path'];
      $options[$key] = $label;
    }
    return $options;
  }

}


