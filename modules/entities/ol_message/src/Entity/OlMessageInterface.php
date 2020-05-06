<?php

namespace Drupal\ol_message\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Message entities.
 *
 * @ingroup ol_message
 */
interface OlMessageInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Message name.
   *
   * @return string
   *   Name of the OL Message.
   */
  public function getName();

  /**
   * Sets the OL Message name.
   *
   * @param string $name
   *   The OL Message name.
   *
   * @return \Drupal\ol_message\Entity\OlMessageInterface
   *   The called OL Message entity.
   */
  public function setName($name);

  /**
   * Gets the OL Message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Message.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Message creation timestamp.
   *
   * @param int $timestamp
   *   The OL Message creation timestamp.
   *
   * @return \Drupal\ol_message\Entity\OlMessageInterface
   *   The called OL Message entity.
   */
  public function setCreatedTime($timestamp);

}
