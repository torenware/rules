<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContextAwarePluginBase.
 */

namespace Drupal\rules\Core;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * Base class for rules plugins.
 */
abstract class RulesPluginBase extends ContextAwarePluginBase {

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
