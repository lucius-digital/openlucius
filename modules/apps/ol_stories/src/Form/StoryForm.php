<?php

namespace Drupal\ol_stories\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stories\Services\OlStories;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class StoryForm.
 */
class StoryForm extends FormBase {

  /**
   * @var $stories
   */
  protected $stories;

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
   * @param \Drupal\ol_stories\Services\OlStories $stories
   * @param \Drupal\ol_main\Services\OlFiles $files
   * @param \Drupal\ol_members\Services\OlMembers $members
   */
  public function __construct(OlStories $stories, OlFiles $files, OlMembers $members) {
    $this->stories = $stories;
    $this->files = $files;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olstories.stories'),
      $container->get('olmain.files'),
      $container->get('olmembers.members')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'story_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = null, $id = null, $story_settings = null) {

    // Defaults.
    $body = '';
    $button_text = t('Submit');
    $hdd_file_location = $this->files->buildFileLocaton('story', 0);
    $question = t('What\'s happening?');

    // Handle edit vars.
    if ($op == 'edit'){
      $story_data = $this->getStoryData($id);
      $body = $story_data->body;
      $button_text = t('Update Story');
    }

    // Build form.
    $form['story_id'] = [
     '#type' => 'hidden',
     '#default_value' => $id,
     '#weight' => '0',
    ];
    $form['body'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group story-body">',
      '#type' => 'textarea',
      '#weight' => '20',
      '#attributes' => [
        'placeholder' => $question,
        'class' => array('form-control'),
        'rows' => 3
      ],
      '#default_value' => $body,
      '#required' => false,
      '#suffix' => '</div>'
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="row"><div class="col-12 col-md-12"><div class="form-group file-upload-wrapper">',
      '#allowed_tags' => ['div'],
      '#weight' => '25',
    ];

    $form['files'] = array(
      '#title' => t('Attach Image'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => FALSE,
      '#progress_indicator' => 'bar',
      '#progress_message' => t('Please wait...'),
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

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get data.
    $id = Html::escape($form_state->getValue('story_id'));
    $body = Html::escape($form_state->getValue('body'));
    $name = $name_shortened = shortenString($body);
    $files = $form_state->getValue('files');
    // New, save story.
    if(!empty(strip_tags($body)) || !empty($files)){
      $id = $this->stories->saveStory($name, $body);
    }
    elseif(empty($files) && empty(strip_tags($body))){
      \Drupal::messenger()->addError( t('No story posted: no text or image placed.'));
    }
    if(!empty($files)) {
      $this->files->saveFiles($files, 'story', $id);
    }
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getStoryData($id){
    $query = \Drupal::database()->select('ol_story', 'mess');
    $query->addField('mess', 'body');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $id);
    return $query->execute()->fetchObject();
  }

}


