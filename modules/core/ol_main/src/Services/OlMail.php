<?php

namespace Drupal\ol_main\Services;

/**
 * Service for sending email notifications.
 */
class OlMail {

  /**
   * The mailer service.
   *
   * @var \Drupal\Core\Mail\MailInterface
   */
  protected $mailer;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The OL members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Constructs an OlMail instance.
   *
   * @param \Drupal\Core\Mail\MailInterface $mailer
   *   The mailer service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   */
  public function __construct($mailer, $current_user, $members) {
    $this->mailer = $mailer;
    $this->currentUser = $current_user;
    $this->members = $members;
  }

  /**
   * Send an email notification to specified recipients or group members.
   *
   * @todo Throw params in one associated array.
   *
   * @param string $subject
   *   The email subject.
   * @param string $url
   *   The URL for the CTA link.
   * @param string|null $body
   *   The email body content.
   * @param array|null $emails
   *   Array of email addresses to send to.
   * @param string|null $sender
   *   The sender name.
   * @param int|null $gid
   *   The group ID for sending to group members.
   * @param string|null $cta_text
   *   The call-to-action button text.
   * @param string|null $lower_body
   *   Additional body content at the bottom.
   * @param string|null $bold_text
   *   Bold text to include in the email.
   * @param string|null $sender_mail
   *   The sender email address.
   * @param string|null $base_url
   *   The base URL for the site.
   *
   * @return bool|void
   *   TRUE if emails were sent successfully.
   */
  public function sendMail($subject, $url, $body = NULL, $emails = NULL, $sender = NULL, $gid = NULL, $cta_text = NULL, $lower_body = NULL, $bold_text = NULL, $sender_mail = NULL, $base_url = NULL) {

    // Handle optional sender.
    $sender = (empty($sender)) ? $this->members->getUserName() : $sender;
    // Handle base_url prepended in cta link.
    $base_url = (empty($base_url)) ? \Drupal::request()->getSchemeAndHttpHost() : $base_url;
    // Handle site_mail.
    $sender_mail = (!$sender_mail) ? \Drupal::config('system.site')->get('mail') : $sender_mail;
    // Define data, build mail.
    $module = 'ol_main';
    $key = 'ol_main_mail';
    $params['subject'] = shortenString($subject, 60);
    $params['body'] = ($body) ? $body : '';
    $params['cta_text'] = ($cta_text) ? $cta_text : t('Find out more');
    $params['cta_url'] = $url;
    $params['lower_body'] = $lower_body;
    $params['sender_name'] = $sender;
    $params['bold_text'] = $bold_text;
    $params['sender_mail'] = $sender_mail;
    $params['base_url'] = $base_url;
    $lang_code = $this->currentUser->getPreferredLangcode();
    $send = TRUE;

    // Send emails to passed through in $emails argument.
    if (!empty($emails)) {
      foreach ($emails as $email) {
        $params['name_recipient'] = '';
        $this->mailer->mail($module, $key, $email, $lang_code, $params, NULL, $send);
      }
      return TRUE;
    }
    // Get members in current group and send mail.
    elseif ($emails == NULL) {
      $members = $this->members->getUsersInGroup(TRUE, $gid);
      $member_count = count($members);
      if ($member_count < 1) {
        \Drupal::messenger()->addWarning(t('No email notification sent.'));
        return;
      }
      $result = [];
      foreach ($members as $member) {
        $params['name_recipient'] = $member->name;
        $result = $this->mailer->mail($module, $key, $member->mail, $lang_code, $params, NULL, $send);
      }
      // Message if mailer returned an okidoki.
      if ($result['result'] === TRUE) {
        \Drupal::messenger()->addStatus($member_count . ' ' . t('member(s) notified successfully.'));
        return TRUE;
      }
      else {
        \Drupal::messenger()->addError(t('Unable to send emails, please contact administrator!'));
      }
    }
  }

}
