<?php

/**
 * @file
 * Contains \Drupal\Tests\rules\Integration\Condition\DataIsEmptyTest.
 */

namespace Drupal\Tests\rules\Integration\Condition;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\rules\Integration\RulesIntegrationTestBase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\Condition\DataIsEmpty
 * @group rules_conditions
 */
class DataIsEmptyTest extends RulesIntegrationTestBase {

  /**
   * The condition to be tested.
   *
   * @var \Drupal\rules\Core\RulesConditionInterface
   */
  protected $condition;

  /**
   * Real TypedDataManager.
   *
   * We will mock some of its methods for this test class.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $realTypedDataManager;

  /**
   * Mocked data definition.
   *
   * @var Prophecy
   */
  protected $dataDefinition;


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->condition = $this->conditionManager->createInstance('rules_data_is_empty');

    // We need better control of the TypedDataManager, so let's put a mock in front of it.
    $typed_data_manager = $this->realTypedDataManager = $this->typedDataManager;

    // We want to model a complex data type that won't "auto-unwrap", since we
    // are testing code that wants to try operations on complex typed data.
    // Let's call it a "duck", since if it walks like a duck, and talks like a duck,
    // then most likely it is complex data.
    $data_definition_interface = $this->prophesize(DataDefinitionInterface::class);
    $data_definition_interface->getDataType()->willReturn('duck');
    $data_definition_interface->isList()->willReturn(TRUE);
    $this->dataDefinition = $data_definition_interface->reveal();
    $typed_data_definition = [
      'id' => 'duck',
    ];

    // Now tell our mocked typed data manager all about our 'duck'.  Since it's
    // just the 'duck' that we want to model, we will will mock our duck, but
    // let the real TDM handle anything else, since we are also testing a number
    // of other types.
    $tdm_mock = $this->prophesize(TypedDataManagerInterface::class);
    $tdm_mock
      ->create(Argument::any())->will(function($args) use($typed_data_manager) {
        $definition = $args[0];
        return $typed_data_manager->create($definition);
      });
    $tdm_mock->createDataDefinition('duck')->willReturn($data_definition_interface->reveal());
    $tdm_mock->createDataDefinition(Argument::any())->will(function($args) use($typed_data_manager) {
      $type = $args[0];
      return $typed_data_manager->createDataDefinition($type);
    });
    $tdm_mock->getDefinition('duck')->willReturn($typed_data_definition);
    $tdm_mock->getDefinition(Argument::type('string'))->will(function($args) use($typed_data_manager) {
      $type = $args[0];
      return $typed_data_manager->getDefinition($type);
    });
    $tdm_mock->getCanonicalRepresentation(Argument::any())->will(function($args) use($typed_data_manager) {
      $data = $args[0];
      $type = $data->getDataDefinition()->getDataType();
      if ($type == 'duck') {
        // We do not want this data unwrapped, so just pass it back.
        return $data;
      }
      return $typed_data_manager->getCanonicalRepresentation($data);
    });

    // Update our TDM, and make sure container-aware classes see it.
    $this->typedDataManager = $tdm_mock->reveal();
    $this->container->set('typed_data_manager', $this->typedDataManager);
  }

  /**
   * Tests evaluating the condition.
   *
   * @covers ::evaluate
   */
  public function testConditionEvaluation() {
    // A couple of complex data instances. First, a 'duck' that isn't empty.
    $complex_data_empty = $this->prophesize(ComplexDataInterface::class);
    $complex_data_empty->isEmpty()->willReturn(TRUE)->shouldBeCalledTimes(1);
    $complex_data_empty->getDataDefinition()->willReturn($this->dataDefinition);
    $complex_data_empty->getValue()->willReturn(FALSE);

    $context = $this->condition->getContext('data');
    $context = Context::createFromContext($context, $complex_data_empty->reveal());
    $this->condition->setContext('data', $context);
    $this->assertTrue($this->condition->evaluate());

    $complex_data_full = $this->prophesize(ComplexDataInterface::class);
    $complex_data_full->isEmpty()->willReturn(FALSE)->shouldBeCalledTimes(1);
    $complex_data_full->getDataDefinition()->willReturn($this->dataDefinition);
    $complex_data_full->getValue()->willReturn(TRUE);

    $context = Context::createFromContext($context, $complex_data_full->reveal());
    $this->condition->setContext('data', $context);
    $this->assertFalse($this->condition->evaluate());

    //
    // These next few items should all return FALSE.
    //

    // A non-empty array.
    $context = Context::createFromContext($context, $this->getTypedData('list', [1, 2, 3]));
    $this->condition->setContext('data', $context);
    $this->assertFalse($this->condition->evaluate());

    // An array containing an empty list.
    $context = Context::createFromContext($context, $this->getTypedData('list', [[]]));
    $this->condition->setContext('data', $context);
    $this->assertFalse($this->condition->evaluate());

    // An array with a zero-value element.
    $context = Context::createFromContext($context, $this->getTypedData('list', [0]));
    $this->condition->setContext('data', $context);
    $this->assertFalse($this->condition->evaluate());

    // A scalar value.
    $context = Context::createFromContext($context, $this->getTypedData('integer', 1));
    $this->condition->setContext('data', $context);
    $this->assertFalse($this->condition->evaluate());

    $context = Context::createFromContext($context, $this->getTypedData('string', 'short string'));
    $this->condition->setContext('data', $context);
    $this->assertFalse($this->condition->evaluate());

    //
    // These should all return TRUE.
    //

    // An empty array.
    $context = Context::createFromContext($context, $this->getTypedData('list', []));
    $this->condition->setContext('data', $context);
    $this->assertTrue($this->condition->evaluate());

    // The false/zero/NULL values.
    $context = Context::createFromContext($context, $this->getTypedData('boolean', FALSE));
    $this->condition->setContext('data', $context);
    $this->assertTrue($this->condition->evaluate());

    $context = Context::createFromContext($context, $this->getTypedData('integer', 0));
    $this->condition->setContext('data', $context);
    $this->assertTrue($this->condition->evaluate());

    $context = Context::createFromContext($context, $this->getTypedData('string', NULL));
    $this->condition->setContext('data', $context);
    $this->assertTrue($this->condition->evaluate());

    // An empty string.
    $context = Context::createFromContext($context, $this->getTypedData('string', ''));
    $this->condition->setContext('data', $context);
    $this->assertTrue($this->condition->evaluate());
  }

}
