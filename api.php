<?php
$app->get('/plugins/sonarrthrottling/settings', function ($request, $response, $args) {
	$sonarrThrottlingPlugin = new sonarrThrottlingPlugin();
	if ($sonarrThrottlingPlugin->checkRoute($request)) {
		if ($sonarrThrottlingPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $sonarrThrottlingPlugin->_pluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});