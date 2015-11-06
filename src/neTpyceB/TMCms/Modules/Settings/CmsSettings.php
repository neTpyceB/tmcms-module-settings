<?php

namespace neTpyceB\TMCms\Modules\Settings;

use neTpyceB\TMCms\Admin\Messages;
use neTpyceB\TMCms\HTML\BreadCrumbs;
use neTpyceB\TMCms\HTML\Cms\CmsFormHelper;
use neTpyceB\TMCms\HTML\Cms\CmsTable;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnData;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnDelete;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnEdit;
use neTpyceB\TMCms\HTML\Cms\Columns;
use neTpyceB\TMCms\Log\App;
use neTpyceB\TMCms\Modules\Settings\Entity\CustomSetting;
use neTpyceB\TMCms\Modules\Settings\Entity\CustomSettingRepository;

defined('INC') or exit;

class CmsSettings
{
    public function _default()
    {
        $breadcrumbs = BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb(__('All settings'))
        ;

        $settings = new CustomSettingRepository();

        $table = CmsTable::getInstance()
            ->addData($settings)
            ->addColumn(ColumnData::getInstance('module')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnData::getInstance('key')
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
        $form_array = [
            'data' => $data,
            'action' => '?p=' . P . '&do=_add',
            'button' => 'Add',
            'fields' => [
                'module',
                'key'
            ]
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
}