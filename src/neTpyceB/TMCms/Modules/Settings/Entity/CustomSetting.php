<?php

namespace neTpyceB\TMCms\Modules\Settings\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class CustomSetting
 *
 * @method setKey(string $key)
 * @method setModule(string $module)
 * @method setValue(string $value)
 *
 * @method string getKey()
 * @method string getModule()
 * @method string getValue()
 */
class CustomSetting extends Entity {
    protected $db_table = 'm_settings';
}