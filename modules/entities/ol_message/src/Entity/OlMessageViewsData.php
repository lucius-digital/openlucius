<?php

namespace Drupal\ol_message\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Message entities.
 */
class OlMessageViewsData extends EntityViewsData {

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
