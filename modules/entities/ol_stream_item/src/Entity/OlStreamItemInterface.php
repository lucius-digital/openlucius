<?php

namespace Drupal\ol_stream_item\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Stream Item entities.
 *
 * @ingroup ol_stream_item
 */
interface OlStreamItemInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Stream Item name.
   *
   * @return string
   *   Name of the OL Stream Item.
   */
  public function getName();

  /**
   * Sets the OL Stream Item name.
   *
   * @param string $name
   *   The OL Stream Item name.
   *
   * @return \Drupal\ol_stream_item\Entity\OlStreamItemInterface
   *   The called OL Stream Item entity.
   */
  public function setName($name);

  /**
   * Gets the OL Stream Item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Stream Item.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Stream Item creation timestamp.
   *
   * @param int $timestamp
   *   The OL Stream Item creation timestamp.
   *
   * @return \Drupal\ol_stream_item\Entity\OlStreamItemInterface
   *   The called OL Stream Item entity.
   */
  public function setCreatedTime($timestamp);

}
