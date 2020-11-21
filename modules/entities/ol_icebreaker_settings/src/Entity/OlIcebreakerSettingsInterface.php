<?php

namespace Drupal\ol_icebreaker_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol icebreaker settings entities.
 *
 * @ingroup ol_icebreaker_settings
 */
interface OlIcebreakerSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol icebreaker settings name.
   *
   * @return string
   *   Name of the Ol icebreaker settings.
   */
  public function getName();

  /**
   * Sets the Ol icebreaker settings name.
   *
   * @param string $name
   *   The Ol icebreaker settings name.
   *
   * @return \Drupal\ol_icebreaker_settings\Entity\OlIcebreakerSettingsInterface
   *   The called Ol icebreaker settings entity.
   */
  public function setName($name);

  /**
   * Gets the Ol icebreaker settings creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol icebreaker settings.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol icebreaker settings creation timestamp.
   *
   * @param int $timestamp
   *   The Ol icebreaker settings creation timestamp.
   *
   * @return \Drupal\ol_icebreaker_settings\Entity\OlIcebreakerSettingsInterface
   *   The called Ol icebreaker settings entity.
   */
  public function setCreatedTime($timestamp);

}
