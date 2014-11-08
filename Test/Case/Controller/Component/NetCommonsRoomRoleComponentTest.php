<?php
/**
 * RoomsController Test Case
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('ComponentCollection', 'Controller');
App::uses('NetCommonsRoomRoleComponent', 'NetCommons.Controller/Component');

/**
 * Controller for NetCommonsRoomRole component test
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\NetCommons\Test\Case\Controller
 */
class TestNetCommonsRoomRoleController extends Controller {

}

/**
 * NetCommonsRoomRole Component test case
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\NetCommons\Test\Case\Controller
 */
class NetCommonsRoomRoleComponentTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.frames.frame',
		'plugin.frames.language',
		'plugin.frames.plugin',
		'plugin.boxes.box',
		'plugin.blocks.block',
		'plugin.rooms.room',
		'plugin.rooms.roles_rooms_user',
		'plugin.roles.default_role_permission',
		'plugin.rooms.roles_room',
		'plugin.rooms.room_role_permission',
		'plugin.rooms.user',
	);

/**
 * NetCommonsRoomRole component
 *
 * @var Component NetCommonsRoomRole component
 */
	public $NetCommonsRoomRole = null;

/**
 * Controller for NetCommonsRoomRole component test
 *
 * @var Controller Controller for NetCommonsRoomRole component test
 */
	public $Controller = null;

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		//テストコントローラ読み込み
		$CakeRequest = new CakeRequest();
		$CakeResponse = new CakeResponse();
		$this->Controller = new TestNetCommonsRoomRoleController($CakeRequest, $CakeResponse);
		//コンポーネント読み込み
		$Collection = new ComponentCollection();
		$this->NetCommonsRoomRole = new NetCommonsRoomRoleComponent($Collection);
		$this->NetCommonsRoomRole->startup($this->Controller);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		unset($this->NetCommonsRoomRole);
		unset($this->Controller);
	}

/**
 * testInitialize method
 *
 * @return void
 */
	public function testInitialize() {
		$result = $this->NetCommonsRoomRole->initialize($this->Controller);
		$this->assertNull($result);

		$expected = array(
			'rolesRoomId' => 0,
			'roomRoleKey' => 'visitor',
		);
		$this->assertEquals($expected, $this->Controller->viewVars);
	}

/**
 * testSetView method
 *
 * @return void
 */
	public function testSetView() {
		CakeSession::write('Auth.User.id', 1);

		$result = $this->NetCommonsRoomRole->initialize($this->Controller);
		$this->assertNull($result);

		$result = $this->NetCommonsRoomRole->setView($this->Controller);
		$this->assertTrue($result);

		$expected = array(
			'pageEditable' => true,
			'blockEditable' => true,
			'contentReadable' => true,
			'contentCreatable' => true,
			'contentEditable' => true,
			'contentPublishable' => true,
			'rolesRoomId' => 1,
			'roomRoleKey' => 'room_administrator',
		);
		$this->assertEquals($expected, $this->Controller->viewVars);

		CakeSession::write('Auth.User.id', null);
	}

/**
 * testSetViewNoLogin method
 *
 * @return void
 */
	public function testSetViewNoLogin() {
		CakeSession::write('Auth.User.id', null);

		$result = $this->NetCommonsRoomRole->initialize($this->Controller);
		$this->assertNull($result);

		$result = $this->NetCommonsRoomRole->setView($this->Controller);
		$this->assertTrue($result);

		$expected = array(
			'pageEditable' => false,
			'blockEditable' => false,
			'contentReadable' => true,
			'contentCreatable' => false,
			'contentEditable' => false,
			'contentPublishable' => false,
			'rolesRoomId' => 0,
			'roomRoleKey' => 'visitor',
		);
		$this->assertEquals($expected, $this->Controller->viewVars);

		CakeSession::write('Auth.User.id', null);
	}

/**
 * testSetViewNotExistUser method
 *
 * @return void
 */
	public function testSetViewNotExistUser() {
		CakeSession::write('Auth.User.id', 999);

		$result = $this->NetCommonsRoomRole->initialize($this->Controller);
		$this->assertNull($result);

		$result = $this->NetCommonsRoomRole->setView($this->Controller);
		$this->assertFalse($result);

		CakeSession::write('Auth.User.id', null);
	}

/**
 * testSetViewRoomRolePermisionDataError method
 *
 * @return void
 */
	public function testSetViewRoomRolePermisionDataError() {
		//テストデータ生成
		$this->RoomRolePermission = ClassRegistry::init('Rooms.RoomRolePermission');
		$this->RoomRolePermission->updateAll(
			array('RoomRolePermission.roles_room_id' => "'2'"),
			array('RoomRolePermission.roles_room_id' => '1')
		);

		CakeSession::write('Auth.User.id', 1);

		$result = $this->NetCommonsRoomRole->initialize($this->Controller);
		$this->assertNull($result);

		$result = $this->NetCommonsRoomRole->setView($this->Controller);
		$this->assertFalse($result);

		CakeSession::write('Auth.User.id', null);
	}

}