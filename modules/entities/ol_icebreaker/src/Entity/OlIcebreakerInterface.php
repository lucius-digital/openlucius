<?php

namespace Drupal\ol_icebreaker\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Icebreaker entities.
 *
 * @ingroup ol_icebreaker
 */
interface OlIcebreakerInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Icebreaker name.
   *
   * @return string
   *   Name of the OL Icebreaker.
   */
  public function getName();

  /**
   * Sets the OL Icebreaker name.
   *
   * @param string $name
   *   The OL Icebreaker name.
   *
   * @return \Drupal\ol_icebreaker\Entity\OlIcebreakerInterface
   *   The called OL Icebreaker entity.
   */
  public function setName($name);

  /**
   * Gets the OL Icebreaker creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Icebreaker.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Icebreaker creation timestamp.
   *
   * @param int $timestamp
   *   The OL Icebreaker creation timestamp.
   *
   * @return \Drupal\ol_icebreaker\Entity\OlIcebreakerInterface
   *   The called OL Icebreaker entity.
   */
  public function setCreatedTime($timestamp);

}
