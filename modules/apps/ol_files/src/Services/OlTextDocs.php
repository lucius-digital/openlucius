<?php

namespace Drupal\ol_files\Services;

use Drupal\Core\Url;
use Drupal\ol_file\Entity\OlFile;
use Drupal\ol_text_doc\Entity\OlTextDoc;

/**
 * Class OlTextdocs.
 */
class OlTextDocs{

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
   * Saves a new text document.
   *
   * @param $name
   * @param $body
   * @param bool $send_mail
   *
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveTextDoc($name, $body, $send_mail = false){
    // Get group id.
    $gid = $this->route->getParameter('gid');
    // Save new TextDoc.
    $doc = OlTextDoc::create([
      'name' => $name,
      'body' =>  $body,
      'group_id' => $gid
    ]);
    $doc->save();
    $text_doc_id = $doc->id();
    // Save new 'File'.
    $doc = OlFile::create([
      'name' => $name,
      'group_id' => $gid,
      'entity_id' => $text_doc_id,
      'entity_type' => 'text_doc',
    ]);
    $doc->save();
    // Add stream item.
    $stream_body = t('Add a text document: @doc', array('@doc' => $name)); // Create new stream item.
    $this->stream->addStreamItem($gid, 'text_doc_added', $stream_body, 'text_doc', $text_doc_id); // Create stream item.
    // Mail if true
    if($send_mail == true){
      // Generate url and send mails.
      $url = Url::fromRoute('ol_files.group_files', ['gid' => $gid])->toString();
      $this->mail->sendMail($name, $url);
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your text document was added successfully.'));
    // Return id
    return $text_doc_id;
  }

  /**
   * @param $id
   * @return mixed
   */
  public function getTextDocData($id){
    // Get message detail data.
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'body');
    $query->addField('olt', 'group_id');
    $query->addField('olt', 'id');
    $query->addField('olt', 'name');
    $query->addField('olt', 'created');
    $query->addField('olt', 'changed');
    $query->addField('olt', 'user_id');
    $query->addField('user', 'name', 'username');
    $query->condition('olt.id', $id);
    $query->join('users_field_data', 'user', 'user.uid = olt.user_id');
    return $query->execute()->fetchObject();
  }

  /**
   * @param $text_doc
   * @return string
   */
  public function renderTextDoc($text_doc){
    // Build vars and render HTML rows via twig file.
    $text_doc_row_data['body'] = $text_doc->body;
    $text_doc_row_data['name'] = $text_doc->name;
    $text_doc_row_data['created'] = $text_doc->created;
    $text_doc_row_data['changed'] = $text_doc->changed;
    $text_doc_row_data['username'] = $text_doc->username;
    $text_doc_row_data['id'] = $text_doc->id;
    $text_doc_row_data['id_group'] = $text_doc->group_id;
    $text_doc_row_data['user_id'] = $text_doc->user_id;
    $text_doc_row_data['owner'] = $text_doc->user_id == $this->members->getUserId();
    $text_doc_row_data['user_picture'] = $this->members->getUserPictureUrl($text_doc->user_id);
    $text_doc_row_data['link'] = '/group/'.$text_doc->group_id.'/files/text_doc/'.$text_doc->id;
    if($text_doc_row_data['owner'] == true) {
      $text_doc_row_data['message_edit_form'] =
        \Drupal::formBuilder()->getForm(\Drupal\ol_files\Form\TextDocForm::class, 'edit', $text_doc->id);
    }
    $text_doc_row_data['comment_count'] = $this->comments->getCommentCount($text_doc->id, 'text_doc', $text_doc->group_id);
    $text_doc_row_data['files'] = $this->files->getAttachedFiles('text_doc_attachment', $text_doc->id);
    // Render the data to html.
    $render = ['#theme' => 'text_doc_card', '#vars' => $text_doc_row_data];
    return \Drupal::service('renderer')->render($render);
  }

  /**
   * @param $data
   * @return mixed
   */
  public function getTextDocTitle($data){
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'name');
    $query->condition('olt.id', $data->id);
    return $query->execute()->fetchField();
  }


  /**
   * @param $id
   * @param $name
   * @param $body
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateTextDoc($id, $name, $body, $send_mail = false){
    // Update message  with spoofing protection.
    if($this->isDocOwner($id)) {
      $entity = OlTextDoc::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();

      // Update name in ol_file table | Duplicate stuff, guilty, will do better in future :)
      \Drupal::database()->update('ol_file')
        ->fields(['name' => $name])
        ->condition('entity_id', $id)
        ->condition('entity_type', 'text_doc')
        ->execute();

      // Mail if checked by user.
      if($send_mail == true){
        // Generate url and send mails.
        $gid = $this->route->getParameter('gid');
        $url = Url::fromRoute('ol_files.text_doc', ['gid' => $gid, 'id' => $id])->toString();
        $this->mail->sendMail($name, $url);
      }
      // Add message.
      \Drupal::messenger()->addStatus(t('Your text document was updated successfully.'));
    }
  }

  /**
   * @param $id
   * @return bool
   */
  private function isDocOwner($id){
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'user_id');
    $query->condition('olt.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }


}
