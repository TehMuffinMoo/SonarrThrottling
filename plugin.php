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


	public function TautulliWebhook($request)
	{
		## Set Sonarr Details
		$SonarrHost = $this->config['sonarrURL'].'/api';
		$SonarrAPIKey = $this->config['sonarrToken'];

		## Set Parameters
		$ThrottledTagName = $this->config['SONARRTHROTTLING-ThrottledTagName'];
		$headers = array(
			'Content-type: application/json',
		);
		  
		## Set Sonarr Tag Endpoint
		$SonarrTagEndpoint = $SonarrHost.'/tag?apikey='.$SonarrAPIKey;

		try {
			$response = Requests::get($SonarrTagEndpoint, $headers, []);
			if ($response->success) {
				$SonarrTagObj = json_decode($response->body);
				$ThrottledTagKey = array_search($ThrottledTagName, array_column($SonarrTagObj, 'label'));
				$ThrottledTag = $SonarrTagObj[$ThrottledTagKey]->id;
			} else {
				$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to check Sonarr tags');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Sonarr Throttling Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to check Sonarr tags: ' . $e->getMessage(), 409);
			return false;
		}

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

		if (!$POST_DATA['test_notification']) {

			## Check tvdbId exists
			if (empty($POST_DATA['tvdbId'])) {
				$this->setResponse(409, 'Empty tvdbId');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: Empty tvdbId', 'SYSTEM');
				return false;
			}

			## Kill if Throttled tag is missing in Sonarr. May add auto creation of tag in future.
			if (empty($ThrottledTag)) {
				$this->setResponse(409, 'Throttled tag missing from Sonarr');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: Throttled tag missing from Sonarr, check configuration.', 'SYSTEM');
				return false;
			}

			## Set Sonarr Search Endpoint
			$userSearch = "tvdbid:".$POST_DATA['tvdbId'];
			$SonarrLookupEndpoint = $SonarrHost.'/series/lookup?term='.$userSearch.'&apikey='.$SonarrAPIKey;

			## Query Sonarr Lookup API
			try {
				$response = Requests::get($SonarrLookupEndpoint, $headers, []);
				if ($response->success) {
					$SonarrLookupObj = json_decode($response->body);
				} else {
					$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query Sonarr series');
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to check Sonarr tags: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to check Sonarr tags: ' . $e->getMessage(), 409);
				return false;
			}

			## Check if Sonarr ID Exists
			if (empty($SonarrLookupObj[0]->id)) {
				$this->setResponse(409, 'TV Show not in Sonarr database.');
				$this->writeLog('error', 'Sonarr Throttling Plugin - Tautulli Webhook Error: TV Show not in Sonarr database.', 'SYSTEM');
				return false;
			}
			
			## Set Sonarr Series Endpoint
			$SeriesID = $SonarrLookupObj[0]->id;
			$SonarrSeriesEndpoint = $SonarrHost.'/series/'.$SeriesID.'?apikey='.$SonarrAPIKey;
		
			## Query Sonarr Series API
			try {
				$response = Requests::get($SonarrSeriesEndpoint, $headers, []);
				if ($response->success) {
					$SonarrSeriesObj = json_decode($response->body);
				} else {
					$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query series');
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query series: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query series' . $e->getMessage(), 409);
				return false;
			}
			
			## Query Sonarr Episode API
			if (in_array($ThrottledTag,$SonarrSeriesObj->tags)) {
				$SonarrEpisodeEndpoint = $SonarrHost.'/episode/?apikey='.$SonarrAPIKey.'&seriesId='.$SeriesID;
				try {
					$response = Requests::get($SonarrEpisodeEndpoint, $headers, []);
					if ($response->success) {
						$SonarrEpisodeObj = json_decode($response->body);
					} else {
						$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query episode data');
						return false;
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query episode data: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query episode data' . $e->getMessage(), 409);
					return false;
				}	
				## Find next incremental episode to download
				foreach ($SonarrEpisodeObj as $Episode) {
					if ($Episode->hasFile == false && $Episode->seasonNumber != "0" && $Episode->monitored == true) {
					$Response = $DateTime.' - Search request sent for: '.$SonarrSeriesObj->title.' - S'.$Episode->seasonNumber.'E'.$Episode->episodeNumber.' - '.$Episode->title.PHP_EOL;
					file_put_contents( 'tautulli.log', $Response, FILE_APPEND );
					$this->setResponse(200, $Response);
					
					## Send Scan Request to Sonarr
					$SonarrCommandEndpoint = $SonarrHost."/command/?apikey=".$SonarrAPIKey; // Set Sonarr URI
					$EpisodesToSearch[] = $Episode->id; // Episode IDs
					$SonarrSearchPostData['name'] = "EpisodeSearch"; // Sonarr command to run
					$SonarrSearchPostData['episodeIds'] = $EpisodesToSearch; // Episode IDs Array
					$SonarrSearchPostData = json_encode($SonarrSearchPostData); // POST Data
					$this->REST($SonarrCommandEndpoint, $SonarrSearchPostData, 'POST'); // Send Scan Command to Sonarr
					$MoreEpisodesAvailable = true;
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
					$SonarrSeriesPUT = $this->REST($SonarrSeriesEndpoint, $SonarrSeriesJSON, 'PUT'); // POST Data to Sonarr

					$Response = $DateTime.' - All aired episodes are available. Removed throttling from: '.$SonarrSeriesObj->title.' and marked as monitored.'.PHP_EOL;
					$this->setResponse(200, $Response);
					$this->writeLog('info', 'Sonarr Throttling Plugin - Tautulli Webhook: '.$Response.'', 'SYSTEM');
					return true;
				}
			} else {
				$this->setResponse(200, 'TV Show not throttled.');
				return true;
			}
		} else {
			$this->setResponse(200, 'TEST SUCCESSFUL');
			$this->writeLog('info', 'Sonarr Throttling Plugin - Tautulli Webhook Test Received.', 'SYSTEM');
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

		if ($POST_DATA['notification_type'] != "TEST_NOTIFICATION") {

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
				
				## Set Sonarr Tag Endpoint
				$SonarrTagEndpoint = $SonarrHost.'/tag?apikey='.$SonarrAPIKey;
				try {
					$response = Requests::get($SonarrTagEndpoint, $headers, []);
					if ($response->success) {
						$SonarrTagObj = json_decode($response->body);
					} else {
						$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query sonarr tags');
						return false;
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr tags: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr tags' . $e->getMessage(), 409);
					return false;
				}
				$ThrottledTagKey = array_search($ThrottledTagName, array_column($SonarrTagObj, 'label'));
				$ThrottledTag = $SonarrTagObj[$ThrottledTagKey]->id;
				
				## Kill if Throttled tag is missing in Sonarr. May add auto creation of tag in future.
				if (empty($ThrottledTag)) {
					$this->setResponse(409, 'Throttled tag missing from Sonarr');
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Throttled tag missing from Sonarr, check configuration.', 'SYSTEM');
					return false;
				}
				
				## Set Sonarr Search Endpoint
				$userSearch = "tvdbid:".$POST_DATA['media']['tvdbId'];
				$SonarrLookupEndpoint = $SonarrHost.'/series/lookup?term='.$userSearch.'&apikey='.$SonarrAPIKey;

				## Query Sonarr Lookup API
				try {
					$response = Requests::get($SonarrLookupEndpoint, $headers, []);
					if ($response->success) {
						$SonarrLookupObj = json_decode($response->body);
					} else {
						$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query sonarr tags');
						return false;
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr tags: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr tags' . $e->getMessage(), 409);
					return false;
				}
				
				## Check if Sonarr ID Exists
				if (empty($SonarrLookupObj[0]->id)) {
					$this->setResponse(409, 'TV Show not in Sonarr database');
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: TV Show not in Sonarr database.', 'SYSTEM');
					return false;
				}
			
				## Set Sonarr Series Endpoint
				$SeriesID = $SonarrLookupObj[0]->id;
				$SonarrSeriesEndpoint = $SonarrHost.'/series/'.$SeriesID.'?apikey='.$SonarrAPIKey;
			
				## Query Sonarr Series API
				try {
					$response = Requests::get($SonarrSeriesEndpoint, $headers, []);
					if ($response->success) {
						$SonarrSeriesObj = json_decode($response->body);
					} else {
						$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query sonarr series');
						return false;
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr series' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr series' . $e->getMessage(), 409);
					return false;
				}

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
				$this->REST($SonarrCommandEndpoint, $SonarrSearchPostData, 'POST'); // Send Scan Command to Sonarr
				} else if ($Search == "searchX") {
				$SonarrEpisodeEndpoint = $SonarrHost."/episode/?seriesId=".$SeriesID."&apikey=".$SonarrAPIKey; // Set Sonarr URI
				try {
					$response = Requests::get($SonarrEpisodeEndpoint, $headers, []);
					if ($response->success) {
						$Episodes = json_decode($response->body, true);
					} else {
						$this->setResponse(409, 'Sonarr Throttling Plugin - Error: Unable to query sonarr episode data');
						return false;
					}
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr episode data: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', 'Sonarr Throttling Plugin - Error: Unable to query sonarr episode data' . $e->getMessage(), 409);
					return false;
				}

				foreach ($Episodes as $Key => $Episode) {
					if ($Episode['seasonNumber'] != "0" && $Episode['hasFile'] != true) {
					$EpisodesToSearch[] = $Episode['id'];
					}
				}
				$SonarrSearchPostData['name'] = "EpisodeSearch";
				$SonarrSearchPostData['episodeIds'] = array_slice($EpisodesToSearch,0,$EpisodeSearchCount);
				$SonarrSearchPostData = json_encode($SonarrSearchPostData);
				$this->REST($SonarrCommandEndpoint, $SonarrSearchPostData, 'POST'); // Send Scan Command to Sonarr
				}
			
				## Submit data back to Sonarr
				$SonarrSeriesJSON = json_encode($SonarrSeriesObj); // Convert back to JSON
				$SonarrSeriesPUT = $this->REST($SonarrSeriesEndpoint, $SonarrSeriesJSON, 'PUT'); // POST Data to Sonarr
				echo json_encode($SonarrSeriesObj, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); // Echo Result
				http_response_code(201);
				
				## Print Return Data
				print_r($SonarrSeriesObj);
				
			} else {
				$this->setResponse(200, 'Not a TV Show Request.');
				return true;
			}

		} else {
			$this->setResponse(200, 'TEST SUCCESSFUL');
			$this->writeLog('info', 'Sonarr Throttling Plugin - Overseerr Webhook Test Received.', 'SYSTEM');
			return true;
		}
	}





	public function REST($Uri, $JsonData, $Method) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $Uri);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($JsonData)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $Method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$JsonData);
		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);
		return $response;
	}







}