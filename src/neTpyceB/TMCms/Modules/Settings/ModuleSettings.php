<?php

namespace neTpyceB\TMCms\Modules\Settings;

use neTpyceB\TMCms\HTML\Cms\CmsFormHelper;
use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Modules\ModuleManager;
use neTpyceB\TMCms\Modules\Settings\Entity\CustomSetting;
use neTpyceB\TMCms\Modules\Settings\Entity\CustomSettingOptionRepository;
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

	public static function requireTableForExternalModule($module = P, $fields = []) {
		$data = new CustomSettingRepository();
		$data->setWhereModule($module);
		$data->getAsArrayOfObjectData();

		foreach ($data->getAsArrayOfObjectData() as $key => $field) {
			if (!isset($field['module'])) {
				$field['module'] = P;
			}

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

			// Input Type
			if ($field['input_type'] == 'select') {
				$field['options'] = ModuleSettings::getSelectTypeSettingOption(P, $field['key']);
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
		)
			->enableAjax()
		;
	}

	public static function requireUpdateModuleSettings($module = P)
	{
		if (!$_POST) return;

		$settings = new CustomSettingRepository();
		$settings->setWhereModule(P);
		$to_unset = $settings->getPairs('key');

		// Update (create) settings
		foreach ($_POST as $k => $v) {
			// Check existing
			/** @var CustomSetting $setting */
			$setting = CustomSettingRepository::findOneEntityByCriteria([
				'module' => $module,
				'key' => $k
			]);

			if (!$setting) {
				$setting = new CustomSetting();
				$setting->setModule($module);
				$setting->setKey($k);
			}

			// Set 1 for checkboxes
			if ($setting->getInputType() == 'checkbox' && !$v) {
				$v = 1;
			}

			$setting->setValue($v);
			$setting->save();

			unset($to_unset[$setting->getId()]);
		}

		// Set 0 for unset checkboxes
		foreach ($to_unset as $unset_id => $unset_key) {
			$setting = new CustomSetting($unset_id);
			$setting->setValue(0);
			$setting->save();
		}


		if (IS_AJAX_REQUEST) {
			die('1');
		}

		back();
	}

	/**
	 * Get Setting object
	 * @param string $module
	 * @param string $key
	 * @return CustomSetting
	 */
	public static function getCustomSetting($module, $key) {

		$setting = CustomSettingRepository::findOneEntityByCriteria([
				'module' => $module,
				'key' => $key
		]);

		return $setting;
	}

	/**
	 * Get Value of Setting
	 * @param string $module
	 * @param string $key
	 * @return string
	 */
	public static function getCustomSettingValue($module, $key) {
		$setting = self::getCustomSetting($module, $key);
		if ($setting) {
			return $setting->getValue();
		}

		return NULL;
	}

	/**
	 * @param string $module
	 * @param string $key
	 * @return array
	 */
	private static function getSelectTypeSettingOption($module, $key)
	{
		$setting = self::getCustomSetting($module, $key);
		if (!$setting) {
			return [];
		}

		$options = new CustomSettingOptionRepository;
		$options->setWhereSettingId($setting->getId());
		return $options->getPairs('option_name');
	}

	// Get Setting pairs
	public static function getSettingsPairs($module = null) {
		if (!$module) {
			return [];
		}

		$fields = new CustomSettingRepository();
		$fields->setWhereModule($module);

		return $fields->getPairs('value', 'key');
	}
}