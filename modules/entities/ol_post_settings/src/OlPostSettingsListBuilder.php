<?php

namespace Drupal\ol_post_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol post settings entities.
 *
 * @ingroup ol_post_settings
 */
class OlPostSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol post settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_post_settings\Entity\OlPostSettings $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_post_settings.edit_form',
      ['ol_post_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
