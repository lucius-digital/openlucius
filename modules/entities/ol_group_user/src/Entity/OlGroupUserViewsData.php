<?php

namespace Drupal\ol_group_user\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Group User entities.
 */
class OlGroupUserViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
