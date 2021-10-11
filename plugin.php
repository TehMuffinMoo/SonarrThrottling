<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['sonarrThrottling'] = array( // Plugin Name
	'name' => 'Sonarr Throttling', // Plugin Name
	'author' => 'TehMuffinMoo', // Who wrote the plugin
	'category' => 'Library Management', // One to Two Word Description
	'link' => 'https://github.com/TehMuffinMoo/SonarrThrottling', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'SONARRTHROTTLING', // html element id prefix (All Uppercase)
	'configPrefix' => 'SONARRTHROTTLING', // config file prefix for array items without the hypen (All Uppercase)
	'version' => '1.0.1', // SemVer of plugin
	'image' => 'api/plugins/sonarrThrottling/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/sonarrthrottling/settings', // api route for settings page (All Lowercase)
	'homepage' => false // Is plugin for use on homepage? true or false
);

class sonarrThrottlingPlugin extends Organizr
{
	public function _sonarrThrottlingPluginGetSettings()
	{
		$SonarrServers = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		if (!empty($SonarrServers)) {
			$list = array();
			foreach($SonarrServers as $key => $SonarrServer){
				$list[$key] = [
					"name" => $SonarrServer['url'],
					"value" => $key
				];
			}
		} else {
			$list = [['name' => 'Refresh page to update List', 'value' => '', 'disabled' => true]];
		}

		return array(
			'About' => array (
				$this->settingsOption('notice', '', ['title' => 'Information', 'body' => '
				<h3 lang="en">Plugin Information</h3>
				<p>This plugin allows you to specify a threshold for TV Show sizes and throttles downloads accordingly. It works by configuring a webhook in Overseerr and Tautulli to manage TV Show episode downloading based on if episodes are being watched. Shows with seasons/episodes over a configured threshold will be marked as throttled and only the first X number of episodes will be downloaded. Further episodes will only be downloaded when an event is logged in Tautulli. Using this method prevents large TV Shows from being downloaded for nobody to watch them.</p>
				<p>More information available within the <a href="https://github.com/TehMuffinMoo/SonarrThrottling" target="blank">README</a></p>
				<br/>
				<h3>Tautulli Webhook</h3>
				<p>Configure this Webhook in Tautulli. Using the <code>Playback Start</code> or <code>Watched</code> triggers will provide the best experience.</p>
				<code class="elip hidden-xs">' . $this->getServerPath() . 'api/v2/plugins/sonarrthrottling/webhooks/tautulli</code>
				<br/>
				<p>Tautulli JSON Data - This can be customised as long as <b>tvdbId</b> and <b>media_type</b> are present.</p>
				<pre>
{
   "action": "{action}",
    "title": "{title}",
    "username": "{username}",
    "media_type": "{media_type}",
    "tvdbId": "{thetvdb_id}"
}			</pre>
				<p>Tautulli JSON Headers - API Key for Sonarr Throttling Plugin</p>
				<pre>
{
	"authorization": "' . $this->config['SONARRTHROTTLING-ApiToken'] . '"
}				</pre>
				<br/>
				<h3>Overseerr Webhook</h3>
				<p>Configure this Webhook in Overseerr</p>
				<code class="elip hidden-xs">' . $this->getServerPath() . 'api/v2/plugins/sonarrthrottling/webhooks/overseerr</code>
				<br/>
				<p>Overseerr JSON Payload (Default Webhook) - This can be customised as long as <b>media->tvdbId</b> and <b>media->media_type</b> are present</p>
				<pre>
{
    "notification_type": "{{notification_type}}",
    "subject": "{{subject}}",
    "message": "{{message}}",
    "image": "{{image}}",
    "email": "{{notifyuser_email}}",
    "username": "{{notifyuser_username}}",
    "avatar": "{{notifyuser_avatar}}",
    "{{media}}": {
        "media_type": "{{media_type}}",
        "tmdbId": "{{media_tmdbid}}",
        "imdbId": "{{media_imdbid}}",
        "tvdbId": "{{media_tvdbid}}",
        "status": "{{media_status}}",
        "status4k": "{{media_status4k}}"
    },
    "{{extra}}": []
}				</pre>
				<p>Overseerr Authorization Header - API Key for Sonarr Throttling Plugin</p>
				<pre>' . $this->config['SONARRTHROTTLING-ApiToken'] . '</pre>
				<br/>']),
			),
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'SONARRTHROTTLING-pluginAuth'),
				$this->settingsOption('input', 'SONARRTHROTTLING-ThrottledTagName', ['label' => 'The name of the tag you want to use in Sonarr']),
				$this->settingsOption('input', 'SONARRTHROTTLING-SeasonCountThreshold', ['label' => 'Season Threshold']),
				$this->settingsOption('input', 'SONARRTHROTTLING-EpisodeCountThreshold', ['label' => 'Episode Threshold']),
				$this->settingsOption('input', 'SONARRTHROTTLING-EpisodeSearchCount', ['label' => 'Amount of episodes to perform initial scan for']),
				$this->settingsOption('token', 'SONARRTHROTTLING-ApiToken'),
				$this->settingsOption('blank'),
				$this->settingsOption('button', '', ['label' => 'Generate API Token', 'icon' => 'fa fa-undo', 'text' => 'Retrieve', 'attr' => 'onclick="sonarrThrottlingPluginGenerateAPIKey();"']),
			),
			'Sonarr Settings' => array(
				$this->settingsOption('multiple-url', 'sonarrURL'),
				$this->settingsOption('multiple-token', 'sonarrToken'),
				$this->settingsOption('select', 'SONARRTHROTTLING-preferredSonarr', ['label' => 'Preferred Server', 'options' => $list]),
				$this->settingsOption('disable-cert-check', 'sonarrDisableCertCheck'),
				$this->settingsOption('use-custom-certificate', 'sonarrUseCustomCertificate'),
				$this->settingsOption('test', 'sonarr'),
			),
		);
	}

