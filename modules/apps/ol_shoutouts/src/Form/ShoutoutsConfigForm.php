<?php

namespace Drupal\ol_shoutouts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_shoutouts\Services\Shoutouts;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class ShoutoutsConfigForm.
 */
class ShoutoutsConfigForm extends FormBase {

  /**
   * @var $shoutouts
   */
  protected $shoutouts;

  /**
   * Class constructor.
   */
  public function __construct(Shoutouts $shoutouts) {
    $this->shoutouts = $shoutouts;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olshoutouts.shoutouts')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shoutouts_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $shoutout_config = null) {

    // Default settings for init.
    $nudge_email_enabled = 1;
    $id = null;

    // Settings for when editing.
    if (!empty($shoutout_config->id)){
      $id = $shoutout_config->id;
      $nudge_email_enabled = $shoutout_config->nudge_email;
    }

    $form['id_shoutout'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#weight' => '0',
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="modal-body"><div class="form-group">'.
        t('If enabled, subtle notifications will be send to encourage people to post shout-outs').': </div>',
      '#weight' => '10',
      '#allowed_tags' => ['div',],
    ];
    $form['nudge_email'] = array(
      '#prefix' => '<div class="form-group">',
      '#type' => 'checkboxes',
      '#options' => array( '1' => t('Enable subtle nudge messages')),
      '#default_value' => array($nudge_email_enabled),
      '#weight' => '20',
      '#attributes' => array(
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ),
      '#suffix' => '</div>'
    );
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '1000',
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
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $id = Html::escape($form_state->getValue('id_shoutout'));
    $nudge_email = $form_state->getValue('nudge_email')[1];
    // Existing settings, update.
    if(is_numeric($id)){
      $this->shoutouts->updateShoutouts($id, $nudge_email);
    }
    // Save new shoutout settings.
    else {
      $this->shoutouts->saveShoutoutSettings($nudge_email);
    }
  }

  /**
   * Helper to make json from filled out questions.
   *
   * @param $form_state
   * @return string
   */
  private function getFilledQuestionsJsonEncoded($form_state){
    // Use this to build loop count for now.
    // TODO
/*    $questions = $this->getDefaultShoutoutQuestions();
    // Build array with section override names.
    $questions_filled = array();
    foreach ($questions as $key => $question){
      // Get override name.
      $question = $form_state->getValue('question_' .$key);
      // Build array if override name is provided.
     // if(!empty($question)) {
        $questions_filled[$key] = $question;
      //}
    }
    // Encode to json format.
    return json_encode($questions_filled);*/
    return '';
  }
}


