<?php

namespace Drupal\idix_multistep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\idix_multistep\MultistepController;
use Drupal\idix_multistep\StepIndicator;

class MultistepFactory {

  /**
   * Check if valid multi step form.
   *
   * @param array $form
   *   Form array.
   *
   * @return bool
   *   TRUE if form multi step.
   */
  public function check_form_multistep(array $form) {
    if (isset($form['#fieldgroups']) && !empty($form['#fieldgroups'])) {
      foreach ($form['#fieldgroups'] as $fieldgroup) {
        if (is_object($fieldgroup) && $fieldgroup->format_type == 'form_step') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function getMultiStepController(array &$form, FormStateInterface $form_state){
    return new MultistepController($form, $form_state);
  }

  public function getStepIndicator(array $form, FormStateInterface $form_state, $current_step){
    return new StepIndicator($form, $form_state, $current_step);
  }

}