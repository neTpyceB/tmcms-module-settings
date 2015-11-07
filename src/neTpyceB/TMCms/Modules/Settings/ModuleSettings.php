<?php

namespace neTpyceB\TMCms\Modules\Settings;

use neTpyceB\TMCms\HTML\Cms\CmsFormHelper;
use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Modules\Settings\Entity\CustomSetting;
use neTpyceB\TMCms\Modules\Settings\Entity\CustomSettingRepository;
use neTpyceB\TMCms\Strings\Converter;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

class ModuleSettings implements IModule {
	use singletonInstanceTrait;

	public static $tables = [
		'settings' => 'm_settings',
		'options' => 'm_settings_options'
	];

	public static function requireTableForExternalModule($module) {
		$data = new CustomSettingRepository();
		$data->setWhereModule($module);
		$data->getAsArrayOfObjectData();

		$fields = [];

		foreach ($data->getAsArrayOfObjectData() as $field) {
			$field['title'] = Converter::symb2Ttl($field['key']);
			$field['type'] = $field['input_type'];

			$options_array = [];
			if ($field['input_options'] && is_string($field['input_options'])) {
				$options_array = json_decode($field['input_options'], JSON_OBJECT_AS_ARRAY);
			}

			// Validators and editors
			if (isset($options_array['editor_wysiwyg'])) {
				$field['edit'] = 'wysiwyg';
			}
			if (isset($options_array['editor_files'])) {
				$field['edit'] = 'files';
			}
			if (isset($options_array['editor_pages'])) {
				$field['edit'] = 'pages';
			}
			if (isset($options_array['require'])) {
				$field['required'] = true;
				$field['validate']['require'] = true;
			}
			if (isset($options_array['is_digit'])) {
				$field['validate']['is_digit'] = true;
			}
			if (isset($options_array['alphanum'])) {
				$field['validate']['alphanum'] = true;
			}
			if (isset($options_array['url'])) {
				$field['validate']['url'] = true;
			}
			if (isset($options_array['email'])) {
				$field['validate']['email'] = true;
			}

			$fields[$field['key']] = $field;
		}

		if (!$fields) {
			return false;
		}

		$form_array = [
				'action' => '?p=' . P . '&do=_settings',
				'button' => __('Update'),
				'fields' => $fields
		];

		return CmsFormHelper::outputForm(self::$tables['settings'],
				$form_array
		)->enableAjax();
	}

	public static function requireUpdateModuleSettings($module)
	{
		if (!$_POST) return;

		// Update (create) settings
		foreach ($_POST as $k => $v) {
			// Check existing
			$setting = CustomSettingRepository::findOneEntityByCriteria([
				'module' => $module,
				'key' => $k
			]);

			if (!$setting) {
				$setting = new CustomSetting();
			}

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