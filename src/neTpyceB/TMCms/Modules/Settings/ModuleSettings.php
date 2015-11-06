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
}