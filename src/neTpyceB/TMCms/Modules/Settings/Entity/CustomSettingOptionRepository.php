<?php

namespace neTpyceB\TMCms\Modules\Settings\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class CustomSettingOptionRepository
 */
class CustomSettingOptionRepository extends EntityRepository {
    protected $db_table = 'm_settings_options';
}