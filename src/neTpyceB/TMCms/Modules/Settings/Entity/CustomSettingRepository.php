<?php

namespace neTpyceB\TMCms\Modules\Settings;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class CustomSettingRepository
 *
 * @method setWhereModule(string $module)
 */
class CustomSettingRepository extends EntityRepository {
    protected $db_table = 'm_settings';
}