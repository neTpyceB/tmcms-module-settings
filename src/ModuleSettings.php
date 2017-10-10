<?php

namespace TMCms\Modules\Settings;

use TMCms\Admin\Messages;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\HTML\Cms\CmsFormHelper;
use TMCms\Log\App;
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
        'options'  => 'm_settings_options'
    ];

    private static $cached_settings = [];

    /**
     * @param string $module
     * @param array  $predefined_fields
     * @return string
     */
    public static function requireTableForExternalModule($module = P, $predefined_fields = [])
    {
        $data = new CustomSettingRepository();
        $data->setWhereModule($module);
        $data->getAsArrayOfObjectData();

        foreach ($data->getAsArrayOfObjectData() as $key => $field) {
            // Any existing data
            if (isset($predefined_fields[$field['key']])) {
                $field = array_merge($predefined_fields[$field['key']], $field);
            }

            // Supplied data
            if (!isset($field['module'])) {
                $field['module'] = P;
            }

            $field['title'] = Converter::charsToNormalTitle($field['key']);
            if (!isset($field['type']) || !$field['type']) {
                $field['type'] = $field['input_type'];
            }
            $field['input_type'] = $field['type'];

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
                $field['path'] = DIR_PUBLIC_URL;
            }
            if (isset($options_array['editor_pages'])) {
                $field['edit'] = 'pages';
            }
            if (isset($options_array['editor_map'])) {
                $field['edit'] = 'map';
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

            $predefined_fields[$field['key']] = $field;
        }

        if (!$predefined_fields) {
            return false;
        }

        $form_array = [
            'action' => '?p=' . P . '&do=_settings',
            'button' => __('Update'),
            'fields' => $predefined_fields
        ];

        return CmsFormHelper::outputForm(self::$tables['settings'],
            $form_array
        )
            ->enableAjax();
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

    /**
     * Get Setting object
     * @param string $module
     * @param string $key
     * @return CustomSetting
     */
    public static function getCustomSetting($module, $key)
    {
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
            foreach ($settings->getAsArrayOfObjects() as $setting) {
                /** @var CustomSetting $setting */
                self::$cached_settings[$setting->getModule()][$setting->getKey()] = $setting;
            }
        }

        // Save cache
        if (Settings::isCacheEnabled()) {
            $cacher->set($cache_key, self::$cached_settings, 86400);
        }

		if(!isset(self::$cached_settings[$module][$key])){
			$item = new CustomSetting();
			$item->setModule($module)->setKey($key)->setInputType('text')->save();
		}

        return isset(self::$cached_settings[$module][$key]) ? self::$cached_settings[$module][$key] : NULL;
    }

    /**
     * @param string $module
     * @param array $predefined_fields use it to save checkboxes, because they all have value 0
     */
    public static function requireUpdateModuleSettings($module = P, $predefined_fields = [])
    {
        $settings = new CustomSettingRepository();
        $settings->setWhereModule(P);
        $to_unset = $settings->getPairs('key');

        // Update (create) settings
        foreach ($_POST as $k => $v) {
            // Check existing
            /** @var CustomSetting $setting */
            $setting = CustomSettingRepository::findOneEntityByCriteria([
                'module' => $module,
                'key'    => $k
            ]);

            if (!$setting) {
                $setting = new CustomSetting();
                $setting->setModule($module);
                $setting->setKey($k);
            }

            // Set 1 for checkboxes
            if ((isset($predefined_fields[$k]) && $predefined_fields[$k]['type'] == 'checkbox' && !$v) || ($setting->getInputType() == 'checkbox' && !$v)) {
                $v = 1;
                $setting->setInputType('checkbox');
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

        App::add('Setting in module "' . P . '" updated');
        Messages::sendGreenAlert('Settings updated');

        if (IS_AJAX_REQUEST) {
            die('1');
        }

        back();
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

    // Get Setting pairs

    public static function getSettingsPairs($module = NULL) {
        $fields = new CustomSettingRepository();

        if ($module) {
            $fields->setWhereModule($module);
        }

        return $fields->getPairs('value', 'key');
    }
}