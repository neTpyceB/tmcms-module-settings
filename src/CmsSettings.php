<?php

namespace TMCms\Modules\Settings;

use TMCms\Admin\Messages;
use TMCms\DB\SQL;
use TMCms\HTML\BreadCrumbs;
use TMCms\HTML\Cms\CmsFormHelper;
use TMCms\HTML\Cms\CmsTable;
use TMCms\HTML\Cms\Column\ColumnData;
use TMCms\HTML\Cms\Column\ColumnDelete;
use TMCms\HTML\Cms\Column\ColumnEdit;
use TMCms\HTML\Cms\Columns;
use TMCms\Log\App;
use TMCms\Modules\ModuleManager;
use TMCms\Modules\Settings\Entity\CustomSetting;
use TMCms\Modules\Settings\Entity\CustomSettingOption;
use TMCms\Modules\Settings\Entity\CustomSettingOptionRepository;
use TMCms\Modules\Settings\Entity\CustomSettingRepository;

defined('INC') or exit;

class CmsSettings
{
    public function _default()
    {
        $breadcrumbs = BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb(__('All settings'))
        ;

        $options = new CustomSettingOptionRepository();

        $settings = new CustomSettingRepository();
        $settings->addOrderByField('id');
        $settings->addSimpleSelectFields(['id', 'module', 'key', 'input_type']);
        $settings->addSelectCountFromPairedObject($options, 'options', 'setting_id');

        $table = CmsTable::getInstance()
            ->addData($settings)
            ->addColumn(ColumnData::getInstance('module')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnData::getInstance('key')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnData::getInstance('input_type')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnData::getInstance('options')
                ->width('1%')
                ->align('right')
                ->href('?p='. P .'&do=setting_options&id={%id%}')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnEdit::getInstance('edit')
                ->href('?p=' . P . '&do=edit&id={%id%}')
                ->width('1%')
                ->value(__('Edit'))
            )
            ->addColumn(ColumnDelete::getInstance('delete')
                ->href('?p=' . P . '&do=_delete&id={%id%}')
            )
            ->setCallbackFunction(function($data) {
                foreach ($data as & $v) {
                    if ($v['input_type'] != 'select') {
                        $v['options'] = ' ';
                    }
                }

                return $data;
            })
        ;

        $columns = Columns::getInstance()
            ->add($breadcrumbs)
            ->add('<a class="btn btn-success" href="?p=' . P . '&do=add">Add Custom Setting</a>', ['align' => 'right'])
        ;

        echo $columns;
        echo $table;
    }

    public function __settings_form($data = NULL)
    {
        /** @var CustomSetting $data */
        $form_array = [
            'data' => $data,
            'action' => '?p=' . P . '&do=_add',
            'button' => 'Add',
            'fields' => [
                'module' => [
                    'type' => 'datalist',
                    'options' => ModuleManager::getListOfCustomModuleNames()
                ],
                'key' => [
                    'required' => true
                ],
                'input_type' => [
                    'options' => SQL::getEnumPairs(ModuleSettings::$tables['settings'], 'input_type')
                ],
                'input_options' => [
                    'type' => 'checkbox_list',
                    'options' => [
                        'editor_wysiwyg' => 'Wysiwyg',
                        'editor_files' => 'Filemanager',
                        'editor_pages' => 'Pages',
                        'require' => 'Required',
                        'is_digit' => 'Digit',
                        'alphanum' => 'Alphanumeric',
                        'url' => 'URL',
                        'email' => 'email',
                    ]
                ]
            ],
        ];

        return CmsFormHelper::outputForm(ModuleSettings::$tables['settings'],
            $form_array
        );
    }

    public function add()
    {
        echo BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb('Add Setting')
        ;

        echo self::__settings_form();
    }

    public function edit()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $setting = new CustomSetting($id);

