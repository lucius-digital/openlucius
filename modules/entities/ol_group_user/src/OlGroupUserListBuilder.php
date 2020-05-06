<?php

namespace Drupal\ol_group_user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OL Group User entities.
 *
 * @ingroup ol_group_user
 */
class OlGroupUserListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OL Group User ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_group_user\Entity\OlGroupUser $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_group_user.edit_form',
      ['ol_group_user' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
