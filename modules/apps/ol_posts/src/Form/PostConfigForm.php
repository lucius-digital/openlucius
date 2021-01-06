<?php

namespace Drupal\ol_posts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_post_settings\Entity\OlPostSettings;
use Drupal\ol_posts\Services\OlPosts;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class PostConfigForm.
 */
class PostConfigForm extends FormBase {

  /**
   * @var $posts
   */
  protected $posts;

  /**
   * Class constructor.
   */
  public function __construct(OlPosts $posts) {
    $this->posts = $posts;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olposts.posts'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'post_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $post_settings = null) {

    $question = t('What are you working on?');
    $default_enabled = array('Mon');
    $enabled = 1;
    $id = null;

    if (!empty($post_settings->id)){
      $id = $post_settings->id;
      $enabled = $post_settings->status;
      $question = $post_settings->question;
      $default_enabled = json_decode($post_settings->send_days, true);
    }

    $form['id_post'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#weight' => '0',
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="modal-body">',
      '#weight' => '10',
      '#allowed_tags' => ['div',],
    ];
    $form['enabled'] = array(
      '#prefix' => '<div class="form-group">',
      '#type' => 'checkboxes',
      '#options' => array( '1' => t('Enable this')),
      '#default_value' => array($enabled),
      '#weight' => '20',
      '#attributes' => array(
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ),
      '#suffix' => '</div>'
    );

    $form['question'] = [
      '#prefix' => '<div class="form-group">',
      '#title' => t('What question do you want to ask?'),
      '#type' => 'textfield',
      '#weight' => '30',
      '#default_value' => $question,
      '#attributes' => [
        'placeholder' => t('Write your question...'),
        'class' => ['form-control']
      ],
      '#suffix' => '</div>'
    ];
    $form['send_days'] = array(
      '#prefix' => '<div class="form-group">',
      '#title' => t('Send emails to members with this question every:'),
      '#type' => 'checkboxes',
      '#options' => array(
        'Mon' => t('Monday'),
        'Tue' => t('Tuesday'),
        'Wed' => t('Wednesday'),
        'Thu' => t('Thursday'),
        'Fri' => t('Friday'),
        'Sat' => t('Saturday'),
        'Sun' => t('Sunday'),
      ),
      '#attributes' => array(
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ),
      '#default_value' => $default_enabled,
      '#weight' => '40',
      '#suffix' => '</div>'
    );

    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '1000',
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
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $id = Html::escape($form_state->getValue('id_post'));
    $enabled = $form_state->getValue('enabled')[1];
    $question = $form_state->getValue('question');
    $send_days = $form_state->getValue('send_days');
    $send_days_json = json_encode($send_days);
    // Existing settings, update.
    if(is_numeric($id)){
      $this->posts->updatePostSettings($id, $question, $send_days_json, $enabled);
    }
    // Save new post settings.
    else {
      $this->posts->savePostSettings($question, $send_days_json, $enabled);
    }
  }
}


