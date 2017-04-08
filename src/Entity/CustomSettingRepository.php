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
    protected $table_structure = [
        'fields' => [
            'module'        => [
                'type' => 'varchar',
            ],
            'key'           => [
                'type' => 'varchar',
            ],
            'value'         => [
                'type' => 'varchar',
            ],
            'input_type'    => [
                'type' => 'enum',
                'options' => [
                    'text',
                    'textarea',
                    'checkbox',
                    'select',
                ],
            ],
            'input_options' => [
                'type' => 'varchar',
            ],
            'hint'          => [
                'type' => 'varchar',
            ],
        ],
    ];
}