<?php
$app->get('/plugins/sonarrthrottling/settings', function ($request, $response, $args) {
	$sonarrThrottlingPlugin = new sonarrThrottlingPlugin();
	if ($sonarrThrottlingPlugin->checkRoute($request)) {
		if ($sonarrThrottlingPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $sonarrThrottlingPlugin->_sonarrThrottlingPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/sonarrthrottling/launch', function ($request, $response, $args) {
	$sonarrThrottlingPlugin = new sonarrThrottlingPlugin();
	if ($sonarrThrottlingPlugin->checkRoute($request)) {
		if ($sonarrThrottlingPlugin->qualifyRequest($sonarrThrottlingPlugin->config['SONARRTHROTTLING-pluginAuth'], true)) {
			$sonarrThrottlingPlugin->_sonarrThrottlingPluginLaunch();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/sonarrthrottling/webhooks/overseerr', function ($request, $response, $args) {
	$sonarrThrottlingPlugin = new sonarrThrottlingPlugin();
		$Headers = getallheaders();
		// Allow 'Authorization' header to be used in this context, to enable compatibility with Overseerr Webhooks.
		if ($Headers['Authorization'] == $sonarrThrottlingPlugin->config['SONARRTHROTTLING-ApiToken'] || $sonarrThrottlingPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $sonarrThrottlingPlugin->OverseerrWebhook(file_get_contents('php://input'));
		}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/sonarrthrottling/webhooks/tautulli', function ($request, $response, $args) {
	$sonarrThrottlingPlugin = new sonarrThrottlingPlugin();
		$Headers = getallheaders();
		// Allow 'Authorization' header to be used in this context, to be consistent with the overseerr webhook.
		if ($Headers['Authorization'] == $sonarrThrottlingPlugin->config['SONARRTHROTTLING-ApiToken'] || $sonarrThrottlingPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $sonarrThrottlingPlugin->TautulliWebhook(file_get_contents('php://input'));
		}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/sonarrthrottling/throttled', function ($request, $response, $args) {
	$sonarrThrottlingPlugin = new sonarrThrottlingPlugin();
	if ($sonarrThrottlingPlugin->checkRoute($request)) {
		if ($sonarrThrottlingPlugin->qualifyRequest($sonarrThrottlingPlugin->config['SONARRTHROTTLING-pluginAuth'], true)) {
			$GLOBALS['api']['response']['data'] = $sonarrThrottlingPlugin->sonarrThrottlingPluginGetSonarrThrottled();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});