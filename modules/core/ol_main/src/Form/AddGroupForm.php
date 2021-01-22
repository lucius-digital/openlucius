<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ol_board\Services\OlTasks;
use Drupal\ol_group\Entity\OlGroup;
use Drupal\ol_group_user\Entity\OlGroupUser;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class AddGroupForm.
 */
class AddGroupForm extends FormBase {

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

    $form['type'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => array('id' => array('group-type-id')),
    ];
    $form['name'] = [
      '#prefix' => '<div class="col-md-4 col-xl-5 offset-xl-4 offset-md-4 py-3 bd-content mb-5 bg-white rounded shadow-sm">
                     <div class="modal-body">
                      <div class="form-group">',
      '#type' => 'textfield',
      '#weight' => '0',
      '#required' => true,
      '#attributes' => array('placeholder' => t('Name...'), 'class' => array('form-control')),
      '#suffix' => '</div>'
    ];
    $form['submit'] = [
      '#prefix' => '<div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => t('Add New Group'),
      '#suffix' => '</div></div>'
    ];
    $form['#attached']['library'][] = 'ol_main/ol_add_group';
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
    if (strlen($name) > 50) {
      $form_state->setErrorByName('name', $this->t('Group not saved: name can not be more then 50 characters long.'));
    }
    if (strlen($name) < 2) {
      $form_state->setErrorByName('name', $this->t('Group not saved: name must be at least 2 character long.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form data.
    $name = Html::escape($form_state->getValue('name'));
    $type = Html::escape($form_state->getValue('type'));
    $enabled_sections = 'stream,board,chat,messages,posts,notebooks,files,members';
    // Save group.
    $this->groups->addGroup($name, $type, null, true, $enabled_sections);


    if ($type == 'company') {
      \Drupal::messenger()
        ->addStatus(t('All members were added to this company wide group.'));
    }
  }

}
