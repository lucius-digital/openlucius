<?php

namespace Drupal\ol_text_doc;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OL Text Doc entities.
 *
 * @ingroup ol_text_doc
 */
class OlTextDocListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OL Text Doc ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_text_doc\Entity\OlTextDoc $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_text_doc.edit_form',
      ['ol_text_doc' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
