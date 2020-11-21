<?php

namespace Drupal\ol_icebreaker_settings\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ol icebreaker settings entities.
 */
class OlIcebreakerSettingsViewsData extends EntityViewsData {

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
