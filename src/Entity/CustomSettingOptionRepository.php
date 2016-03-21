<?php

namespace TMCms\Modules\Settings\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class CustomSettingOptionRepository
 *
 * @method setWhereSettingId(int $id)
 */
class CustomSettingOptionRepository extends EntityRepository {
    protected $db_table = 'm_settings_options';
    protected $table_structure = [
        'fields' => [
            'setting_id' => [
                'type' => 'index',
            ],
            'option_name' => [
                'type' => 'varchar',
            ],
        ],
    ];
}