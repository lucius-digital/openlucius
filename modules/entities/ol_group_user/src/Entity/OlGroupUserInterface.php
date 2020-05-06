<?php

namespace Drupal\ol_group_user\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Group User entities.
 *
 * @ingroup ol_group_user
 */
interface OlGroupUserInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Group User name.
   *
   * @return string
   *   Name of the OL Group User.
   */
  public function getName();

  /**
   * Sets the OL Group User name.
   *
   * @param string $name
   *   The OL Group User name.
   *
   * @return \Drupal\ol_group_user\Entity\OlGroupUserInterface
   *   The called OL Group User entity.
   */
  public function setName($name);

  /**
   * Gets the OL Group User creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Group User.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Group User creation timestamp.
   *
   * @param int $timestamp
   *   The OL Group User creation timestamp.
   *
   * @return \Drupal\ol_group_user\Entity\OlGroupUserInterface
   *   The called OL Group User entity.
   */
  public function setCreatedTime($timestamp);

}
