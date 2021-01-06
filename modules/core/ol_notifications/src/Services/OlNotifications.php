<?php

namespace Drupal\ol_notifications\Services;

use Drupal\ol_comment\Entity\OlComment;

/**
 * Class OlNotifications.
 */
class OlNotifications{

  /**
   * @var $mail
   */
  protected $mail;

  /**
   * @param $mail
   */
  public function __construct($mail) {
    $this->mail =  $mail;
  }

  /**
   * @param $notifications
   */
  public function sendNotifications($notifications) {

  }

}
