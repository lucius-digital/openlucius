<?php

namespace Drupal\ol_group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Group entities.
 *
 * @ingroup ol_group
 */
interface OlGroupInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Group name.
   *
   * @return string
   *   Name of the OL Group.
   */
  public function getName();

  /**
   * Sets the OL Group name.
   *
   * @param string $name
   *   The OL Group name.
   *
   * @return \Drupal\ol_group\Entity\OlGroupInterface
   *   The called OL Group entity.
   */
  public function setName($name);

  /**
   * Gets the OL Group creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Group.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Group creation timestamp.
   *
   * @param int $timestamp
   *   The OL Group creation timestamp.
   *
   * @return \Drupal\ol_group\Entity\OlGroupInterface
   *   The called OL Group entity.
   */
  public function setCreatedTime($timestamp);

}
