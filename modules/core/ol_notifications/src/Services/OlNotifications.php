<?php

namespace Drupal\ol_notifications\Services;

use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Class OlNotifications.
 */
class OlNotifications{

  /**
   * @var $mail
   */
  protected $mail;

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @param $mail
   * @param $route
   * @param $members
   */
  public function __construct($mail, $route, $members) {
    $this->mail = $mail;
    $this->route = $route;
    $this->members = $members;
  }

  /**
   * @param $message
   * @param null $url
   */
  public function sendMentions($message, $url = null) {
    // No @, no mentions.
    if (strpos($message, '@') === false) {
      return;
    }
    // Get users in group.
    $group_users = $this->members->getUsersInGroup();
    // Mentions to whole group.
    if (strpos($message, '@Group') !== false) {
      // Loop through all users in group and send email.
      foreach($group_users as $group_user){
        $email = $group_user->mail;
        $this->sendNotifications($email, strip_tags($message), $url );
      }
      // Don't send duplicates to individual users that were maybe also mentioned.
      return;
    }
    // Loop though all users in group and send email if match.
    foreach($group_users as $group_user){
      if (strpos($message, '@'.$group_user->name) !== false) {
        $email = $group_user->mail;
        $this->sendNotifications($email, strip_tags($message), $url );
      }
    }
  }

  /**
   * @param $email
   * @param $message
   * @param null $url
   */
  private function sendNotifications($email, $message, $url = null){
    // Get current url if $url is null.
    $url = ($url) ? $url: \Drupal::service('path.current')->getPath();
    // Build mail vars.
    $emails = [$email];
    $mail = \Drupal::service('olmain.mail');
    $uid = \Drupal::currentUser()->id();
    $sender = User::load($uid)->getAccountName();
    $cta_text = t('Find Out More');
    $mail_body = t('@user mentioned you:', ['@user' => $sender]);
    $subject = t('@username mentioned you', ['@username' => $sender]);
    $message = shortenString(html_entity_decode($message),200);
    // Send mails via service.
    $mail->sendMail($subject, $url, $mail_body, $emails, null, null, $cta_text, null, $message);
  }

}
