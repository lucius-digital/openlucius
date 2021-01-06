<?php

namespace Drupal\ol_text_docs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_text_docs\Services\OlCategories;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class PlaceTextDocInCategoryForm.
 */
class PlaceTextDocInCategoryForm extends FormBase {


  /**
   * @var $categories
   */
  protected $categories;

  /**
   * Class constructor.
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(OlCategories $categories) {
    $this->categories = $categories;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oltextdocs.categories')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'place_text_doc_in_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['id'] = [
      '#type' => 'hidden',
      '#attributes' => array('id' => array('edit-textdoc-id')),
    ];
    $form['category_id'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'select',
      '#weight' => '0',
      '#title' => t('Choose a category'),
      '#default_value' => '0',
      '#options' => $this->categories->getCategoriesInCurrentGroup(),
      '#attributes' => array('class' => array('form-control')),
      '#suffix' => '</div>'
    ];
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '30',
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get vars.
    $id = Html::escape($form_state->getValue('id'));
    $category_id = Html::escape($form_state->getValue('category_id'));
    // Place in category.
    $this->categories->placeTextDocInCategory($category_id, $id);

  }


}


