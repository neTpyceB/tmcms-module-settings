# tmcms-module-settings
Module Settings for TMCms

Module Settings can be used for extentions of any other module. Add this code to any other CmsModule class and you will have Settings available for it, for example in CmsClients:

```php
public function settings() { 
  echo ModuleSettings::requireTableForExternalModule(P, [ 
    'is_registration_enabled' => [ 
      'type' => 'checkbox', 
    ], 
  ]); 
} 

public function _settings() { 
  ModuleSettings::requireUpdateModuleSettings(P, [ 
    'is_registration_enabled' => [ 
      'type' => 'checkbox', 
      'value' => 1, 
    ], 
  ]);
}
```

And to get setting value in code, use:

```php
$module_name = 'clients';
$setting_key = 'is_registration_enabled';
$setting_value = ModuleSettings::getCustomSettingValue($module_name, $setting_key);
```

Also you can add `'settings' => []` in menu file to have module in admin panel.
