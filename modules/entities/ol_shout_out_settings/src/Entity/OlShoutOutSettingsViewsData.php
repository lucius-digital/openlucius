<?php

namespace Drupal\ol_shout_out_settings\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ol shout out settings entities.
 */
class OlShoutOutSettingsViewsData extends EntityViewsData {

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
