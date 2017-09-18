<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'firewall_dynamic';
$app['version'] = '1.0.5';
$app['release'] = '1';
$app['vendor'] = 'WikiSuite';
$app['packager'] = 'eGloo';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('firewall_dynamic_app_description');
$app['tooltip'] = array(
    lang('firewall_dynamic_tooltip_group')
);
$app['powered_by'] = array(
    'vendor' => array(
        'name' => 'WikiSuite',
        'url' => 'http://wikisuite.org/',
    ),
);


/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('firewall_dynamic_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_firewall');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'clearos-framework >= 7.3.10',
    //'firewall-dynamic-extension-core',
);

$app['core_file_manifest'] = array( 
    'firewall-dynamic.cron' => array(
        'target' => '/etc/cron.d/app-firewall-dynamic',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'rule_ssh.php' => array(
        'target' => '/var/clearos/firewall_dynamic/triggers/webconfig_login/ssh.php',
        'mode' => '0750',
        'owner' => 'webconfig',
        'group' => 'webconfig',
    ),
    'ssh.xml' => array(
        'target' => '/var/clearos/firewall_dynamic/rules/ssh.xml',
        'mode' => '0644',
        'owner' => 'webconfig',
        'group' => 'webconfig',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'rule_openvpn.php' => array(
        'target' => '/var/clearos/firewall_dynamic/triggers/webconfig_login/openvpn.php',
        'mode' => '0750',
        'owner' => 'webconfig',
        'group' => 'webconfig',
    ),
    'openvpn.xml' => array(
        'target' => '/var/clearos/firewall_dynamic/rules/openvpn.xml',
        'mode' => '0644',
        'owner' => 'webconfig',
        'group' => 'webconfig',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    '10-firewall-dynamic' => array(
        'target' => '/etc/clearos/firewall.d/10-firewall-dynamic',
        'mode' => '0755',
        'config' => TRUE,
        'config_params' => 'noreplace'
    )
);
$app['core_directory_manifest'] = array(
    '/var/clearos/firewall_dynamic' => array(
        'mode' => '0755',
        'owner' => 'webconfig',
        'group' => 'webconfig',
    ),
    '/var/clearos/firewall_dynamic/triggers' => array(
        'mode' => '0755',
        'owner' => 'webconfig',
        'group' => 'webconfig',
    ),
    '/var/clearos/firewall_dynamic/triggers/webconfig_login' => array(
        'mode' => '0755',
        'owner' => 'webconfig',
        'group' => 'webconfig',
    ),
);

$app['delete_dependency'] = array(
    'firewall-dynamic-core',
    'firewall-dynamic-extension',
);
