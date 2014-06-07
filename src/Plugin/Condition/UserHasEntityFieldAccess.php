<?php

/**
 * @file
 * Contains \Drupal\rules\Plugin\Condition\UserHasEntityFieldAccess.
 */

namespace Drupal\rules\Plugin\Condition;

use Drupal\Core\TypedData\TypedDataManager;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Engine\RulesConditionBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'User has entity field access' condition.
 *
 * @Condition(
 *   id = "rules_entity_field_access",
 *   label = @Translation("User has entity field access")
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 * @todo: Add group information from Drupal 7.
 */
class UserHasEntityFieldAccess extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public static function contextDefinitions(TypedDataManager $typed_data_manager) {

    $contexts['entity'] = ContextDefinition::create($typed_data_manager, 'entity')
      ->setLabel(t('Entity'));
    $contexts['field'] = ContextDefinition::create($typed_data_manager, 'string')
      ->setLabel(t('Field Name'));
    $contexts['op'] = ContextDefinition::create($typed_data_manager, 'string')
      ->setLabel(t('Operation'));
    $contexts['account'] = ContextDefinition::create($typed_data_manager, 'entity:user')
      ->setLabel(t('User'));

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('User has access to field on entity');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $entity = $this->getContextValue('entity');
    $field = $this->getContextValue('field');
    $op = $this->getContextValue('op');
    $account = $this->getContextValue('account');
    $entity_access_controller = \Drupal::entityManager()->getAccessController($entity->getEntityTypeId());
    $lang_code = Language::LANGCODE_DEFAULT;
    return $entity_access_controller->access($entity, $op, $lang_code, $account);
  }

}
