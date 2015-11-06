<?php

namespace neTpyceB\TMCms\Modules\Settings;

use neTpyceB\TMCms\HTML\Cms\CmsFormHelper;
use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

class ModuleSettings implements IModule {
	use singletonInstanceTrait;

	public static $tables = [
		'settings' => 'm_settings'
	];

	public static function requireTableForExternalModule($module) {
		$data = new CustomSettingRepository();
		$data->setWhereModule($module);
		$data = $data->getPairs('value', 'key');

		// TODO different field types

		if (!$data) {
			return false;
		}

		$form_array = [
				'data' => $data,
				'action' => '?p=' . P . '&do=_settings',
				'button' => __('Update'),
				'fields' => array_keys($data)
		];

		return CmsFormHelper::outputForm(self::$tables['settings'],
				$form_array
		)->enableAjax();
	}

	public static function requireUpdateModuleSettings($module)
	{
		if (!$_POST) return;

		// Delete all settings for Module
		$settings = new CustomSettingRepository;
		$settings->setWhereModule($module);
		$settings->deleteObjectCollection();

		// Update (create) settings
		foreach ($_POST as $k => $v) {
			$setting = new CustomSetting();

			$setting->setModule($module);
			$setting->setKey($k);
			$setting->setValue($v);

			$setting->save();
		}

		if (IS_AJAX_REQUEST) {
			die('1');
		}

		back();
	}

	public static function getCustomSetting($module, $key) {

		$setting = CustomSettingRepository::findOneEntityByCriteria([
				'module' => $module,
				'key' => $key
		]);

		/** @var CustomSetting $setting */
		if ($setting) {
			return $setting->getValue();
		}

		return NULL;
	}
}