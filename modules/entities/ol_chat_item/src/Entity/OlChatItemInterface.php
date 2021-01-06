<?php

namespace Drupal\ol_chat_item\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Chat Item entities.
 *
 * @ingroup ol_chat_item
 */
interface OlChatItemInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Chat Item name.
   *
   * @return string
   *   Name of the OL Chat Item.
   */
  public function getName();

  /**
   * Sets the OL Chat Item name.
   *
   * @param string $name
   *   The OL Chat Item name.
   *
   * @return \Drupal\ol_chat_item\Entity\OlChatItemInterface
   *   The called OL Chat Item entity.
   */
  public function setName($name);

  /**
   * Gets the OL Chat Item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Chat Item.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Chat Item creation timestamp.
   *
   * @param int $timestamp
   *   The OL Chat Item creation timestamp.
   *
   * @return \Drupal\ol_chat_item\Entity\OlChatItemInterface
   *   The called OL Chat Item entity.
   */
  public function setCreatedTime($timestamp);

}
