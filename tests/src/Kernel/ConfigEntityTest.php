<?php

/**
 * @file
 * Contains \Drupal\rules\Tests\ConfigEntityTest.
 */

namespace Drupal\Tests\rules\Kernel;

use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Context\ContextConfig;

/**
 * Tests storage and loading of Rules config entities.
 *
 * @group rules
 */
class ConfigEntityTest extends RulesDrupalTestBase {

  /**
   * The entity storage for Rules config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Disable strict config schema checking for now.
   *
   * @todo: Fix once config schema has been improved.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = $this->container->get('entity.manager')->getStorage('rules_component');
  }

  /**
   * Tests that an empty rule configuration can be saved.
   */
  public function testSavingEmptyRule() {
    $rule = $this->expressionManager->createRule();
    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();
  }

  /**
   * Tests saving the configuration of an action and then loading it again.
   */
  public function testConfigAction() {
    $action = $this->expressionManager->createAction('rules_test_log');
    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($action);
    $config_entity->save();

    $loaded_entity = $this->storage->load('test_rule');
    $this->assertEqual($loaded_entity->get('expression_id'), 'rules_action', 'Expression ID was successfully loaded.');
    $this->assertEqual($loaded_entity->get('configuration'), $action->getConfiguration(), 'Action configuration is the same after loading the config.');

    // Create the Rules expression object from the configuration.
    $expression = $loaded_entity->getExpression();
    $expression->execute();

    // Test that the action logged something.
    $this->assertRulesLogEntryExists('action called');
  }

  /**
   * Test saving a deeply nested rule.
   *
   * @todo This test currently fails if $strictConfigSchema = TRUE.
   */
  public function testNestedRuleSave() {
    // This test is specific to reaction rules, so get the appropriate
    // entity storage:
    $storage = $this->container->get('entity.manager')->getStorage('rules_reaction_rule');
    // Create a nested expression.
    $and = $this->expressionManager->createAnd()
      ->addCondition('rules_test_false')
      ->addCondition('rules_test_true', ContextConfig::create()->negateResult())
      ->negate();

    $or = $this->expressionManager->createOr()
      ->addCondition('rules_test_true', ContextConfig::create()->negateResult())
      ->addCondition('rules_test_false')
      ->addCondition($and);

    $rule = $this->expressionManager->createReactionRule();
    $rule->addCondition('rules_test_true')
      ->addCondition('rules_test_true')
      ->addExpressionObject($or);

    $rule->addAction('rules_test_log');

    // Save it as a config_entity.
    $rule_config = $storage->create(['id' => 'test_big_hairy_rule']);
    $rule_config->setExpression($rule);
    $rule_config->set('event', 'rules_user_login');
    $rule_config->set('tag', 'demo example');
    $rule_config->set('description', 'Component that uses a nested rule.');
    $rule_config->save();

    // Try to retrieve it.
    $retrieved_config = $storage->load('test_big_hairy_rule');
    $this->assertNotEmpty($retrieved_config, "We created and retrieved a big hairy rule expression");
  }

  /**
   * Tests saving the nested config of a rule and then loading it again.
   */
  public function testConfigRule() {
    // Create a simple rule with one action and one condition.
    $rule = $this->expressionManager
      ->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();

    $loaded_entity = $this->storage->load('test_rule');
    // Create the Rules expression object from the configuration.
    $expression = $loaded_entity->getExpression();
    $expression->execute();

    // Test that the action logged something.
    $this->assertRulesLogEntryExists('action called');
  }

  /**
   * Make sure that expressions using context definitions can be exported.
   */
  public function testContextDefinitionExport() {
    $rule = $this->expressionManager->createRule([
      'context_definitions' => [
        'test' => ContextDefinition::create('string')
          ->setLabel('Test string')
          ->toArray(),
      ],
    ]);

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();

    $loaded_entity = $this->storage->load('test_rule');
    // Create the Rules expression object from the configuration.
    $expression = $loaded_entity->getExpression();
    $context_definitions = $expression->getContextDefinitions();
    $this->assertEqual($context_definitions['test']->getDataType(), 'string', 'Data type of context definition is correct.');
    $this->assertEqual($context_definitions['test']->getLabel(), 'Test string', 'Label of context definition is correct.');
  }

  /**
   * Tests that a reaction rule config entity can be saved.
   */
  public function testReactionRuleSaving() {
    $rule = $this->expressionManager->createReactionRule();
    $storage = $this->container->get('entity.manager')->getStorage('rules_reaction_rule');
    $config_entity = $storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();
  }

}
