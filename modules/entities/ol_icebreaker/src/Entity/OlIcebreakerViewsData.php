<?php

namespace Drupal\ol_icebreaker\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Icebreaker entities.
 */
class OlIcebreakerViewsData extends EntityViewsData {

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
