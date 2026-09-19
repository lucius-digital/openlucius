<?php

namespace Drupal\ol_shoutouts\Services;

use Drupal\Core\Url;
use Drupal\ol_shout_out\Entity\OlShoutOut;
use Drupal\ol_shout_out_settings\Entity\OlShoutOutSettings;
use Drupal\user\Entity\User;

/**
 * Class Shoutouts.
 */
class Shoutouts{

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * @var $mail
   */
  protected $mail;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @param $route
   * @param $members
   * @param $stream
   * @param $mail
   * @param $groups
   * @param $comments
   * @param $files
   */
  public function __construct($route, $members, $stream, $mail, $groups, $comments, $files) {
    $this->route = $route;
    $this->members = $members;
    $this->stream = $stream;
    $this->mail = $mail;
    $this->groups = $groups;
    $this->comments = $comments;
    $this->files = $files;
  }

  /**
   * @param $body
   * @param $receivers_json
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveShoutout($body, $receivers_json){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Save new record.
    $ol_shout_outs = OlShoutout::create([
      'body' =>  $body,
      'receivers' =>  $receivers_json,
      'group_id' => $gid,
    ]);
    $ol_shout_outs->save();
    $id = $ol_shout_outs->id();
    $name = strip_tags(shortenString($body));
    // Save stream item.
    $stream_body = $name; // Create new stream item.
    $this->stream->addStreamItem($gid, 'shoutouts_added', $stream_body, 'shoutouts', $id);
    // Send email to shout-outed.
    $current_uid = \Drupal::currentUser()->id();
    $sender = User::load($current_uid)->getAccountName();
    $email =  User::load(json_decode($receivers_json))->getEmail();
    $subject = t('You got a shout-out! 📣');
    $url = Url::fromRoute('ol_shoutouts.list_page', ['gid' => $gid])->toString();
    $cta_text = t('Show the Shout-out');
    $mail_body = t('@sender showed appreciation:', array('@sender' => $sender));
    $bold_text = $name;
    $this->mail->sendMail($subject, $url, $mail_body, array($email), $sender, $gid, $cta_text, null, $bold_text);
    \Drupal::messenger()->addStatus(t('Your shoutout was posted successfully!'));
  }

  /**
   * Inactive at the moment.
   *
   * @param $id
   * @param $body
   */
  public function updateShoutout($id, $body, $receivers_json){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Update record.
    $current_uid = \Drupal::currentUser()->id();
    \Drupal::database()->update('ol_shout_out')
      ->fields([
                'body' => $body,
                'receivers' => $receivers_json,
              ])
      ->condition('group_id', $gid)
      ->condition('id', $id)
      ->condition('user_id', $current_uid)
      ->execute();
    \Drupal::messenger()->addStatus(t('Your shoutout was updated successfully.'));
  }

  /**
   * @param $nudge_email
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveShoutoutsettings($nudge_email){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Save new shoutout settings.
    $ol_shout_out = OlShoutoutsettings::create([
      'group_id' => $gid,
      'nudge_email' => $nudge_email,
    ]);
    $ol_shout_out->save();
    // Add message.
    \Drupal::messenger()->addStatus(t('Your shoutout settings are saved successfully.'));
  }

  /**
   * @param $id
   * @param $nudge_email
   */
  public function updateShoutouts($id, $nudge_email){
    $uid = \Drupal::currentUser()->id();
    $gid = $this->route->getParameter('gid');
    \Drupal::database()->update('ol_shout_out_settings')
      ->fields([
        'nudge_email' => $nudge_email
      ])
      ->condition('id', $id)
      ->condition('user_id', $uid)
      ->condition('group_id', $gid)
      ->execute();
    \Drupal::messenger()->addStatus(t('Your shoutout settings are saved successfully.'));
  }

  /**
   * @param null $shoutout_id
   * @param null $num_per_page
   * @param null $offset
   * @param bool $get_total
   *
   * @return mixed
   */
  public function getShoutoutListData($shoutout_id = null, $num_per_page = null, $offset = null, $get_total = false){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Get message data.
    $query = \Drupal::database()->select('ol_shout_out', 'oib');
    $query->addField('oib', 'id');
    $query->condition('oib.group_id', $gid);
    if(!empty($shoutout_id)) {
      $query->condition('oib.id', $shoutout_id);
    }
    $query->orderBy('oib.created', 'desc');

    if ($get_total == false) {
      $query->range($offset, $num_per_page);
    }
    // Data for message lists.
    if ($get_total == false) {
      return $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      return $query->countQuery()->execute()->fetchField();
    }
  }

  /**
   * @param $shoutout_list_data
   * @param string $view
   *
   * @return string
   * @throws \Exception
   */
  public function renderShoutoutList($shoutout_list_data, $view = 'list'){
    // Init.
    $shoutouts_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($shoutout_list_data as $shoutout){
      $shoutout_data = $this->getShoutoutData($shoutout->id);
      $shoutouts_row_data['body'] = $shoutout_data->body;
      $shoutouts_row_data['created'] = ($view == 'list') ? time_elapsed_string('@'.$shoutout_data->created): $shoutout_data->created;
      $shoutouts_row_data['username'] = $shoutout_data->username;
      $shoutouts_row_data['id'] = $shoutout_data->id;
      $shoutouts_row_data['group_id'] = $shoutout_data->group_id;
      $shoutouts_row_data['user_id'] = $shoutout_data->user_id;
      $shoutouts_row_data['receiver_uid'] = json_decode($shoutout_data->receivers);
      $shoutouts_row_data['receiver_user_picture'] = $this->members->getUserPictureUrl(json_decode($shoutout_data->receivers));
      $shoutouts_row_data['receiver_user_name'] = $this->members->getUserName(json_decode($shoutout_data->receivers));
      $shoutouts_row_data['owner'] = $shoutout_data->user_id == $this->members->getUserId();
      $shoutouts_row_data['group_name'] = $this->groups->getGroupName($shoutout_data->group_id);
      $shoutouts_row_data['view'] = $view;
      $shoutouts_row_data['user_picture'] = $this->members->getUserPictureUrl($shoutout_data->user_id);
      $shoutouts_row_data['like_button'] = \Drupal::formBuilder()->getForm(\Drupal\ol_like\Form\LikeForm::class, 'shoutout', $shoutout_data->id);
      if($shoutouts_row_data['owner'] == TRUE) {
        $shoutouts_row_data['shoutout_edit_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_shoutouts\Form\AddShoutoutForm::class, 'edit', $shoutout_data->id);
      }
      // Render html row.
      $render = ['#theme' => 'shoutouts_card', '#vars' => $shoutouts_row_data];
      $shoutouts_html .= \Drupal::service('renderer')->render($render);
    }
    return $shoutouts_html;
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getShoutoutData($id){
    $query = \Drupal::database()->select('ol_shout_out', 'ib');
    $query->addField('ib', 'body');
    $query->addField('ib', 'group_id');
    $query->addField('ib', 'id');
    $query->addField('ib', 'created');
    $query->addField('ib', 'user_id');
    $query->addField('ib', 'receivers');
    $query->addField('user', 'name', 'username');
    $query->condition('ib.id', $id);
    $query->join('users_field_data', 'user', 'user.uid = ib.user_id');
    return $query->execute()->fetchObject();
  }

  /**
   * @return mixed
   */
  public function getShoutoutsGroupSettingsData(){
    $gid = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_shout_out_settings', 'ois');
    $query->addField('ois', 'id');
    $query->addField('ois', 'nudge_email');
    $query->condition('ois.group_id', $gid);
    return $query->execute()->fetchObject();
  }

}
