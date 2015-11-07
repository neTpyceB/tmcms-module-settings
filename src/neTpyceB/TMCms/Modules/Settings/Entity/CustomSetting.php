<?php

namespace neTpyceB\TMCms\Modules\Settings\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class CustomSetting
 *
 * @method setKey(string $key)
 * @method setModule(string $module)
 * @method setInputOptions(array $options)
 * @method setInputType(string $type)
 * @method setValue(string $value)
 *
 * @method string getKey()
 * @method string getModule()
 * @method string getInputOptions()
 * @method string getInputType()
 * @method string getValue()
 */
class CustomSetting extends Entity {
    protected $db_table = 'm_settings';

    protected function beforeSave() {
        $options = $this->getInputOptions();
        if (is_array($options)) {
            $this->setInputOptions(json_encode($options));
        }
    }

    protected function afterLoad() {
        $options = $this->getInputOptions();
        if (is_string($options)) {
            $this->setInputOptions(json_decode($options, JSON_OBJECT_AS_ARRAY));
        }
    }

    protected function beforeDelete() {
        // Delete options
        $options = new CustomSettingOptionRepository();
        $options->setWhereSettingId($this->getId());
        $options->deleteObjectCollection();

        return $this;
    }
}