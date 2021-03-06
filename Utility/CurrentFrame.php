<?php
/**
 * CurrentFrame Utility
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Container', 'Containers.Model');

/**
 * CurrentFrame Utility
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\NetCommons\Utility
 */
class CurrentFrame {

/**
 * 管理プラグイン以外でFrameチェックからスキップするプラグインリスト
 *
 * @var mixed
 */
	public static $skipFramePlugins = array(
		Current::PLUGIN_PAGES,
		Current::PLUGIN_USERS,
		Current::PLUGIN_WYSIWYG,
	);

/**
 * setup current data
 *
 * @return void
 */
	public function initialize() {
		if (isset(Current::$current['Frame'])) {
			unset(Current::$current['Frame']);
		}
		if (isset(Current::$current['Block'])) {
			unset(Current::$current['Block']);
		}
		if (isset(Current::$current['BlockRolePermission'])) {
			unset(Current::$current['BlockRolePermission']);
		}
		if (isset(Current::$m17n['Frame'])) {
			unset(Current::$m17n['Frame']);
		}
		if (isset(Current::$m17n['Block'])) {
			unset(Current::$m17n['Block']);
		}

		if (!in_array(Current::$request->params['plugin'], self::$skipFramePlugins, true)) {
			$this->setFrame();
			$this->setBlock();
			$this->setM17n();
		}

		(new CurrentPage())->initialize();

		$this->setBlockRolePermissions();
	}

/**
 * Set Frame
 *
 * @return void
 */
	public function setFrame() {
		if (! Hash::get(Current::$request->params, 'requested') &&
					Hash::get(Current::$request->data, 'Frame.id')) {
			$frameId = Current::$request->data['Frame']['id'];
		} elseif (Hash::get(Current::$request->params, '?.frame_id')) {
			$frameId = Hash::get(Current::$request->params, '?.frame_id');
		} elseif (isset(Current::$request->query['frame_id'])) {
			$frameId = Current::$request->query['frame_id'];
		}

		$this->Frame = ClassRegistry::init('Frames.Frame');
		$this->Box = ClassRegistry::init('Boxes.Box');
		$this->Block = ClassRegistry::init('Blocks.Block');

		if (isset($frameId)) {
			$result = $this->Frame->findById($frameId);
			Current::$current = Hash::merge(Current::$current, $result);
		}

		//ブロック設定の新規の場合の処理
		if (Current::$layout === 'NetCommons.setting' &&
				Hash::get(Current::$request->params, 'action') === 'add') {
			Current::$current['Block'] = $this->Block->create()['Block'];
		}

		$this->setPageByBox();
	}

/**
 * Set PageByBox
 *
 * @return void
 */
	public function setPageByBox() {
		if (isset(Current::$current['Box']['id'])) {
			$boxId = Current::$current['Box']['id'];
		} elseif (isset(Current::$request->data['Frame']) &&
					isset(Current::$request->data['Frame']['box_id'])) {
			$boxId = Current::$request->data['Frame']['box_id'];
		} elseif (isset(Current::$request->data['Box']) &&
					isset(Current::$request->data['Box']['id'])) {
			$boxId = Current::$request->data['Box']['id'];
		} else {
			return;
		}

		$result = $this->Box->find('first', array(
			'conditions' => array(
				'Box.id' => $boxId,
			),
		));
		if (! $result) {
			return;
		}

		if ($result['Container']['type'] === Container::TYPE_MAIN) {
			Current::$current['Page'] = $result['Page'][0];
		}

		if (! isset(Current::$current['Room'])) {
			Current::$current['Room'] = $result['Room'];
		}

		Current::$current['Container'] = $result['Container'];
	}

/**
 * Set Block
 *
 * @param int $blockId Blocks.id
 * @return void
 */
	public function setBlock($blockId = null) {
		$this->Block = ClassRegistry::init('Blocks.Block');

		if (! Hash::get(Current::$request->params, 'requested') &&
					Hash::get(Current::$request->data, 'Block.id')) {
			$blockId = Current::$request->data['Block']['id'];
		} elseif (isset($blockId)) {
			//何もしない
		} elseif (isset(Current::$request->params['block_id'])) {
			$blockId = Current::$request->params['block_id'];
		} else {
			return;
		}

		$result = $this->Block->find('first', array(
			'recursive' => 0,
			'conditions' => array(
				'Block.id' => $blockId,
			),
		));
		if ($result) {
			Current::$current = Hash::merge(Current::$current, $result);
			return;
		}

		if (isset(Current::$current['Frame']['block_id'])) {
			$result = $this->Block->find('first', array(
				'recursive' => 0,
				'conditions' => array(
					'Block.id' => Current::$current['Frame']['block_id'],
				),
			));
			if ($result) {
				Current::$current = Hash::merge(Current::$current, $result);
			}
		}
	}

/**
 * Set BlockRolePermissions
 *
 * @return void
 */
	public function setBlockRolePermissions() {
		$this->BlockRolePermission = ClassRegistry::init('Blocks.BlockRolePermission');

		if (isset(Current::$current['BlockRolePermission'])) {
			return;
		}

		if (isset(Current::$current['RolesRoom']) && isset(Current::$current['Block']['key'])) {
			$result = $this->BlockRolePermission->find('all', array(
				'recursive' => -1,
				'conditions' => array(
					'roles_room_id' => Current::$current['RolesRoom']['id'],
					'block_key' => Current::$current['Block']['key'],
				)
			));
			if ($result) {
				Current::$current['BlockRolePermission'] = Hash::combine(
					$result, '{n}.BlockRolePermission.permission', '{n}.BlockRolePermission'
				);
			}
		}

		$permission = array();
		if (isset(Current::$current['DefaultRolePermission'])) {
			$permission = Hash::merge($permission, Current::$current['DefaultRolePermission']);
		}
		if (isset(Current::$current['RoomRolePermission'])) {
			$permission = Hash::merge($permission, Current::$current['RoomRolePermission']);
		}
		if (isset(Current::$current['BlockRolePermission'])) {
			$permission = Hash::merge($permission, Current::$current['BlockRolePermission']);
		}

		Current::$current['Permission'] = $permission;
	}

/**
 * 多言語化のデータ取得
 *
 * @return void
 */
	public function setM17n() {
		if (isset(Current::$current['Frame'])) {
			$this->Frame = ClassRegistry::init('Frames.Frame');
			Current::$m17n['Frame'] = $this->Frame->find('all', array(
				'recursive' => -1,
				'conditions' => array(
					'key' => Current::$current['Frame']['key']
				),
			));
		}

		if (isset(Current::$current['Block'])) {
			$this->Block = ClassRegistry::init('Blocks.Block');
			Current::$m17n['Block'] = $this->Block->find('all', array(
				'recursive' => -1,
				'conditions' => array(
					'key' => Current::$current['Block']['key']
				),
			));
		}
	}

}
