<?php

namespace Drupal\ol_chat_item;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OL Chat Item entities.
 *
 * @ingroup ol_chat_item
 */
class OlChatItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OL Chat Item ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_chat_item\Entity\OlChatItem $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_chat_item.edit_form',
      ['ol_chat_item' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
