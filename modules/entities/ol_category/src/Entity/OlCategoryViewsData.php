<?php

namespace Drupal\ol_category\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Category entities.
 */
class OlCategoryViewsData extends EntityViewsData {

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
