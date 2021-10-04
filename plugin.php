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
				$this->settingsOption('input', 'SONARRTHROTTLING-ThrottledTagName', ['label' => 'The name of the tag you want to use in Sonarr']),
				$this->settingsOption('input', 'SONARRTHROTTLING-SeasonCountThreshold', ['label' => 'Season Threshold']),
				$this->settingsOption('input', 'SONARRTHROTTLING-EpisodeCountThreshold', ['label' => 'Episode Threshold']),
				$this->settingsOption('input', 'SONARRTHROTTLING-EpisodeSearchCount', ['label' => 'Amount of episodes to perform initial scan for']),
			),
			'Sonarr' => array(
				$this->settingsOption('multiple-url', 'sonarrURL'),
				$this->settingsOption('multiple-token', 'sonarrToken'),
				$this->settingsOption('disable-cert-check', 'sonarrDisableCertCheck'),
				$this->settingsOption('use-custom-certificate', 'sonarrUseCustomCertificate'),
				$this->settingsOption('test', 'sonarr'),
			)
		);
	}

	public function getSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID) {
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

	public function setSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID,$postData) {
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

	public function getSonarrEpisodes($SonarrHost,$SonarrAPIKey,$SeriesID) {
		$SonarrEpisodeEndpoint = $SonarrHost.'/episode/?apikey='.$SonarrAPIKey.'&seriesId='.$SeriesID; // Set Sonarr Episode Endpoint
		$headers = array(
			'Accept' => 'application/json',
			'Content-type: application/json',
		);
		try {
			$response = Requests::get($SonarrEpisodeEndpoint, $headers, []);
			if ($response->success) {
				$SonarrEpisodeObj = json_decode($response->body);
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

	public function runSonarrCommand($SonarrHost,$SonarrAPIKey,$postData) {
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

	public function lookupSonarrSeries($SonarrHost,$SonarrAPIKey,$tvdbId) {
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

	public function getSonarrTags($SonarrHost,$SonarrAPIKey) {
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

	public function TautulliWebhook($request)
	{
		## Set Sonarr Details
		$SonarrHost = $this->config['sonarrURL'].'/api';
		$SonarrAPIKey = $this->config['sonarrToken'];

		## Set Parameters
		$ThrottledTagName = $this->config['SONARRTHROTTLING-ThrottledTagName'];
		  
		## Query Sonarr Tags
		$SonarrTagObj = $this->getSonarrTags($SonarrHost,$SonarrAPIKey);
		$ThrottledTagKey = array_search($ThrottledTagName, array_column($SonarrTagObj, 'label'));
		$ThrottledTag = $SonarrTagObj[$ThrottledTagKey]->id;

		############# DEBUG #############
		$req_dump = print_r( $request, true );
		$fp = file_put_contents( 'tautulli-request.log', $req_dump );
		#################################

		$DateTime = date("d-m-Y h:i:s");
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
		
		## Check tvdbId exists
		if (empty($POST_DATA['tvdbId'])) {
			$this->setResponse(409, 'Empty tvdbId');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: Empty tvdbId', 'SYSTEM');
				return false;
			}

		## Error if Throttled tag is missing in Sonarr. May add auto creation of tag in future.
		if (empty($ThrottledTag)) {
			$this->setResponse(409, 'Throttled tag missing from Sonarr');
			$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: Throttled tag missing from Sonarr, check configuration.', 'SYSTEM');
			return false;
		}

		## Set Sonarr Search Endpoint
		$SonarrLookupObj = $this->lookupSonarrSeries($SonarrHost,$SonarrAPIKey,$POST_DATA['tvdbId']);

		## Check if Sonarr ID Exists
		if (empty($SonarrLookupObj[0]->id)) {
			$this->setResponse(409, 'TV Show not in Sonarr database.');
			$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: TV Show not in Sonarr database.', 'SYSTEM');
			return false;
		}
			
		## Query Sonarr Series API
		$SeriesID = $SonarrLookupObj[0]->id;
		$SonarrSeriesObj = $this->getSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID);
			
		## Query Sonarr Episode API
		if (in_array($ThrottledTag,$SonarrSeriesObj->tags)) {
			$SonarrEpisodeObj = $this->getSonarrEpisodes($SonarrHost,$SonarrAPIKey,$SeriesID);
			## Find next incremental episode to download
			foreach ($SonarrEpisodeObj as $Episode) {
				if ($Episode->hasFile == false && $Episode->seasonNumber != "0" && $Episode->monitored == true) {
					## Send Scan Request to Sonarr
					$EpisodesToSearch[] = $Episode->id; // Episode IDs
					$SonarrSearchPostData['name'] = "EpisodeSearch"; // Sonarr command to run
					$SonarrSearchPostData['episodeIds'] = $EpisodesToSearch; // Episode IDs Array
					$SonarrSearchPostData = json_encode($SonarrSearchPostData); // POST Data
					$this->runSonarrCommand($SonarrHost,$SonarrAPIKey,$SonarrSearchPostData);
					$MoreEpisodesAvailable = true;
					
					$Response = $DateTime.' - Search request sent for: '.$SonarrSeriesObj->title.' - S'.$Episode->seasonNumber.'E'.$Episode->episodeNumber.' - '.$Episode->title.PHP_EOL;
					file_put_contents( 'tautulli.log', $Response, FILE_APPEND );
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
				$SonarrSeriesPUT = $this->setSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID,$SonarrSeriesJSON); // POST Data to Sonarr
				$Response = $DateTime.' - All aired episodes are available. Removed throttling from: '.$SonarrSeriesObj->title.' and marked as monitored.'.PHP_EOL;
				$this->setResponse(200, $Response);
				$this->writeLog('info', 'Sonarr Throttling Plugin - Tautulli Webhook: '.$Response.'', 'SYSTEM');
				return true;
			}
		} else {
			$this->setResponse(200, 'TV Show not throttled.');
			return true;
		}
	}


	public function OverseerrWebhook($request)
	{

		############# DEBUG #############
		$req_dump = print_r( $request, true );
		$fp = file_put_contents( 'overseerr-request.log', $req_dump );
		#################################

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
			$SonarrHost = $this->config['sonarrURL'].'/api';
			$SonarrAPIKey = $this->config['sonarrToken'];
			## Set Parameters
			$SeasonCountThreshold = $this->config['SONARRTHROTTLING-SeasonCountThreshold'];
			$EpisodeCountThreshold = $this->config['SONARRTHROTTLING-EpisodeCountThreshold'];
			$EpisodeSearchCount = $this->config['SONARRTHROTTLING-EpisodeSearchCount'];
			$ThrottledTagName = $this->config['SONARRTHROTTLING-ThrottledTagName'];
			
			$SonarrTagObj = $this->getSonarrTags($SonarrHost,$SonarrAPIKey);
			$ThrottledTagKey = array_search($ThrottledTagName, array_column($SonarrTagObj, 'label'));
			$ThrottledTag = $SonarrTagObj[$ThrottledTagKey]->id;
			
			## Error if Throttled tag is missing in Sonarr. May add auto creation of tag in future.
			if (empty($ThrottledTag)) {
				$this->setResponse(409, 'Throttled tag missing from Sonarr');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Throttled tag missing from Sonarr, check configuration.', 'SYSTEM');
				return false;
			}
				
			## Lookup Sonarr Series by tvdbId
			$SonarrLookupObj = $this->lookupSonarrSeries($SonarrHost,$SonarrAPIKey,$POST_DATA['tvdbId']);
			
			## Check if Sonarr ID Exists
			if (empty($SonarrLookupObj[0]->id)) {
				$this->setResponse(409, 'TV Show not in Sonarr database');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Error: TV Show not in Sonarr database.', 'SYSTEM');
				return false;
			}
			
			## Query Sonarr Series API
			$SeriesID = $SonarrLookupObj[0]->id;
			$SonarrSeriesObj = $this->getSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID);

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
				$this->runSonarrCommand($SonarrHost,$SonarrAPIKey,$SonarrSearchPostData); // Send Scan Command to Sonarr
			} else if ($Search == "searchX") {
				$Episodes = $this->getSonarrEpisodes($SonarrHost,$SonarrAPIKey,$SeriesID); // Get list of episodes
				foreach ($Episodes as $Key => $Episode) {
					if ($Episode['seasonNumber'] != "0" && $Episode['hasFile'] != true) {
					$EpisodesToSearch[] = $Episode['id'];
					}
				}
				$SonarrSearchPostData['name'] = "EpisodeSearch";
				$SonarrSearchPostData['episodeIds'] = array_slice($EpisodesToSearch,0,$EpisodeSearchCount);
				$SonarrSearchPostData = json_encode($SonarrSearchPostData);
				$this->runSonarrCommand($SonarrHost,$SonarrAPIKey,$SonarrSearchPostData); // Send Scan Command to Sonarr
			}
			
			## Submit data back to Sonarr
			$SonarrSeriesJSON = json_encode($SonarrSeriesObj); // Convert back to JSON
			$SonarrSeriesPUT = $this->setSonarrSeries($SonarrHost,$SonarrAPIKey,$SeriesID,$SonarrSeriesJSON); // POST Data to Sonarr
				
		} else {
			$this->setResponse(200, 'Not a TV Show Request.');
			return true;
		}

	}
}