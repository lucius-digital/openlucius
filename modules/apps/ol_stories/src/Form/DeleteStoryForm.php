<?php

namespace Drupal\ol_stories\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_stories\Services\OlStories;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class DeleteStoryForm.
 */
class DeleteStoryForm extends FormBase {

  /**
   * @var $stories
   */
  protected $stories;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct( OlStories $stories) {
    $this->stories = $stories;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olstories.stories')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ol_delete_story_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['story_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => array('id' => array('remove-story-id')),
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<button type="button" class="btn btn-default" data-dismiss="modal">' .t('Cancel').'</button>',
      '#allowed_tags' => ['button'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => array('class' => array('btn btn-danger')),
      '#value' => t('Yes, Delete Permanently'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get vars.
    $story_id = $form_state->getValue('story_id');
    $this->stories->removeStoryAndFile($story_id);
  }

}
