<?php

namespace neTpyceB\TMCms\Modules\Settings\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class CustomSetting
 *
 * @method setKey(string $key)
 * @method setModule(string $module)
 * @method setInputOptions(array $options)
 * @method setValue(string $value)
 *
 * @method string getKey()
 * @method string getModule()
 * @method string getInputOptions()
 * @method string getValue()
 */
class CustomSetting extends Entity {
    protected $db_table = 'm_settings';

    protected function beforeSave() {
        $options = $this->getInputOptions();
        if ($options && is_array($options)) {
            $this->setInputOptions(json_encode($options));
        }
    }

    protected function afterLoad() {
        $options = $this->getInputOptions();
        if ($options && is_string($options)) {
            $this->setInputOptions(json_decode($options, JSON_OBJECT_AS_ARRAY));
        }
    }
}