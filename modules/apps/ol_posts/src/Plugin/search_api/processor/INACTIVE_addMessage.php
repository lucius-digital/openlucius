<?php

namespace Drupal\lus_post\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "lus_add_post",
 *   label = @Translation("Post title"),
 *   description = @Translation("Adds the item's post title to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddPost extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Post title'),
        'description' => $this->t('The post title'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_post_title'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $post_entity = $item->getOriginalObject()->getValue();

    if ($post_entity) {
      if ($post_entity->getEntityType()->id() == 'lus_post') {
        print $title = $post_entity->get('name');

        //$url = $url->toString();
        $fields = $item->getFields(FALSE);

        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'search_api_post_title');

        foreach ($fields as $field) {
          $field->addValue($title);
        }
      }
    }
  }

}