	public function _sonarrThrottlingPluginLaunch()
	{
		$user = $this->getUserById($this->user['userID']);
		if ($user) {
			$this->setResponse(200, 'User approved for plugin');
			return true;
		}
		$this->setResponse(401, 'User not approved for plugin');
		return false;
	}

	public function sonarrThrottlingPluginGetSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID) {
		$SonarrSeriesEndpoint = $SonarrHost.'/series/'.$SeriesID.'?apikey='.$SonarrAPIKey; // Set Sonarr Series Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		## Query Sonarr Series API
		try {
			$response = Requests::get($SonarrSeriesEndpoint, $headers, []);
			if ($response->success) {
				$SonarrSeriesObj = json_decode($response->body);
				return $SonarrSeriesObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query Sonarr series');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query Sonarr series: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query Sonarr series' . $e->getMessage(), 409);
			return false;
		}
	}

	public function sonarrThrottlingPluginSetSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID,$postData) {
		$SonarrSeriesEndpoint = $SonarrHost.'/series/'.$SeriesID.'?apikey='.$SonarrAPIKey; // Set Sonarr Series Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		## Query Sonarr Series API
		try {
			$response = Requests::put($SonarrSeriesEndpoint, $headers, $postData, []);
			if ($response->success) {
				$SonarrSeriesObj = json_decode($response->body);
				return $SonarrSeriesObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to update Sonarr series');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to update Sonarr series: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to update Sonarr series' . $e->getMessage(), 409);
			return false;
		}
	}

	public function sonarrThrottlingPluginGetSonarrEpisodes($SonarrHost,$SonarrAPIKey,$SeriesID) {
		$SonarrEpisodeEndpoint = $SonarrHost.'/episode/?apikey='.$SonarrAPIKey.'&seriesId='.$SeriesID; // Set Sonarr Episode Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		try {
			$response = Requests::get($SonarrEpisodeEndpoint, $headers, []);
			if ($response->success) {
				$SonarrEpisodeObj = $response->body;
				return $SonarrEpisodeObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query episode data');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query episode data: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query episode data' . $e->getMessage(), 409);
			return false;
		}	
	}

	public function sonarrThrottlingPluginRunSonarrCommand($SonarrHost,$SonarrAPIKey,$postData) {
		$SonarrCommandEndpoint = $SonarrHost."/command/?apikey=".$SonarrAPIKey; // Set Sonarr Command Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		try {
			$response = Requests::post($SonarrCommandEndpoint, $headers, $postData, []);
			if ($response->success) {
				$SonarrCommandObj = json_decode($response->body);
				return $SonarrCommandObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to run Sonarr command');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to run Sonarr command: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to run Sonarr command' . $e->getMessage(), 409);
			return false;
		}	
	}

	public function sonarrThrottlingPluginLookupSonarrSeries($SonarrHost,$SonarrAPIKey,$tvdbId) {
		$tvdbIdSearch = "tvdbid:".$tvdbId; // Set tvdbId search string
		$SonarrLookupEndpoint = $SonarrHost.'/series/lookup?term='.$tvdbIdSearch.'&apikey='.$SonarrAPIKey; // Set Sonarr Lookup Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		## Query Sonarr Lookup API
		try {
			$response = Requests::get($SonarrLookupEndpoint, $headers, []);
			if ($response->success) {
				$SonarrLookupObj = json_decode($response->body);
				return $SonarrLookupObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to lookup Sonarr series');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to lookup Sonarr series: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to lookup Sonarr series' . $e->getMessage(), 409);
			return false;
		}
	}

	public function sonarrThrottlingPluginGetSonarrTags($SonarrHost,$SonarrAPIKey) {
		$SonarrTagEndpoint = $SonarrHost.'/tag?apikey='.$SonarrAPIKey; // Set Sonarr Tag Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		try {
			$response = Requests::get($SonarrTagEndpoint, $headers, []);
			if ($response->success) {
				$SonarrTagObj = json_decode($response->body);
				return $SonarrTagObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query Sonarr tags');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query Sonarr tags: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query Sonarr tags' . $e->getMessage(), 409);
			return false;
		}
	}

	public function sonarrThrottlingPluginGetThrottledTag($SonarrHost,$SonarrAPIKey) {
		$ThrottledTagName = $this->config['SONARRTHROTTLING-ThrottledTagName'];
		$SonarrTagObj = $this->sonarrThrottlingPluginGetSonarrTags($SonarrHost,$SonarrAPIKey);
		$ThrottledTagKey = array_search($ThrottledTagName, array_column($SonarrTagObj, 'label'));
		if (!$ThrottledTagKey) {
			$this->sonarrThrottlingPluginCreateThrottledTag($SonarrHost,$SonarrAPIKey);
			$SonarrTagObj = $this->sonarrThrottlingPluginGetSonarrTags($SonarrHost,$SonarrAPIKey);
			$ThrottledTagKey = array_search($ThrottledTagName, array_column($SonarrTagObj, 'label'));
			if (!$ThrottledTagKey) {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to find throttling Sonarr tag');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to find throttling Sonarr tag', 'SYSTEM');
				return false;
			} else {
				$ThrottledTag = $SonarrTagObj[$ThrottledTagKey]->id;
				return $ThrottledTag;
			}
		} else {
			$ThrottledTag = $SonarrTagObj[$ThrottledTagKey]->id;
			return $ThrottledTag;
		}
	}

	public function sonarrThrottlingPluginCreateThrottledTag($SonarrHost,$SonarrAPIKey) {
		$SonarrTagEndpoint = $SonarrHost.'/tag?apikey='.$SonarrAPIKey; // Set Sonarr Tag Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		$postData = json_encode(array(
			"label" => $this->config['SONARRTHROTTLING-ThrottledTagName'],
		));
		try {
			$response = Requests::post($SonarrTagEndpoint, $headers, $postData,[]);
			if ($response->success) {
				$SonarrTagObj = json_decode($response->body);
				return $SonarrTagObj;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to create Sonarr throttled tag');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to create Sonarr throttled tag: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to create Sonarr throttled tag' . $e->getMessage(), 409);
			return false;
		}		
	}

	public function sonarrThrottlingPluginGetSonarrThrottled() {
		## Set Sonarr Details
		$SonarrInstances = $this->chooseInstance($this->config['sonarrURL'],$this->config['sonarrToken'],$this->config['SONARRTHROTTLING-preferredSonarr']);
		$SonarrHost = $SonarrInstances['url'].'/api';
		$SonarrAPIKey = $SonarrInstances['token'];
		$ThrottledTag = $this->sonarrThrottlingPluginGetThrottledTag($SonarrHost,$SonarrAPIKey);
		$SonarrSeriesObj = $this->sonarrThrottlingPluginGetSonarrSeries($SonarrHost,$SonarrAPIKey,"");
		$apiData = array();
		foreach ($SonarrSeriesObj as $SonarrSeriesItem) {
			if (in_array($ThrottledTag,$SonarrSeriesItem->tags)) {
				if ($SonarrSeriesItem->episodeCount != "0") {
					$SonarrSeriesItemPerc = (100 / $SonarrSeriesItem->episodeCount) * $SonarrSeriesItem->episodeFileCount;
					if ($SonarrSeriesItemPerc > 100) {
						$SonarrSeriesItemPerc = "100";
					}
					foreach ($SonarrSeriesItem->images as $ImgObj) {
						if ($ImgObj->coverType == "poster") {
						$SonarrSeriesObjImage = $ImgObj->remoteUrl;
						}
					}
					$apiData[] = array (
						"Title" => $SonarrSeriesItem->title,
						"EpisodeCount" => $SonarrSeriesItem->episodeCount,
						"EpisodeFileCount" => $SonarrSeriesItem->episodeFileCount,
						"TotalEpisodeCount" => $SonarrSeriesItem->totalEpisodeCount,
						"Progress" => $SonarrSeriesItemPerc,
						"ImageUrl" => $SonarrSeriesObjImage,
						"tvdbId" => $SonarrSeriesItem->tvdbId
					);
				}
			}
		}
		return $apiData;		
	}

	public function TautulliWebhook($request)
	{
		## Set Sonarr Details
		$SonarrInstances = $this->chooseInstance($this->config['sonarrURL'],$this->config['sonarrToken'],$this->config['SONARRTHROTTLING-preferredSonarr']);
		$SonarrHost = $SonarrInstances['url'].'/api';
		$SonarrAPIKey = $SonarrInstances['token'];

		## Get Throttled Tag
		$ThrottledTag = $this->sonarrThrottlingPluginGetThrottledTag($SonarrHost,$SonarrAPIKey);

		## Error if Throttled tag is missing in Sonarr.
		if (empty($ThrottledTag)) {
			$this->setResponse(409, 'Throttling tag missing from Sonarr, check logs.');
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Throttling tag missing from Sonarr, check logs.', 'SYSTEM');
			return false;
		}
			
		## Check for valid data and API Key
		if ($request == null) {
			$this->setResponse(409, 'PHP Input Empty');
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: PHP Input Empty', 'SYSTEM');
			return false;
		}
		
		## Decode POST Data
		$POST_DATA = json_decode($request, true);

		## Check for test notification
		if ($POST_DATA['test_notification']) {
			$this->setResponse(200, 'TEST SUCCESSFUL');
			$this->writeLog('info', 'Sonarr Throttling Plugin - Tautulli Webhook Test Received.', 'SYSTEM');
			return true;
		}

		if ($POST_DATA['media_type'] == "episode") {
			
			## Check tvdbId exists
			if (empty($POST_DATA['tvdbId'])) {
				$this->setResponse(409, 'Empty tvdbId');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: Empty tvdbId', 'SYSTEM');
				return false;
			}

			## Set Sonarr Search Endpoint
			$SonarrLookupObj = $this->sonarrThrottlingPluginLookupSonarrSeries($SonarrHost,$SonarrAPIKey,$POST_DATA['tvdbId']);

			## Check if Sonarr ID Exists
			if (empty($SonarrLookupObj[0]->id)) {
				$this->setResponse(409, 'TV Show not in Sonarr database.');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: TV Show not in Sonarr database.', 'SYSTEM');
				return false;
			}
				
			## Query Sonarr Series API
			$SeriesID = $SonarrLookupObj[0]->id;
			$SonarrSeriesObj = $this->sonarrThrottlingPluginGetSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID);
				
			## Query Sonarr Episode API
			if (in_array($ThrottledTag,$SonarrSeriesObj->tags)) {
				$SonarrEpisodeObj = json_decode($this->sonarrThrottlingPluginGetSonarrEpisodes($SonarrHost,$SonarrAPIKey,$SeriesID));
				## Find next incremental episode to download
				foreach ($SonarrEpisodeObj as $Episode) {
					if ($Episode->hasFile == false && $Episode->seasonNumber != "0" && $Episode->monitored == true) {
						## Send Scan Request to Sonarr
						$EpisodesToSearch[] = $Episode->id; // Episode IDs
						$SonarrSearchPostData['name'] = "EpisodeSearch";  // Sonarr command to run
						$SonarrSearchPostData['episodeIds'] = $EpisodesToSearch; // Episode IDs Array
						$SonarrSearchPostData = json_encode($SonarrSearchPostData); // POST Data
						$this->sonarrThrottlingPluginRunSonarrCommand($SonarrHost,$SonarrAPIKey,$SonarrSearchPostData);
						$MoreEpisodesAvailable = true;
						
						$Response = 'Search request sent for: '.$SonarrSeriesObj->title.' - S'.$Episode->seasonNumber.'E'.$Episode->episodeNumber.' - '.$Episode->title.PHP_EOL;
						$this->writeLog('info', 'Sonarr Throttling Plugin - Tautulli Webhook: '.$Response.'', 'SYSTEM');
						$this->setResponse(200, $Response);
						break;
					}
				}
				if (empty($MoreEpisodesAvailable)) {
					## Find Throttled Tag and remove it
					$SonarrSeriesObjtags[] = $SonarrSeriesObj->tags;
					$ArrKey = array_search($ThrottledTag, $SonarrSeriesObjtags['0']);
					unset($SonarrSeriesObjtags['0'][$ArrKey]);
					$SonarrSeriesObj->tags = $SonarrSeriesObjtags['0'];
					## Mark TV Show as Monitored
					$SonarrSeriesObj->monitored = true;
					## Submit data back to Sonarr
					$SonarrSeriesJSON = json_encode($SonarrSeriesObj); // Convert back to JSON
					$SonarrSeriesPUT = $this->sonarrThrottlingPluginSetSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID,$SonarrSeriesJSON); // POST Data to Sonarr
					$Response = 'All aired episodes are available. Removed throttling from: '.$SonarrSeriesObj->title.' and marked as monitored.';
					$this->setResponse(200, $Response);
					$this->writeLog('info', 'Sonarr Throttling Plugin - Tautulli Webhook: '.$Response.'', 'SYSTEM');
					return true;
				}
			} else {
				$this->setResponse(200, 'TV Show not throttled.');
				return true;
			}
		} else {
			$this->setResponse(200, 'Not a TV Show.');
			return true;
		}
	}


	public function OverseerrWebhook($request)
	{

		## Check for data
		if ($request == null) {
			$this->setResponse(409, 'PHP Input Empty');
			$this->writeLog('error', 'Sonarr Throttling Plugin - Overseerr Webhook Error: PHP Input Empty', 'SYSTEM');
			return false;
		}
		
		## Decode POST Data
		$POST_DATA = json_decode($request, true);

		if ($POST_DATA['notification_type'] == "TEST_NOTIFICATION") {
			$this->setResponse(200, 'TEST SUCCESSFUL');
			$this->writeLog('info', 'Sonarr Throttling Plugin - Overseerr Webhook Test Received.', 'SYSTEM');
			return true;
		}

		## Check Request Type
		if ($POST_DATA['media']['media_type'] == "tv") {
			
			## Sleep to allow Sonarr to update. Might add a loop checking logic here in the future.
			sleep(10);
			## Set Sonarr Details
			$SonarrInstances = $this->chooseInstance($this->config['sonarrURL'],$this->config['sonarrToken'],$this->config['SONARRTHROTTLING-preferredSonarr']);
			$SonarrHost = $SonarrInstances['url'].'/api';
			$SonarrAPIKey = $SonarrInstances['token'];
			## Set Parameters
			$SeasonCountThreshold = $this->config['SONARRTHROTTLING-SeasonCountThreshold'];
			$EpisodeCountThreshold = $this->config['SONARRTHROTTLING-EpisodeCountThreshold'];
			$EpisodeSearchCount = $this->config['SONARRTHROTTLING-EpisodeSearchCount'];
			
			## Get Throttled Tag
			$ThrottledTag = $this->sonarrThrottlingPluginGetThrottledTag($SonarrHost,$SonarrAPIKey);
			## Error if Throttled tag is missing in Sonarr.
			if (empty($ThrottledTag)) {
				$this->setResponse(409, 'Throttling tag missing from Sonarr, check logs.');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Throttling tag missing from Sonarr, check logs.', 'SYSTEM');
				return false;
			}
			## Lookup Sonarr Series by tvdbId
			$SonarrLookupObj = $this->sonarrThrottlingPluginLookupSonarrSeries($SonarrHost,$SonarrAPIKey,$POST_DATA['media']['tvdbId']);
			## Check if Sonarr ID Exists
			if (empty($SonarrLookupObj[0]->id)) {
				$this->setResponse(409, 'TV Show not in Sonarr database');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Overseerr Webhook Error: TV Show not in Sonarr database.', 'SYSTEM');
				return false;
			}
			
			## Query Sonarr Series API
			$SeriesID = $SonarrLookupObj[0]->id;
			$SonarrSeriesObj = $this->sonarrThrottlingPluginGetSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID);

			## Check Season Count & Apply Throttling Tag if neccessary
			$EpisodeCount = 0;
			foreach ($SonarrSeriesObj->seasons as $season) {
			$EpisodeCount += $season->statistics->totalEpisodeCount;
			}
		
			$SeasonCount = $SonarrSeriesObj->seasonCount;
			if ($SeasonCount > $SeasonCountThreshold) {
				$SonarrSeriesObjtags[] = $ThrottledTag;
				$SonarrSeriesObj->tags = $SonarrSeriesObjtags;
				$SonarrSeriesObj->monitored = false;
				$Search = "searchX";
			} else if ($EpisodeCount > $EpisodeCountThreshold) {
				$SonarrSeriesObjtags[] = $ThrottledTag;
				$SonarrSeriesObj->tags = $SonarrSeriesObjtags;
				$SonarrSeriesObj->monitored = false;
				$Search = "searchX";
			} else {
				$SonarrSeriesObj->monitored = true;
				$SonarrSeriesObj->addOptions->searchForMissingEpisodes = true;
				$Search = "searchAll";
			};

			## Set Sonarr Command Endpoint
			$SonarrCommandEndpoint = $SonarrHost."/command/?apikey=".$SonarrAPIKey; // Set Sonarr URI
			
			## Initiate Searching
			if ($Search == "searchAll") {
				$SonarrSearchPostData['name'] = "SeriesSearch";
				$SonarrSearchPostData['seriesId'] = $SeriesID;
				$SonarrSearchPostData = json_encode($SonarrSearchPostData);
				$this->sonarrThrottlingPluginRunSonarrCommand($SonarrHost,$SonarrAPIKey,$SonarrSearchPostData); // Send Scan Command to Sonarr
			} else if ($Search == "searchX") {
				$Episodes = json_decode($this->sonarrThrottlingPluginGetSonarrEpisodes($SonarrHost,$SonarrAPIKey,$SeriesID),true); // Get list of episodes
				foreach ($Episodes as $Key => $Episode) {
					if ($Episode['seasonNumber'] != "0" && $Episode['hasFile'] != true) {
					$EpisodesToSearch[] = $Episode['id'];
					}
				}
				$SonarrSearchPostData['name'] = "EpisodeSearch";
				$SonarrSearchPostData['episodeIds'] = array_slice($EpisodesToSearch,0,$EpisodeSearchCount);
				$SonarrSearchPostData = json_encode($SonarrSearchPostData);
				$this->sonarrThrottlingPluginRunSonarrCommand($SonarrHost,$SonarrAPIKey,$SonarrSearchPostData); // Send Scan Command to Sonarr
			}

			## Submit data back to Sonarr
			$SonarrSeriesJSON = json_encode($SonarrSeriesObj); // Convert back to JSON
			$SonarrSeriesPUT = $this->sonarrThrottlingPluginSetSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID,$SonarrSeriesJSON); // POST Data to Sonarr

			if ($SonarrSeriesPUT) {
				switch ($Search) {
					case "searchAll":
						$Response = $SonarrSeriesObj->title.' has been added as a normal TV Show. Sent search request for all episodes.';
						$this->writeLog('info', 'Sonarr Throttling Plugin - Overseerr Webhook: '.$Response.'', 'SYSTEM');
						$this->setResponse(200, $Response);
					case "searchX":
						$Response = $SonarrSeriesObj->title.' has been added as a Throttled TV Show. Sent search request for the first '.$EpisodeSearchCount.' episodes.';
						$this->writeLog('info', 'Sonarr Throttling Plugin - Overseerr Webhook: '.$Response.'', 'SYSTEM');
						$this->setResponse(200, $Response);
				}
			} else {
				$this->setResponse(409, 'Unable to update TV Show.');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Overseerr Webhook Error: Unable to update TV Show.', 'SYSTEM');
			}
				
		} else {
			$this->setResponse(200, 'Not a TV Show Request.');
			return true;
		}

	}
}
