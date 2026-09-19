<?php

namespace Drupal\ol_shoutouts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_shoutouts\Services\Shoutouts;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class AddShoutoutForm.
 */
class AddShoutoutForm extends FormBase {

  /**
   * @var $shoutouts
   */
  protected $shoutouts;
  /**
   * @var $members
   */
  protected $members;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_shoutouts\Services\Shoutouts $shoutouts
   * @param \Drupal\ol_members\Services\OlMembers $members
   */
  public function __construct(Shoutouts $shoutouts, OlMembers $members) {
    $this->shoutouts = $shoutouts;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olshoutouts.shoutouts'),
      $container->get('olmembers.members')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shoutout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = null, $id = null, $inline = FALSE) {

    // Init vars.
    $body = '';
    $default_value_uid = 0;

    // Handle edit vars.
    if ($op == 'edit'){
      $shoutout_data = $this->getShoutoutBody($id);
      $body = $shoutout_data->body;
      $default_value_uid = json_decode($shoutout_data->receivers);
    }
    $form['id_shoutout'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#weight' => '0',
    ];
    $form['uid'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'select',
      '#weight' => '10',
      '#required' => true,
      '#default_value' => $default_value_uid,
      '#options' => $this->getUsersList(),
      '#attributes' => array('class' => array('form-control')),
      '#suffix' => '</div>'
    ];
    $form['body'] = [
      '#prefix' => '<div class="form-group post-body">',
      '#type' => 'text_format',
      '#format' => 'plain_text',
      '#weight' => '20',
      '#attributes' => array('placeholder' => (t('Write your shout-out')), 'class' => array('form-control')),
      '#default_value' => $body,
      '#required' => true,
      '#suffix' => '</div>'
    ];
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '30',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $this->t('Save'),
      '#suffix' => '</div>'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do validation checks.
    if (!$form_state->getValue('uid')) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('uid', $this->t('Shout-out not posted yet: please choose a teammate.'));
    }
    if (strlen($form_state->getValue('body')['value']) < 1 ) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('body', $this->t('Shout-out not posted yet: please add a shout-out message.'));
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get form values.
    $id = Html::escape($form_state->getValue('id_shoutout'));
    $uid = $form_state->getValue('uid');
    $receivers_json = json_encode($uid);
    $body = $form_state->getValue('body')['value'];
    $body = check_markup($body,'plain_text');

    // Existing, update.
    if(is_numeric($id)){
       $this->shoutouts->updateShoutout($id, $body, $receivers_json);
    }
    // Save new shout-out.
    else {
      $this->shoutouts->saveShoutout($body, $receivers_json);
    }
  }

  /**
   * @return array
   */
  private function getUsersList(){
    $users_in_group = $this->members->getUsersInGroup(TRUE);
    // Build and return the option list.
    $receivers = array('0' => '- '.t('Choose a teammate') .' -');
    foreach ($users_in_group as $user){
      $receivers[$user->uid] = $user->name;
    }
    return $receivers;
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getShoutoutBody($id){
    $query = \Drupal::database()->select('ol_shout_out', 'oso');
    $query->addField('oso', 'body');
    $query->addField('oso', 'receivers');
    $query->condition('oso.id', $id);
    return $query->execute()->fetchObject();
  }

}


