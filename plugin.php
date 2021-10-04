<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['sonarrThrottling'] = array( // Plugin Name
	'name' => 'Sonarr Throttling', // Plugin Name
	'author' => 'TehMuffinMoo', // Who wrote the plugin
	'category' => 'Library Management', // One to Two Word Description
	'link' => 'https://github.com/TehMuffinMoo/SonarrThrottling', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'SONARTHROTTLING', // html element id prefix (All Uppercase)
	'configPrefix' => 'SONARTHROTTLING', // config file prefix for array items without the hypen (All Uppercase)
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/sonarrThrottling/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/sonarrthrottling/settings', // api route for settings page (All Lowercase)
	'homepage' => false // Is plugin for use on homepage? true or false
);

class sonarrThrottlingPlugin extends Organizr
{
	public function _pluginGetSettings()
	{
		$this->setGroupOptionsVariable();
		return array(
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'SONARRTHROTTLING-pluginAuth'),
			),
			'Sonarr' => array(
				$this->settingsOption('multiple-url', 'sonarrURL'),
				$this->settingsOption('multiple-token', 'sonarrToken'),
				$this->settingsOption('disable-cert-check', 'sonarrDisableCertCheck'),
				$this->settingsOption('use-custom-certificate', 'sonarrUseCustomCertificate'),
				$this->settingsOption('test', 'sonarr'),
			),
			'Tautulli' => array(
				$this->settingsOption('multiple-url', 'tautulliURL'),
				$this->settingsOption('multiple-api-key', 'tautulliApikey'),
				$this->settingsOption('disable-cert-check', 'tautulliDisableCertCheck'),
				$this->settingsOption('use-custom-certificate', 'tautulliUseCustomCertificate'),
				$this->settingsOption('test', 'tautulli'),
			)
		);
	}
}