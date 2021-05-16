<?php

namespace Drupal\ol_posts\Form;

use Drupal\Component\Utility\Xss;
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
   *
   * @param \Drupal\ol_posts\Services\OlPosts $posts
   * @param \Drupal\ol_main\Services\OlFiles $files
   * @param \Drupal\ol_members\Services\OlMembers $members
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
  public function buildForm(array $form, FormStateInterface $form_state, $op = null, $id = null, $post_settings = null, $gid = null) {

    // Defaults.
    $body = '';
    $button_text = t('Submit');
    $hdd_file_location = $this->files->buildFileLocaton('post');
    $mail_send_default = array('1');
    $num_users = $this->members->countMembers($gid, true);
    $send_mail_title = array( '1' => t('Notify all members') .' ('.$num_users .')',);
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
    // For homepage posts.
    if($gid) {
      $form['group_id'] = [
        '#type' => 'hidden',
        '#default_value' => $gid,
        '#weight' => '0',
      ];
    }
    $form['body'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'textarea',
      '#attributes' => [
//        'class' => ['summernote'],
        'class' => ['form-control'],
        'placeholder' => $question,
      ],
      '#weight' => '20',
      '#default_value' => $body,
      '#required' => true,
      '#suffix' => '</div><div class="row">'
    ];
    if($op != 'edit') {
      $form['send_mail'] = [
        '#prefix' => '<div class="col-12 col-md-6 pl-4 small text-muted pb-2">',
        '#type' => 'checkboxes',
        '#options' => $send_mail_title,
        '#default_value' => $mail_send_default,
        '#weight' => '25',
        '#attributes' => array(
          'data-toggle' => 'toggle',
          'data-onstyle' => 'success',
          'data-size' => 'xs',
        ),
        '#suffix' => '</div>'
      ];
    }
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-12 col-md-6">',
      '#allowed_tags' => ['div'],
      '#weight' => '30',
    ];

    $form['files'] = array(
//      '#title' => t('Attach images or files'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedImageExtentions(),
      ),
      '#attributes' => array(
        'class' => ['small text-muted pl-md-3'],
      ),
      '#progress_indicator' => 'bar',
      '#progress_message' => t('Please wait...'),
      '#weight' => '35',
    );
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
      '#allowed_tags' => ['div'],
      '#weight' => '40',
    ];

    $form['submit'] = [
      '#prefix' => '</div></div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '100',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $button_text,
      '#suffix' => '</div>'
    ];
    // For @-mentions.
    $group_users = $this->members->getUsersNamesInGroupFlatArray();
  //  $form['#attached']['library'][] = 'ol_main/summernote_inc_init';
    $form['#attached']['drupalSettings']['group_users'] = $group_users;
 //   $form['#attached']['drupalSettings']['placeholder_override'] = t('What\'s happening?');
    // Return form.
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
    $gid = Html::escape($form_state->getValue('group_id'));
    $body = Xss::filter($form_state->getValue('body'), getAllowedHTMLTags() );
    $body = sanatizeSummernoteInput($body);
    $name = $name_shortened = shortenString($body);
    $files = $form_state->getValue('files');
    $send_mail = $form_state->getValue('send_mail')[1];
    // Only global posts have a group id here.
    // This is needed for sending correct url mail.
    $global_post = ($gid) ? true : false;
    // Existing, update post.
    if(is_numeric($id)){
      $this->posts->updatePost($id, $name, $body, $send_mail, $global_post, $gid);
    }
    // New, save post.
    elseif(empty($id)){
      $id = $this->posts->savePost($name, $body, $send_mail, $gid, false, $global_post);
    }
    if(!empty($files)) {
      $this->files->saveFiles($files, 'post', $id, null, null, $gid);
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


