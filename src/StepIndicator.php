<?php

namespace Drupal\idix_multistep;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class StepIndicator.
 *
 * @package Drupal\idix_multistep
 */
class StepIndicator extends FormStep {

  /**
   * Constructor.
   *
   * @param array $form
   *   Form settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param int $current_step
   *   Current step.
   */
  public function __construct(array $form, FormStateInterface $form_state, $current_step) {
    parent::__construct($form, $form_state);

    $this->currentStep = $current_step;
  }

  /**
   * Create indicator.
   */
  private function createIndicator() {
    $steps_label = [
      '#type' => 'item',
      '#weight' => -1,
      '#theme' => 'multistep_indicator',
      '#steps' => $this->steps,
      '#current_step' => $this->currentStep,
    ];

    return $steps_label;
  }

  /**
   * Get Indicator.
   *
   * @param array $form
   *   Reference to form.
   */
  public function getRender() {
    return $this->createIndicator();
  }

}
