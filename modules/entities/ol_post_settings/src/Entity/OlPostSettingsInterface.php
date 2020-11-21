<?php

namespace Drupal\ol_post_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol post settings entities.
 *
 * @ingroup ol_post_settings
 */
interface OlPostSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol post settings name.
   *
   * @return string
   *   Name of the Ol post settings.
   */
  public function getName();

  /**
   * Sets the Ol post settings name.
   *
   * @param string $name
   *   The Ol post settings name.
   *
   * @return \Drupal\ol_post_settings\Entity\OlPostSettingsInterface
   *   The called Ol post settings entity.
   */
  public function setName($name);

  /**
   * Gets the Ol post settings creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol post settings.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol post settings creation timestamp.
   *
   * @param int $timestamp
   *   The Ol post settings creation timestamp.
   *
   * @return \Drupal\ol_post_settings\Entity\OlPostSettingsInterface
   *   The called Ol post settings entity.
   */
  public function setCreatedTime($timestamp);

}
