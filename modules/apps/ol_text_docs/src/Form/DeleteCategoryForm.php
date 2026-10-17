<?php

namespace Drupal\ol_text_docs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_text_docs\Services\OlCategories;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting text document categories.
 */
class DeleteCategoryForm extends FormBase {

  /**
   * Categories service.
   *
   * @var \Drupal\ol_text_docs\Services\OlCategories
   */
  protected $categories;

  /**
   * DeleteCategoryForm constructor.
   *
   * @param \Drupal\ol_text_docs\Services\OlCategories $categories
   *   The categories service.
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
    return 'ol_delete_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['category_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => ['id' => ['remove-category-id']],
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<button type="button" class="btn btn-default" data-dismiss="modal">' . t('Cancel') . '</button>',
      '#allowed_tags' => ['button'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => ['class' => ['btn btn-danger']],
      '#value' => t('Yes, Delete Permanently'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get vars.
    $category_id = $form_state->getValue('category_id');
    $this->categories->removeCategory($category_id);
  }

}
