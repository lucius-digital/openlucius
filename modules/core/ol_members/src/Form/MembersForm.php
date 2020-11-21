<?php

namespace Drupal\ol_members\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_group_user\Entity\OlGroupUser;
use Drupal\Component\Utility\Html;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ol_members\Services\OlMembers;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Language\LanguageManager;
use Drupal\ol_stream\Services\OlStream;

/**
 * Class MembersForm.
 */
class MembersForm extends FormBase {

  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $current_route
   */
  protected $current_route;

  /**
   * @var $messenger
   */
  protected $messenger;

  /**
   * @var $language_manager
   */
  protected $language_manager;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * Class constructor.
   * @param AccountInterface $account
   */
  public function __construct(AccountInterface $account, OlMembers $members, CurrentRouteMatch $current_route, Messenger $messenger, LanguageManager $language_manager, OlStream $stream, OlGroups $groups) {
    $this->account = $account;
    $this->members = $members;
    $this->current_route = $current_route;
    $this->messenger = $messenger;
    $this->language_manager = $language_manager;
    $this->stream = $stream;
    $this->groups =  $groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('olmembers.members'),
      $container->get('current_route_match'),
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('olstream.stream'),
      $container->get('olmain.groups')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'members_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get current group id.
    $group_id = $this->current_route->getParameter('gid');
    // Handle new members permission.
    $disabled = true;
    $placeholder = t('(Disabled: only user managers can add new members)');
    if($this->account->hasPermission('administer ol users')) {
      $disabled = false;
      $placeholder = t('Email address of new user...');
    }

    // Build form.
    $form['group_id'] = [
      '#type' => 'hidden',
      '#default_value' => $group_id,
      '#weight' => '0',
    ];
    $form['uid'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'select',
      '#weight' => '0',
      '#title' => t('Choose an existing user from within your organization:'),
      '#default_value' => '0',
      '#options' => $this->getUsersNotInCurrentGroup(),
      '#attributes' => array('class' => array('form-control')),
      '#suffix' => '</div>'
    ];
    $form['new_member_email'] = [
      '#prefix' => '<div class="form-group">',
      '#type' => 'email',
      '#title' => t('Or, add a new user:'),
      '#weight' => '0',
      '#label' => 'test',
      '#disabled' => $disabled,
      '#attributes' => array('placeholder' => $placeholder, 'class' => array('form-control')),
      '#suffix' => '</div>'
    ];
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $this->t('Add member'),
      '#suffix' => '</div>'
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $uid = $form_state->getValue('uid');
    $group_id = $form_state->getValue('group_id');
    $email = $form_state->getValue('new_member_email');
    // Check if 'new' email already exists.
    $email_exists = $this->checkEmailExistence($email);
    // Get current language code.
    $language = $this->language_manager->getCurrentLanguage()->getId();

    // Create new user, if it doesn't exist already.
    if(!empty($email) && $email_exists == FALSE && $this->account->hasPermission('administer ol users')) {
      // Create new user.
      $account = $this->members->addNewUser($email, $language);
      $account_id = $account->id();
      // Get group type.
      $group_type = $this->groups->getGroupType();
      // If group type is 'company', add new user to all company-wide groups.
      if($group_type == 'company'){
        $this->members->addUserToCompanyWideGroups($account_id);
      // If type is not 'company', add only to this group.
      } else {
        $this->members->addUserToGroup($account_id, $group_id);
      }
      // Send login email to new user.
      _user_mail_notify('register_no_approval_required', $account);
      // Create new stream item.
      $stream_body = t('Added a member: @user', array('@user' => $email));
      // Create stream item.
      $this->stream->addStreamItem($group_id, 'user_added', $stream_body,'user', $account_id);
      // Add message.
      $this->messenger->addStatus(t('@mail added as a member. A login link was sent by e-mail.', array('@mail' => $email)));
    }
    // Add existing user to group.
    elseif (!empty($uid)) {
      $this->members->addUserToGroup($uid, $group_id);
      $username = $this->members->getUserName($uid);
      $stream_body = t('Added a member: @user', array('@user' => $username)); // Create new stream item.
      $this->stream->addStreamItem($group_id, 'user_added', $stream_body,'user', $uid); // Create stream item.
      $this->messenger->addStatus(t( 'Member successfully added to this group'));
    }
  }

  /**
   * @param $email
   * @return bool
   */
  private function checkEmailExistence($email){
    // Check if email already exists.
    $user = user_load_by_mail($email);
    if ($user == TRUE){
      $this->messenger->addWarning(t($email .' was not added, because it already exists.'));
    }
    return !empty($user);
  }

  /**
   * Helper function, for populating user drop down .
   */
  function getUsersNotInCurrentGroup() {
    // Get all members in current group.
    $current_members = $this->members->getUsersInGroup();
    $group_members = array();
    foreach ($current_members as $member){
      array_push($group_members, $member->uid);
    }
    $query = \Drupal::database()->select('users_field_data', 'ufd');
    $query->addField('ufd', 'name');
    $query->addField('ufd', 'uid');
    $query->condition('ufd.uid', $group_members ,'NOT IN');
    $query->condition('ufd.status', 1);
    $query->addTag('ol_user_list');
    $users = $query->execute()->fetchAll();

    // Build and return the option list.
    $user_list = array('0' => '- '.t('Select user') .' -');
    foreach ($users as $user){
      $user_list[$user->uid] = $user->name;
    }
    return $user_list;
  }


}
