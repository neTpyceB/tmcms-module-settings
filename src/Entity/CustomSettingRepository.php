<?php

namespace TMCms\Modules\Settings\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class CustomSettingRepository
 *
 * @method setWhereModule(string $module)
 */
class CustomSettingRepository extends EntityRepository {
    protected $db_table = 'm_settings';
}