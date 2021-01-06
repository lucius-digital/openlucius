<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_main\Services\OlSections;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class CheckinConfigForm.
 */
class GroupConfigForm extends FormBase {

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $sections
   */
  protected $sections;

  /**
   * @var $files
   */
  protected $files;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_main\Services\OlSections $sections
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(OlGroups $groups, OlSections $sections, OlFiles $files) {
    $this->groups = $groups;
    $this->sections = $sections;
    $this->files = $files;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.groups'),
      $container->get('olmain.sections'),
      $container->get('olmain.files')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get group name.
    $gid = $this->groups->getCurrentGroupId();
    $name = $this->groups->getGroupName($gid);
    $sections = $this->sections->getSectionsData();
    // Get section override info and decode json to an array.
    $section_overrides_json = $this->sections->getSectionOverridesData($gid);
    $section_overrides = json_decode($section_overrides_json, true);
    // Build usable array from $sections.
    $options = $this->sections->buildOptionsFromSections($sections);
    // Get enabled sections, for default_value.
    $enabled_sections = $this->sections->getEnabledSections($gid);
    // Build file location
    $hdd_file_location = $this->files->buildFileLocaton('group_header');
    // Set default header image, if uploaded.
    $current_header_fid = $this->groups->getHeaderImage();
    $default_fid = (is_numeric($current_header_fid)) ? array($current_header_fid) : '';
    // Handle 'group on top' option.
    // $on_top_title = array( '1' => t('Show on top in group list (in left sidebar)'));
    // $on_top_default = array($this->groups->isOnTop());
    // Handle 'archived'.
    $archived_title = array( '1' => '');
    $is_archived = $this->groups->isArchived();
    $is_archived = ($is_archived == 1) ? 0 : 1; // Switch, because checked = 1 means archived, but status 0 is archived in dbase.
    $archived_default = array($is_archived);


    $form['name'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group row"><div class="col-sm-8">',
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $name ,
      '#required' => true,
      '#attributes' => array('placeholder' => t('Add a group name...'), 'class' => array('form-control')),
      '#weight' => '10',
      '#suffix' => '</div></div>'
    ];
    $form['file'] = array(
      '#prefix' => '<div class="form-group p-5">',
      '#title' => t('Group image'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#default_value' => $default_fid,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => FALSE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedImageExtentions(),
      ),
      '#theme' => 'image_widget',
      '#preview_image_style' => '50x50',
      '#attributes' => [
        'class' => array('form-control')
      ],
      '#weight' => '15',
      '#suffix' => '</div>'
    );
    $form['sections'] = [
      '#prefix' => '<div class="form-group">',
      '#type' => 'checkboxes',
      '#title' => t('Enabled Sections'),
      '#default_value' => $enabled_sections,
      '#options' => $options,
      '#required' => true,
      '#weight' => '20',
      '#attributes' => array(
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ),
      '#suffix' => '</div>'
    ];
    $form['homepage'] = [
      '#prefix' => '<div class="form-group row"><div class="col-sm-6">',
      '#type' => 'select',
      '#title' => t('Group Homepage'),
      '#default_value' => array($this->groups->getGroupHome($gid)),
      '#options' => $options,
      '#attributes' => array('class' => array('form-control')),
      '#weight' => '30',
      '#suffix' => '</div></div>'
    ];
/*    $form['on_top'] = array(
      '#prefix' => '<div class="form-group">',
      '#title' => t('Show on top'),
      '#type' => 'checkboxes',
      '#options' => $on_top_title,
      '#default_value' => $on_top_default,
      '#weight' => '40',
      '#suffix' => '</div>'
    );*/
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="form-group">
                        <legend>
                            <span class="fieldset-legend">' .t('Custom section names') .'</span>
                        </legend>
                    </div>',
      '#allowed_tags' => ['div','span','legend'],
      '#weight' => '45',
    ];
    // Section name overrides.
    foreach ($options as $key => $option) {
      /* Activate this to not show override if section is disabled,
         but this will delete the override names that were entered.
       if (!in_array($key, $enabled_sections)){
        continue;
      }*/
      // If override is present, use that as default value.
      $override_value = (!empty($section_overrides[$key])) ? $section_overrides[$key] : '';
      $form['override_'.$key] = [
        '#prefix' => '<div class="form-group row">
                         <div class="col-sm-2 col-form-label">'.$option.'</div>
                      <div class="col-sm-6">',
        '#type' => 'textfield',
        '#default_value' => $override_value,
        '#required' => FALSE,
        '#attributes' => [
          'placeholder' => t('Your custom name...'),
          'class' => ['form-control']
        ],
        '#weight' => '50',
        '#suffix' => '</div></div>'
      ];
    }
    $form['status'] = array(
      '#prefix' => '<div class="form-group">',
      '#title' => t('Published / Archived'),
      '#type' => 'checkboxes',
      '#options' => $archived_title,
      '#default_value' => $archived_default,
      '#weight' => '60',
      '#attributes' => array(
        'data-toggle' => 'toggle',
        'data-onstyle' => 'warning',
        'data-offstyle' => 'success',
        'data-size' => 's',
        'data-off' => t('Published'),
        'data-on' => t('Archived'),
      ),
      '#suffix' => '</div>'
    );
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '100',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $this->t('Save Settings'),
      '#suffix' => '</div>'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Get value.
    $name = Html::escape($form_state->getValue('name'));
    // Set an error for the form element with a key of "title".
    if (strlen($name) < 2) {
      $form_state->setErrorByName('name', $this->t('Name not changed: it must be at least 2 characters long.'));
    }
    if (strlen($name) > 50) {
      $form_state->setErrorByName('name', $this->t('Group not saved: name can not be more then 50 characters long.'));
    }
    // Make sure homepage can't be set to disabled section.
    $sections = $form_state->getValue('sections');
    $homepage = $form_state->getValue('homepage');
    if(empty($sections[$homepage])){
      //$form_state->setErrorByName('homepage', $this->t('The homepage has to be an enabled section.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // We need this to save section name overrides, even if they are disabled by user.
    $all_sections = $this->sections->getSectionsData();
    $options = $this->sections->buildOptionsFromSections($all_sections);
    // Save group settings.
    $this->groups->saveGroupSettings($form_state, $options);

    // Handle files.
    $file = $form_state->getValue('file');
    $current_header_fid = $this->groups->getHeaderImage();
    // If there is no file, remove existing (if any).
    if(empty($file[0])){
      $this->removeExistingHeaderFile();
    }
    // Check if there is a change in file, if so: remove existing and save new.
    elseif ($current_header_fid != $file[0]){
      $this->removeExistingHeaderFile();
      $this->files->saveFiles($file, 'group_header');
    }
  }

  /**
   * Remove existing file.
   */
  private function removeExistingHeaderFile() {
    $current_header_fid = $this->groups->getHeaderImage();
    if ($current_header_fid) {
      $this->files->removeOlFileAndFile($current_header_fid, FALSE);
    }
  }


}


