<?php

namespace Drupal\ol_folder\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Folder entities.
 */
class OlFolderViewsData extends EntityViewsData {

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
