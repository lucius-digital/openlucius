<?php

namespace Drupal\ol_main\Services;


use Drupal\Core\Render\Markup;

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
   * Todo: throw params in one associated array.
   * @param $subject
   * @param $url
   * @param $body
   * @param null $emails
   * @param null $sender
   * @param null $gid
   * @param null $cta_text
   *
   * @param null $lower_body
   * @param null $bold_text
   * @param null $sender_mail
   * @param null $base_url
   *
   * @return bool|void
   */
  public function sendMail($subject, $url, $body = null, $emails = null, $sender = null, $gid = null, $cta_text = null, $lower_body = null, $bold_text = null, $sender_mail = null, $base_url = null) {

    // Handle optional sender
    $sender = (empty($sender)) ? $this->members->getUserName() : $sender;
    // Handle base_url prepended in cta link.
    $base_url = (empty($base_url)) ? \Drupal::request()->getSchemeAndHttpHost() : $base_url;
    // Handle site_mail.
    $sender_mail = (!$sender_mail) ? \Drupal::config('system.site')->get('mail'): $sender_mail;
    // Define data, build mail.
    $module = 'ol_main';
    $key = 'ol_main_mail';
    $params['subject'] =  shortenString($subject, 60);
    $params['body'] = ($body) ? $body : '';
    $params['cta_text'] = ($cta_text) ? $cta_text : t('Find out more');
    $params['cta_url'] = $url;
    $params['lower_body'] = $lower_body;
    $params['sender_name'] = $sender;
    $params['bold_text'] = $bold_text;
    $params['sender_mail'] = $sender_mail;
    $params['base_url'] = $base_url;
    $lang_code = $this->current_user->getPreferredLangcode();
    $send = TRUE;

    // Send emails to passed through in $emails argument.
    if(!empty($emails)){
      foreach ($emails as $email) {
        $params['name_recipient'] = '';
        $this->mailer->mail($module, $key, $email, $lang_code, $params, NULL, $send);
      }
      return true;
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
        $params['name_recipient'] = $member->name;
        $result = $this->mailer->mail($module, $key, $member->mail, $lang_code, $params, NULL, $send);
      }
      // Message if mailer returned an okidoki.
      if($result['result'] === true){
        \Drupal::messenger()->addStatus($member_count .t(' member(s) notified successfully.'));
        return true;
      } else{
        \Drupal::messenger()->addError(t('Unable to send emails, please contact administrator!'));
      }
    }
  }

}
