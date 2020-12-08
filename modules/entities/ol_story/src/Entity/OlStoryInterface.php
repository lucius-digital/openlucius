<?php

namespace Drupal\ol_story\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Story entities.
 *
 * @ingroup ol_story
 */
interface OlStoryInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Story name.
   *
   * @return string
   *   Name of the OL Story.
   */
  public function getName();

  /**
   * Sets the OL Story name.
   *
   * @param string $name
   *   The OL Story name.
   *
   * @return \Drupal\ol_story\Entity\OlStoryInterface
   *   The called OL Story entity.
   */
  public function setName($name);

  /**
   * Gets the OL Story creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Story.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Story creation timestamp.
   *
   * @param int $timestamp
   *   The OL Story creation timestamp.
   *
   * @return \Drupal\ol_story\Entity\OlStoryInterface
   *   The called OL Story entity.
   */
  public function setCreatedTime($timestamp);

}