        echo BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P), '?p='. P)
            ->addCrumb('Edit Setting')
            ->addCrumb($setting->getKey())
        ;

        echo self::__settings_form($setting)
            ->setAction('?p=' . P . '&do=_edit&id=' . $id)
            ->setSubmitButton('Update');
    }

    public function _add()
    {
        if (!isset($_POST['input_options'])) {
            $_POST['input_options'] = [];
        }

        $setting = new CustomSetting();
        $setting->loadDataFromArray($_POST);
        $setting->save();

        App::add('Custom Setting "' . $setting->getKey() . '" added');

        Messages::sendMessage('Setting added');

        go('?p='. P .'&highlight='. $setting->getId());
    }

    public function _edit()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        if (!isset($_POST['input_options'])) {
            $_POST['input_options'] = [];
        }

        $setting = new CustomSetting($id);
        $setting->loadDataFromArray($_POST);
        $setting->save();

        App::add('Custom Setting "' . $setting->getKey() . '" edited');

        Messages::sendMessage('Setting updated');

        go('?p='. P .'&highlight='. $setting->getId());
    }

    public function _delete()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $setting = new CustomSetting($id);
        $setting->deleteObject();

        App::add('Custom Setting "' . $setting->getKey() . '" deleted');

        Messages::sendMessage('Setting deleted');

        back();
    }

    public function setting_options()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $setting = new CustomSetting($id);

        $breadcrumbs = BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb($setting->getModule())
            ->addCrumb($setting->getKey())
        ;

        $options = new CustomSettingOptionRepository();
        $options->setWhereSettingId($id);

        $table = CmsTable::getInstance()
            ->addData($options)
            ->addColumn(ColumnData::getInstance('option_name')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnEdit::getInstance('edit')
                ->href('?p=' . P . '&do=setting_options_edit&id={%id%}')
                ->width('1%')
                ->value(__('Edit'))
            )
            ->addColumn(ColumnDelete::getInstance('delete')
                ->href('?p=' . P . '&do=_setting_options_delete&id={%id%}')
            )
        ;

        $columns = Columns::getInstance()
            ->add($breadcrumbs)
            ->add('<a class="btn btn-success" href="?p=' . P . '&do=setting_options_add&id='. $id .'">Add Setting Option</a>', ['align' => 'right'])
        ;

        echo $columns;
        echo $table;
    }

    public function __setting_options_form($data = NULL)
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        /** @var CustomSetting Option$data */
        $form_array = [
            'data' => $data,
            'action' => '?p=' . P . '&do=_setting_options_add',
            'button' => 'Add',
            'fields' => [
                'option_name',
                'setting_id' => [
                    'type' => 'hidden',
                    'value' => $id
                ]
            ],
        ];

        return CmsFormHelper::outputForm(ModuleSettings::$tables['options'],
            $form_array
        );
    }

    public function setting_options_add()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $setting = new CustomSetting($id);

        echo BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb($setting->getModule())
            ->addCrumb($setting->getKey())
            ->addCrumb('Add Option')
        ;

        echo self::__setting_options_form();
    }

    public function setting_options_edit()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $option = new CustomSettingOption($id);

        $setting = new CustomSetting($option->getSettingId());

        echo BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb($setting->getModule())
            ->addCrumb($setting->getKey())
            ->addCrumb('Edit Option')
            ->addCrumb($option->getOptionName())
        ;

        echo self::__setting_options_form($option)
            ->setAction('?p=' . P . '&do=_setting_options_edit&id=' . $id)
            ->setSubmitButton('Update');
    }

    public function _setting_options_add()
    {
        $option = new CustomSettingOption();
        $option->loadDataFromArray($_POST);
        $option->save();

        $setting = new CustomSetting($option->getSettingId());

        App::add('Custom Setting Option "' . $option->getOptionName() . '" added');

        Messages::sendMessage('Setting Option added');

        go('?p='. P .'&do=setting_options&id='. $setting->getId() .'&highlight='. $option->getId());
    }

    public function _setting_options_edit()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $option = new CustomSettingOption($id);
        $option->loadDataFromArray($_POST);
        $option->save();

        $setting = new CustomSetting($option->getSettingId());

        App::add('Custom Setting Option "' . $option->getOptionName() . '" edited');

        Messages::sendMessage('Setting Option updated');

        go('?p='. P .'&do=setting_options&id='. $setting->getId() .'&highlight='. $id);
    }

    public function _setting_options_delete()
    {
        $id = abs((int)$_GET['id']);
        if (!$id) return;

        $option = new CustomSettingOption($id);
        $option->deleteObject();

        App::add('Custom Setting Option "' . $option->getOptionName() . '" deleted');

        Messages::sendMessage('Setting Option deleted');

        back();
    }
}