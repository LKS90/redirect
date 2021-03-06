<?php

/**
 * @file
 * Contains Drupal\Tests\redirect\Unit\RedirectRequestSubscriberTest.
 */

namespace Drupal\Tests\redirect\Unit;

use Drupal\redirect\RedirectChecker;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Tests the redirect logic.
 *
 * @group redirect
 */
class RedirectCheckerTest extends UnitTestCase {

  /**
   * Tests the can redirect check.
   */
  public function testCanRedirect() {

    $config = array('redirect.settings' => array('global_admin_paths' => FALSE));

    $state = $this->getMockBuilder('Drupal\Core\State\StateInterface')
      ->getMock();
    $state->expects($this->any())
      ->method('get')
      ->with('system.maintenance_mode')
      ->will($this->returnValue(FALSE));

    $checker = new RedirectChecker($this->getConfigFactoryStub($config), $state);

    // All fine - we can redirect.
    $request = $this->getRequestStub('index.php', 'GET');
    $this->assertTrue($checker->canRedirect($request), 'Can redirect');

    // The script name is not index.php.
    $request = $this->getRequestStub('statistics.php', 'GET');
    $this->assertFalse($checker->canRedirect($request), 'Cannot redirect script name not index.php');

    // The request method is not GET.
    $request = $this->getRequestStub('index.php', 'POST');
    $this->assertFalse($checker->canRedirect($request), 'Cannot redirect other than GET method');

    // Maintenance mode is on.
    $state = $this->getMockBuilder('Drupal\Core\State\StateInterface')
      ->getMock();
    $state->expects($this->any())
      ->method('get')
      ->with('system.maintenance_mode')
      ->will($this->returnValue(TRUE));

    $checker = new RedirectChecker($this->getConfigFactoryStub($config), $state);

    $request = $this->getRequestStub('index.php', 'GET');
    $this->assertFalse($checker->canRedirect($request), 'Cannot redirect if maintenance mode is on');

    // We are at a admin path.
    $state = $this->getMockBuilder('Drupal\Core\State\StateInterface')
      ->getMock();
    $state->expects($this->any())
      ->method('get')
      ->with('system.maintenance_mode')
      ->will($this->returnValue(FALSE));

    $checker = new RedirectChecker($this->getConfigFactoryStub($config), $state);

    $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
    $route->expects($this->any())
      ->method('getOption')
      ->with('_admin_route')
      ->will($this->returnValue('system.admin_config_search'));

    $request = $this->getRequestStub('index.php', 'GET',
      array(RouteObjectInterface::ROUTE_OBJECT => $route));
    $this->assertFalse($checker->canRedirect($request), 'Cannot redirect if we are requesting a admin path');

    // We are at admin path with global_admin_paths set to TRUE.
    $config['redirect.settings']['global_admin_paths'] = TRUE;
    $checker = new RedirectChecker($this->getConfigFactoryStub($config), $state);

    $request = $this->getRequestStub('index.php', 'GET',
      array(RouteObjectInterface::ROUTE_OBJECT => $route));
    $this->assertTrue($checker->canRedirect($request), 'Can redirect a admin with global_admin_paths set to TRUE');
  }

  /**
   * Gets request mock object.
   *
   * @param $script_name
   *   The result of the getScriptName() method.
   * @param $method
   *   The request method.
   * @param array $attributes
   *   Attributes to be passed into request->attributes.
   *
   * @return PHPUnit_Framework_MockObject_MockObject
   */
  protected function getRequestStub($script_name, $method, array $attributes = array()) {
    $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();
    $request->expects($this->any())
      ->method('getScriptName')
      ->will($this->returnValue($script_name));
    $request->expects($this->any())
      ->method('isMethod')
      ->with($this->anything())
      ->will($this->returnValue($method == 'GET'));
    $request->attributes = new ParameterBag($attributes);

    return $request;
  }

}
