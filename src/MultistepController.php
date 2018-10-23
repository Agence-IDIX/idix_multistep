<?php

namespace Drupal\idix_multistep;

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
   * Form button.
   *
   * @var FormButton
   */
  public $formButton;

  /**
   * Stored values from $form_state.
   *
   * @var array
   */
  protected $storedValues;

  /**
   * Input values from $form_state.
   *
   * @var array
   */
  protected $inputValues;

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

    // Initialize empty storage.
    $this->inputValues = [];
    $this->storedValues = [];
  }

  /**
   * Save input values from current step.
   */
  public function saveInputValues() {
    $stored_input = $this->inputValues;
    $stored_input[$this->currentStep] = $this->formState->getUserInput();

    $this->inputValues = $stored_input;
  }

  /**
   * Get input values.
   */
  public function getInputValues() {
    return $this->inputValues;
  }

  /**
   * Save stored values from current step.
   */
  public function saveStoredValues() {
    $stored_values = $this->storedValues;
    $stored_values[$this->currentStep] = $this->getStepValues($this->steps[$this->currentStep]);

    $this->storedValues = $stored_values;
  }

  /**
   * Get stored values.
   */
  public function getStoredValues() {
    return $this->storedValues;
  }

  /**
   * Prepare Multistep Form.
   *
   * @param array $form
   *   Reference to form.
   */
  public function rebuildForm(array &$form) {
    /** @var \Drupal\idix_multistep\MultistepFactory $multistep_factory */
    $multistep_factory = \Drupal::service('multistep_factory');
    // Add step indicator.
    $this->stepIndicator = $multistep_factory->getStepIndicator($form, $this->formState, $this->currentStep);
    $this->stepIndicator->render($form);

    // Add additional button for form.
    $this->formButton = new FormButton($form, $this->formState, $this->currentStep);
    $this->formButton->render($form);

    unset($form['actions']['next']['#limit_validation_errors']);
    foreach ($this->steps as $key => $step) {
      $all_children = $this->getAllChildren($step);
      if (!empty($all_children)) {
        // Another step.
        if ($key != $this->currentStep) {
          foreach ($all_children as $child_id) {
            if (isset($form[$child_id])) {
              /*if ($this->currentStep != count($this->steps) - 1) {
                unset($form[$child_id]);
              }
              else {*/
                $form[$child_id]['#access'] = FALSE;
                // @todo need found solution with password.
                if ($child_id == 'account' && isset($form[$child_id]['pass'])) {
                  $form[$child_id]['pass']['#required'] = FALSE;
                }
              /*}*/
            }
          }
        }
        else {
          foreach ($all_children as $child_id) {
            if (isset($form[$child_id])) {
              /*if($this->currentStep == count($this->steps) - 1){
                $form['actions']['submit']['#limit_validation_errors'][] = [$child_id];
              }else{*/
                $form['actions']['next']['#limit_validation_errors'][] = [$child_id];
              //}
            }
          }
        }
      }
    }

    // Last step.
    /*if ($this->currentStep == count($this->steps) - 1) {
      foreach ($form as $element_key => $form_element) {
        if (is_array($form_element) && isset($form_element['#type'])) {
          if (isset($form['actions']['next']['#limit_validation_errors'])) {
            unset($form['actions']['next']['#limit_validation_errors']);
          }
        }
      }
    }*/

  }

}
