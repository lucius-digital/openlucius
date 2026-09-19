<?php

namespace Drupal\ol_task\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ol task entities.
 */
class OlTaskViewsData extends EntityViewsData {

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
