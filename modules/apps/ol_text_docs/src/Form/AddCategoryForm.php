<?php

namespace Drupal\ol_text_docs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_text_docs\Services\OlCategories;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class AddCategoryForm.
 */
class AddCategoryForm extends FormBase {

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
    return 'add_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['category_id'] = [
      '#type' => 'hidden',
      '#attributes' => array('id' => array('edit-category-id')),
    ];

    $form['name'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'textfield',
      '#weight' => '0',
      '#required' => true,
      '#attributes' => array('placeholder' => t('Add a category name...'), 'class' => array('form-control')),
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
    parent::validateForm($form, $form_state);
    $name = Html::escape($form_state->getValue('name'));
    if (strlen($name) < 2) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Category name must be at least 2 characters long.'));
    }
    if (strlen($name) > 35) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Category name can be no longer than 35 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $name = Html::escape($form_state->getValue('name'));
    $category_id = Html::escape($form_state->getValue('category_id'));
    // Update or add.
    if(is_numeric($category_id)){
      // Update Category.
      $this->categories->updateCategory($name, $category_id);
      \Drupal::messenger()->addStatus(t('Your category was successfully updated to ' .$name));
    } else {
      // Add New Category.
      $this->categories->saveCategory($name);
      \Drupal::messenger()->addStatus(t('Your category was added successfully.'));
    }

  }

}


