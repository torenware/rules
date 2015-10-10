<?php

/**
 * @file
 * Contains \Drupal\rules\Core\RulesConditionBase.
 */

namespace Drupal\rules\Core;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\rules\Context\ContextProviderTrait;

/**
 * Base class for rules conditions.
 *
 * @todo Figure out whether buildConfigurationForm() is useful to Rules somehow.
 */
abstract class RulesConditionBase extends ConditionPluginBase implements RulesConditionInterface {

  use ContextProviderTrait;
  use ExecutablePluginTrait;

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions() {
    // Do not refine anything by default.
  }

  /**
   * {@inheritdoc}
   */
  public function negate($negate = TRUE) {
    $this->configuration['negate'] = $negate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Provide a reasonable default implementation that calls doEvaluate() while
    // passing the defined context as arguments.
    $args = [];
    foreach ($this->getContexts() as $name => $context) {
      $args[$name] = $context->getContextValue();
    }
    return call_user_func_array([$this, 'doEvaluate'], $args);
  }

  /**
   * {@inheritdoc}
   *
   * Override so we get context in cannonical order.
   */
  public function getContexts() {
    // Make sure all context objects are initialized.
    $ordered_context = [];
    foreach ($this->getContextDefinitions() as $name => $definition) {
      $ordered_context[$name] = $this->getContext($name);
    }
    return $ordered_context;
  }

}
