<?php

namespace TMCms\Modules\Settings;

use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\HTML\Cms\CmsFormHelper;
use TMCms\Modules\IModule;
use TMCms\Modules\ModuleManager;
use TMCms\Modules\Settings\Entity\CustomSetting;
use TMCms\Modules\Settings\Entity\CustomSettingOptionRepository;
use TMCms\Modules\Settings\Entity\CustomSettingRepository;
use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

class ModuleSettings implements IModule {
	use singletonInstanceTrait;

	public static $tables = [
		'settings' => 'm_settings',
		'options' => 'm_settings_options'
	];

	private static $cached_settings = [];

	/**
	 * @param string $module
	 * @param array $fields
	 * @return string
	 */
	public static function requireTableForExternalModule($module = P, $fields = []) {
		$data = new CustomSettingRepository();
		$data->setWhereModule($module);
		$data->getAsArrayOfObjectData();

		foreach ($data->getAsArrayOfObjectData() as $key => $field) {
			// Any existing data
			if (isset($fields[$field['key']])) {
				$field = array_merge($fields[$field['key']], $field);
			}

			// Supplied data
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
		// Check cache
		if (Settings::isCacheEnabled()) {
			$cache_key = 'module_custom_settings_all';
			$cacher = Cacher::getInstance()->getDefaultCacher();

			if (!self::$cached_settings) {
				self::$cached_settings = $cacher->get($cache_key);
			}
		}

		if (!self::$cached_settings) {
			// To prevent more iterations
			self::$cached_settings['empty']['empty'] = '';

			$settings = new CustomSettingRepository;
			foreach ($settings->getAsArrayOfObjects() as $setting) { /** @var CustomSetting $setting */
				self::$cached_settings[$setting->getModule()][$setting->getKey()] = $setting;
			}
		}

		// Save cache
		if (Settings::isCacheEnabled()) {
			$cacher->set($cache_key, self::$cached_settings, 86400);
		}

		return isset(self::$cached_settings[$module][$key]) ? self::$cached_settings[$module][$key] : NULL;
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