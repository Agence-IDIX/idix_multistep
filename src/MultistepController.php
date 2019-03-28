<?php

namespace Drupal\idix_multistep;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MultistepController.
 *
 * @package Drupal\idix_multistep
 */
class MultistepController extends FormStep {

  /**
   * Steps indicator.
   *
   * @var StepIndicator
   */
  public $stepIndicator;

  /**
   * MultistepController constructor.
   *
   * @param array $form
   *   Form settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function __construct(array &$form, FormStateInterface $form_state) {
    parent::__construct($form, $form_state);
  }


  public function rebuildForm(array &$form){
    /** @var \Drupal\idix_multistep\MultistepFactory $multistep_factory */
    $multistep_factory = \Drupal::service('multistep_factory');

    $form['#attached']['library'][] = 'idix_multistep/general';

    unset($form['#pre_render']);
    unset($form['_field_layout']);

    foreach($this->steps as $key => $step){
      $all_children = $this->getAllChildren($step);
      if (!empty($all_children)) {
        $step_key = 'step_' . $key . '_pane';
        $form[$step_key] = [
          '#type' => 'container',
          '#id' => $step_key,
          '#attributes' => [
            'class' => [
              'multistep_pane',
              $key == 0 ? '' : 'multistep_pane_hidden'
            ]
          ]
        ];

        // Add step indicator.
        $stepIndicator = $multistep_factory->getStepIndicator($form, $this->formState, $key);
        $form[$step_key]['step_labels'] = $stepIndicator->getRender();

        $limit_validation = [];

        $form[$step_key]['messages'] = [
          '#type' => 'container',
          '#id' => $step_key . '_messages',
          '#weight' => 0
        ];

        $form[$step_key]['fields'] = [
          '#type' => 'fieldset',
          '#title' => '',
          '#id' => $step_key . '_fields',
          '#weight' => 1,
          '#attributes' => [
            'class' => [
              'multistep-fieldset'
            ]
          ]
        ];

        $hasRequired = false;

        foreach ($all_children as $child_id) {
          if (isset($form[$child_id])) {
            $form[$step_key]['fields'][$child_id] = $form[$child_id];

            if(isset($form[$child_id]['widget']['#required']) && $form[$child_id]['widget']['#required']){
              $hasRequired = true;
            }

            unset($form[$child_id]);
            $limit_validation[] = [$child_id];
          }
        }

        $form[$step_key]['fields']['actions'] = [
          '#type' => 'actions',
        ];

        if($hasRequired){
          $form[$step_key]['fields']['actions']['#prefix'] = new FormattableMarkup('<div class="form-required-nota">@required_fields</div>', array(
            '@required_fields' => t('Required fields')
          ));
        }

        $back_button = $this->getBackButton($key);
        if($back_button !== false){
          $form[$step_key]['fields']['actions']['back_' . $key] = $back_button;
        }
        if($key != count($this->steps) - 1) {
          $form[$step_key]['fields']['actions']['next_' . $key] = $this->getNextButton($key, $limit_validation);
        }else{
          $form[$step_key]['fields']['actions']['submit'] = $form['actions']['submit'];
        }
      }
    }
    unset($form['actions']);
  }

  /**
   * get next button.
   *
   * @param int $step step
   */
  public function getNextButton($step, $limit_validation_errors = []) {
    $step_format_settings = $this->getOneStepSettings($step)->format_settings;

    return [
      '#type' => 'button',
      '#name' => 'next_' . $step,
      '#value' => $step_format_settings['next_button_text'],
      '#ajax' => [
        'callback' => 'Drupal\idix_multistep\MultistepController::ajaxStepNext',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#step' => $step,
      '#limit_validation_errors' => $limit_validation_errors,
      '#weight' => 0.1
    ];
  }

  /**
   * get back button.
   *
   * @param int $step step
   */
  public function getBackButton($step) {
    $step_format_settings = $this->getOneStepSettings($step)->format_settings;
    $button = false;
    if (!empty($step_format_settings['back_button_show'])) {
      // Add back button and remove validation.
      $button = [
        '#type' => 'button',
        '#name' => 'back_' . $step,
        '#value' => $step_format_settings['back_button_text'],
        '#ajax' => [
          'callback' => 'Drupal\idix_multistep\MultistepController::ajaxStepBack',
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ]
        ],
        '#step' => $step,
        '#limit_validation_errors' => [],
        '#weight' => 0,
      ];
    }
    return $button;
  }

  public static function ajaxStepBack(array $form, FormStateInterface $form_state){
    $response = new AjaxResponse();

    $trigger = $form_state->getTriggeringElement();
    $current_step = $trigger['#step'];

    $prev_step = $current_step - 1;

    $response->addCommand(new HtmlCommand('#step_' . $prev_step . '_pane_messages', ''));
    $response->addCommand(new InvokeCommand('#step_' . $current_step . '_pane', 'addClass', ['multistep_pane_hidden']));
    $response->addCommand(new InvokeCommand('#step_' . $prev_step . '_pane', 'removeClass', ['multistep_pane_hidden']));
    $response->addCommand(new InvokeCommand(null, 'scrollTo', ['node-subscription-multistep-form']));

    return $response;
  }

  public static function ajaxStepNext(array $form, FormStateInterface $form_state){
    $response = new AjaxResponse();

    $trigger = $form_state->getTriggeringElement();
    $current_step = $trigger['#step'];

    if($form_state->hasAnyErrors()){
      $messages = [
        '#type' => 'status_messages',
      ];
      $response->addCommand(new HtmlCommand('#step_' . $current_step . '_pane_messages', $messages));

      $replace = $form['step_' . $current_step . '_pane']['fields'];

      $response->addCommand(new ReplaceCommand('#step_' . $current_step . '_pane_fields', $replace));
    }else{
      $next_step = $current_step+1;
      $response->addCommand(new HtmlCommand('#step_' . $current_step . '_pane_messages', ''));
      $response->addCommand(new InvokeCommand('#step_' . $current_step . '_pane', 'addClass', ['multistep_pane_hidden']));
      $response->addCommand(new InvokeCommand('#step_' . $next_step . '_pane', 'removeClass', ['multistep_pane_hidden']));
    }

    $response->addCommand(new InvokeCommand(null, 'scrollTo', ['node-subscription-multistep-form']));

    return $response;
  }

}
