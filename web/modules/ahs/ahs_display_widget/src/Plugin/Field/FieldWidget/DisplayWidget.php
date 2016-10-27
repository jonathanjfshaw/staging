<?php

namespace Drupal\ahs_display_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldDefinitionInterface;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Render\Element;
use Symfony\Component\Validator\ConstraintViolationListInterface;


/**
 * Plugin implementation of the 'ahs_display' widget.
 *
 * @FieldWidget(
 *   id = "ahs_display",
 *   label = @Translation("Display"),
 *   description = @Translation("Display the field, not as a form."),
 *   field_types = {
 *     "comment",
 *     "datetime",
 *     "file",
 *     "image",
 *     "link",
 *     "list_string",
 *     "list_float",
 *     "list_integer",
 *     "path",
 *     "text_with_summary",
 *     "text",
 *     "text_long",
 *     "email",
 *     "boolean",
 *     "created",
 *     "changed",
 *     "timestamp",
 *     "string_long",
 *     "language",
 *     "decimal",
 *     "uri",
 *     "float",
 *     "password",
 *     "string",
 *     "integer",
 *     "uuid",
 *     "map"
 *   },
 *   list_class = "\Drupal\Core\Field\FieldItemList",
 * )
 */
class DisplayWidget extends WidgetBase {
  protected $pluginManager;
  protected $widget;


  /**
   * Constructs a WidgetBase object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->pluginManager = \Drupal::service('plugin.manager.field.widget');
    $this->setWidget();
  }

  // @todo: if widget is set on default formmode, but form mode selector is default, then infinite loop.
  
    /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'preview_widget_view_mode' => 'default',
      'preview_widget_form' => 'TRUE',
      'preview_widget_form_mode' => 'default',
      'preview_widget_edit' => FALSE,
      'preview_widget_move' => TRUE,
      'preview_widget_remove' => TRUE,
      'preview_widget_add' => TRUE,
      'preview_widget_add_open' => FALSE,
      'preview_widget_empty_message' => t('No items have been referenced.'),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entityDisplayRepository = \Drupal::service('entity_display.repository');

    $element['preview_widget_view_mode'] = array(
      '#type' => 'select',
      '#options' => $entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('Preview view mode'),
      '#default_value' => $this->getSetting('preview_widget_view_mode'),
      '#description' => t('Preview using the formatter & settings from this view mode.'),
    );

    $element['preview_widget_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Allow editing"),
      '#default_value' => $this->getSetting('preview_widget_form'),
    );

    $element['preview_widget_form_mode'] = array(
      '#type' => 'select',
      '#options' => $entityDisplayRepository->getFormModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('Form mode'),
      '#default_value' => $this->getSetting('preview_widget_form_mode'),
      '#description' => t('Handle data using the widget & settings from this form mode.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_widget_form]"]' => ['checked' => TRUE],
        ],
      ],
    );

    $element['preview_widget_edit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Allow editing"),
      '#default_value' => $this->getSetting('preview_widget_edit'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_widget_form]"]' => ['checked' => TRUE],
        ],
      ],
    );

    $element['preview_widget_move'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Allow reordering"),
      '#default_value' => $this->getSetting('preview_widget_move'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_widget_form]"]' => ['checked' => TRUE],
        ],
      ],
    );

    $element['preview_widget_remove'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Allow removing"),
      '#default_value' => $this->getSetting('preview_widget_remove'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_widget_form]"]' => ['checked' => TRUE],
        ],
      ],
    );

    $element['preview_widget_add'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Allow adding"),
      '#default_value' => $this->getSetting('preview_widget_remove'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_widget_form]"]' => ['checked' => TRUE],
        ],
      ],
    );

    $element['preview_widget_add_open'] = array(
      '#type' => 'checkbox',
      '#title' => t('Adding is open initially'),
      '#default_value' => $this->getSetting('preview_widget_add_open'),
      //'#description' => t('Hide controls for adding new items and reordering or removing existing items.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_widget_add]"]' => ['checked' => TRUE],
        ],
      ],
    );

    $element['preview_widget__empty_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Text if empty'),
      '#default_value' => $this->getSetting('preview_widget_empty_message'),
      '#description' => t('A message that will be displayed if the field has no data. Leave empty to show no message.'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $viewMode = ucfirst($this->getSetting('preview_widget_view_mode'));
    $summary[] =  t('Preview: @view_mode', array('@view_mode' => $viewMode));
    $formMode = ucfirst($this->getSetting('preview_widget_view_mode'));
    $summary[] =  t('Widget: @form_mode', array('@form_mode' => $formMode));

    $reorder = $this->getSetting('preview_widget_move')? t('reorder') : '';
    $remove = $this->getSetting('preview_widget_remove')? t('remove') : '';
    $add = $this->getSetting('preview_widget_add')? t('add') : '';
    $open = $this->getSetting('preview_widget_add_open')? t('(open initially)') : '';
    $actions = ucfirst(trim(trim($reorder . ' ' . $remove) . ' ' . $add . ' ' . $open));
    $actions = empty($actions) ? t('Nothing') : $actions;
    $summary[] =  t('Allowed: @actions', array('@actions' => $actions));

    return $summary;
  }

  protected function setWidget() {
    $entityType = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    $fieldName = $this->fieldDefinition->getName();
    $formMode = $this->getSetting('preview_widget_form_mode');
    $configuration = entity_get_form_display($entityType, $bundle, $formMode)
      ->getComponent($fieldName);

    if ($configuration && isset($configuration['type']) && $this->fieldDefinition) {
      $this->widget = $this->pluginManager->getInstance([
        'field_definition' => $this->fieldDefinition,
        'form_mode' => $formMode,
        // NO! @todo: delete this comment: No need to prepare, defaults have been merged in setComponent().
        'prepare' => TRUE,
        'configuration' => $configuration
      ]);
    }
  }

  /*
  public function __call($method, $args) {
    // This silences reference warnings but fails to ensure references
    // are returned correctly to whatever called this __call.
    $argsByRef = array();
    foreach($args as $k => &$arg){
      $argsByRef[$k] = &$arg;
    }
    return call_user_func_array([$this->widget, $method], $args);
  }
*/

