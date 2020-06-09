<?php

namespace Drupal\ol_text_doc\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Text Doc entities.
 */
class OlTextDocViewsData extends EntityViewsData {

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
