<?php

namespace Drupal\ol_general_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol general settings entities.
 *
 * @ingroup ol_general_settings
 */
class OlGeneralSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol general settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_general_settings\Entity\OlGeneralSettings $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_general_settings.edit_form',
      ['ol_general_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
