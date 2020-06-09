<?php

namespace Drupal\ol_main\Services;


/**
 * Class OlMail.
 */
class OlMail{

  /**
   * @var $route
   */
  protected $mailer;

  /**
   * @var $current_user
   */
  protected $current_user;

  /**
   * @var $members
   */
  protected $members;

  /**
   * Constructor.
   *
   * @param $mailer
   * @param $current_user
   * @param $members
   */
  public function __construct($mailer, $current_user, $members) {
    $this->mailer = $mailer;
    $this->current_user = $current_user;
    $this->members = $members;
  }


  /**
   * @param $subject
   * @param $url
   * @param $body
   * @param null $emails
   * @param null $sender
   * @param null $gid
   */
  public function sendMail($subject, $url, $body = null, $emails = null, $sender = null, $gid = null) {

    // Define data.
    $module = 'ol_main';
    $key = 'ol_main_mail';
    $sender = (empty($sender)) ? $this->members->getUserName() : $sender;
    $sender_info = $sender .t(' posted: ');
    $params['subject'] = $sender_info .shortenString($subject, 60);
    $params['body'] = (!empty($body)) ? $body : '';
    $params['url'] = t('Find out more: ') .$url;
    $lang_code = $this->current_user->getPreferredLangcode();
    $send = TRUE;

    // Send emails to passed through in $emails argument.
    if(!empty($emails)){
      foreach ($emails as $email) {
        $this->mailer->mail($module, $key, $email, $lang_code, $params, NULL, $send);
      }
    }
    // Get members in current group and send mail.
    elseif ($emails == null) {
      $members = $this->members->getUsersInGroup(true, $gid);
      $member_count = count($members);
      if($member_count < 1){
        \Drupal::messenger()->addWarning(t('No email notification sent.'));
        return;
      }
      $result = array();
      foreach ($members as $member) {
        $result = $this->mailer->mail($module, $key, $member->mail, $lang_code, $params, NULL, $send);
      }
      // Message if mailer returned an okidoki.
      if($result['result'] === true){
        \Drupal::messenger()->addStatus($member_count .t(' member(s) notified successfully.'));
      } else{
        \Drupal::messenger()->addError(t('Unable to send emails, please contact administrator!'));
      }
    }
  }

}
