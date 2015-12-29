<?php

namespace TMCms\AdminTMCms\Modules\Settings\Entity;

use TMCms\AdminTMCms\Orm\EntityRepository;

/**
 * Class CustomSettingOptionRepository
 *
 * @method setWhereSettingId(int $id)
 */
class CustomSettingOptionRepository extends EntityRepository {
    protected $db_table = 'm_settings_options';
}