  // I've chosen to extend widgetbase and not reimplement the static methods as
  // they can't refer to $this and load the widget.
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $this->widget->massageFormValues($values, $form, $form_state);
  }
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $this->widget->errorElement($element, $violation, $form, $form_state);
  }
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    return $this->widget->errorElement($items, $violations, $form, $form_state);
  }
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    return $this->widget->extractFormValues($items, $form, $form_state);    
  }


  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = $this->widget->formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }

  protected function isBaseMethod($methodName) {
    $method = new \ReflectionMethod($this->widget, $methodName);
    $className = $method->getDeclaringClass()->getName();
    return ($className === 'Drupal\Core\Field\WidgetBase');
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // If editing is not allowed, don't use a widget at all.
    // @todo Possibly this should be replaced with using #access = FALSE
    if (!$this->getSetting('preview_widget_form')) {
      return $items->view($this->getSetting('preview_widget_view_mode'));
    }
    
    // If the widget's form method overrides widgetBase,
    // we have to defer to the widget.
    if (!$this->isBaseMethod("form")) {
      $elements = $this->widget->form($items, $form, $form_state, $get_delta);
    }
    // Else we can call the widgetBase form method directly here, which will
    // then use our modified versions of the formMultipleElements and
    // formSingleElement methods.
    else {
      $elements = parent::form($items, $form, $form_state, $get_delta);
      $elements['#attributes']['class'][] = 'preview-widget-previewing';
      $elements['#attributes']['class'][] = 'preview-widget-hideui-requested';
      $elements['#attached']['library'][] = 'ahs_er_enhanced/hideui';
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    // If the widget's formMultipleElements method overrides widgetBase,
    // we have to defer to the widget.
    if (!$this->isBaseMethod("formMultipleElements")) {
      $elements = $this->widget->formMultipleElements($items, $form, $form_state);
    }
    // Else we can call the widgetBase method directly here.
    else {
      $elements = parent::formMultipleElements($items, $form, $form_state);
      if ((count($items) < 2) && !empty($this->getSetting('preview_empty_message'))) {
        $elements['preview_widget_empty_message'] = [
          '#markup' => '<p class="preview-widget-empty-message">' . $this->getSetting('preview_empty_message') . '</p>',
        ];
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // If the widget's formMultipleElements method overrides widgetBase,
    // we have to defer to the widget.
    if (!$this->isBaseMethod("formSingleElement")) {
      $element = $this->widget->formSingleElement($items, $delta, $element, $form, $form_state);
    }
    // Else we can use our own method building on the widgetBase method directly.
    else {
      $element = $this->formSingleElementWithPreview($element, $items, $delta, $element, $form, $form_state);
    }
    return $element;
  }

  protected function formSingleElementWithPreview (Array $element,FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formSingleElement($items, $delta, $element, $form, $form_state);
    // Hide the last $item element
    if ($delta === count($items)) {

    }
  }


  /**
   * {@inheritdoc}
   */
//  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
//    return $element;
//  }


}
