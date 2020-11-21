<?php

namespace Drupal\ol_posts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_posts\Services\OlCultureQuestions;
use Drupal\ol_posts\Services\OlPosts;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class PostForm.
 */
class PostForm extends FormBase {

  /**
   * @var $posts
   */
  protected $posts;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $members
   */
  protected $members;

  /**
   * Class constructor.
   */
  public function __construct(OlPosts $posts, OlFiles $files, OlMembers $members) {
    $this->posts = $posts;
    $this->files = $files;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olposts.posts'),
      $container->get('olmain.files'),
      $container->get('olmembers.members')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'post_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = null, $id = null, $post_settings = null) {

    // Defaults.
    $body = '';
    $button_text = t('Submit');
    $hdd_file_location = $this->files->buildFileLocaton('post');
    //$mail_send_default = array('0');
    $num_users = $this->members->countMembers(null, true);
    //$send_mail_title = array( '1' => t('Notify members') .' ('.$num_users .')',);
    $question = t('What\'s happening?');

    if(!empty($post_settings->question)){
      $question = $post_settings->question;
    }

    // Handle edit vars.
    if ($op == 'edit'){
      $post_data = $this->getPostData($id);
      $body = $post_data->body;
      $button_text = t('Update Post');
      $mail_send_default = array('0');
    }

    // Build form.
    $form['post_id'] = [
     '#type' => 'hidden',
     '#default_value' => $id,
     '#weight' => '0',
    ];
    $form['body'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group post-body">',
      '#type' => 'textarea',
//      '#format' => 'ol_rich_text',
      '#weight' => '20',
      '#attributes' => array('placeholder' => $question, 'class' => array('form-control')),
      '#default_value' => $body,
      '#required' => true,
      '#suffix' => '</div>'
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="row"><div class="col-12 col-md-12"><div class="form-group file-upload-wrapper">',
      '#allowed_tags' => ['div'],
      '#weight' => '25',
    ];

    $form['files'] = array(
      '#title' => t('Attach images or files'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedImageExtentions(),
      ),
      '#weight' => '30',
    );
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
      '#allowed_tags' => ['div'],
      '#weight' => '35',
    ];
/*    $form['send_mail'] = array(
      '#prefix' => '<div class="col-12 col-md-6"><div class="form-group send_mail_checkbox">',
      '#title' => t('Email notifications'),
      '#type' => 'checkboxes',
      '#options' => $send_mail_title,
      '#default_value' => $mail_send_default,
      '#weight' => '40',
      '#suffix' => '</div></div></div>'
    );*/
    $form['submit'] = [
      '#prefix' => '</div></div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '100',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $button_text,
      '#suffix' => '</div>'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (strlen($form_state->getValue('name')) > 128) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Post not saved yet: title can\'t be more than 128 characters.'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get data.
    $id = Html::escape($form_state->getValue('post_id'));
    $body = Html::escape($form_state->getValue('body'));
//    $body = $form_state->getValue('body')['value'];
//    $body = check_markup($body,'ol_rich_text');
    $name = $name_shortened = shortenString($body);
    $send_mail = $form_state->getValue('send_mail')[1];
    $files = $form_state->getValue('files');
    // Existing, update post.
    if(is_numeric($id)){
      $this->posts->updatePost($id, $name, $body, $send_mail);
    }
    // New, save post.
    elseif(empty($id)){
      $id = $this->posts->savePost($name, $body, $send_mail);
    }
    if(!empty($files)) {
      $this->files->saveFiles($files, 'post', $id);
    }
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getPostData($id){
    $query = \Drupal::database()->select('ol_post', 'mess');
    $query->addField('mess', 'body');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $id);
    return $query->execute()->fetchObject();
  }

}


