<?php

namespace Drupal\lus_message\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "lus_add_message",
 *   label = @Translation("Message title"),
 *   description = @Translation("Adds the item's message title to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddMessage extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Message title'),
        'description' => $this->t('The message title'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_message_title'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $message_entity = $item->getOriginalObject()->getValue();

    if ($message_entity) {
      if ($message_entity->getEntityType()->id() == 'lus_message') {
        print $title = $message_entity->get('name');

        //$url = $url->toString();
        $fields = $item->getFields(FALSE);

        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'search_api_message_title');

        foreach ($fields as $field) {
          $field->addValue($title);
        }
      }
    }
  }

}
