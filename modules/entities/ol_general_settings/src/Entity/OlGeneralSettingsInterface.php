<?php

namespace Drupal\ol_general_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol general settings entities.
 *
 * @ingroup ol_general_settings
 */
interface OlGeneralSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol general settings name.
   *
   * @return string
   *   Name of the Ol general settings.
   */
  public function getName();

  /**
   * Sets the Ol general settings name.
   *
   * @param string $name
   *   The Ol general settings name.
   *
   * @return \Drupal\ol_general_settings\Entity\OlGeneralSettingsInterface
   *   The called Ol general settings entity.
   */
  public function setName($name);

  /**
   * Gets the Ol general settings creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol general settings.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol general settings creation timestamp.
   *
   * @param int $timestamp
   *   The Ol general settings creation timestamp.
   *
   * @return \Drupal\ol_general_settings\Entity\OlGeneralSettingsInterface
   *   The called Ol general settings entity.
   */
  public function setCreatedTime($timestamp);

}
