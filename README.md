# Sonarr Throttling Organizr Plugin

| :exclamation: Important                                                          |
|:---------------------------------------------------------------------------|
| To add this plugin to Organizr, please add https://github.com/TehMuffinMoo/Organizr-Plugins to the Plugins Marketplace within your Organizr instance. |

#### This plugin allows you to specify a threshold for TV Show sizes and throttles downloads accordingly. It works by configuring a webhook in Overseerr and Tautulli to manage TV Show episode downloading based on if episodes are being watched. Shows with seasons/episodes over a configured threshold will be marked as throttled and only the first X number of episodes will be downloaded. Further episodes will only be downloaded when an event is logged in Tautulli. Using this method prevents large TV Shows from being downloaded for nobody to watch them.
#### This prevents large TV Shows from being downloaded whilst never getting watched. By using this method, there should always be at least X (Default 10) number of available epsiodes ahead of the last watched episode.
- When a TV Show is requested in Overseerr, it's added to Sonarr unmonitored and webhook is triggered. (You need to make sure Overseerr is set to not monitor after adding)
  - If they exceed a configurable threshold (by default this is, 5 seasons or 25 epsisodes) then it will add a tag "throttled" (configurable) to the show in Sonarr and trigger the download of only the first 10 (also configurable) episodes.
   - If they are below threshold, the show is set to a normal monitored state and a full episode scan is started.
  
- A second Webhook is then configured within Tautulli to trigger when content is watched.
  - Webbook checks if it is a TV Show and if it has the throttled tag in Sonarr
  - If throttled tag exists, identify the next unavailable episode and trigger search request
  - If all episodes have downloaded, mark TV show as monitored and remove throttled tag


The default Overseerr Webhook template works fine, this can be modified but you must ensure {{media}}->{{tvdbId}} and {{media}}->{{media_type}} remain populated.

| :exclamation: Important                                                          |
|:---------------------------------------------------------------------------|
| The "Authorization" header must be populated with your Sonarr Throttling Plugin API Token. |


## Overseerr Webhook Example
Webhook URL: `https://OrganizrURL/api/v2/plugins/sonarrthrottling/webhooks/overseerr`

```
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
}
```

## Tautulli Webhook Example
| :memo:        | For Tautulli I would suggest using either the `Playback Start` or `Watched` triggers for the best experience. |
|---------------|:--------------------------------------------------------------------------------------------------------------|

Webhook URL: `https://OrganizrURL/api/v2/plugins/sonarrthrottling/webhooks/tautulli`

### JSON Headers
```
{
	"authorization": "SonarrThrottlingPlugin"
}
```

### JSON Data
This can follow whatever format you like, but you must ensure {tvdbId} and {media_type} remain populated.
```
{
    "action": "{action}",
    "title": "{title}",
    "username": "{username}",
    "mediaType": "{media_type}",
    "tvdbId": "{thetvdb_id}"
}
```

## Screenshots
![plugin-settings-about](https://user-images.githubusercontent.com/51195492/136164479-d06e8eff-6969-43e4-8b0b-92897e2ba142.png)
![plugin-settings](https://user-images.githubusercontent.com/51195492/136164475-ff9f7274-0bef-4368-9865-da9c4dbb4fcd.png)
![plugin-settings-sonarr](https://user-images.githubusercontent.com/51195492/136164481-2aedd17a-f042-431b-b3d8-b6a6b2b4e167.png)
![plugin-modal-dropdown](https://user-images.githubusercontent.com/51195492/136164487-2f33c921-998d-41bd-b0ff-3563ffd04531.png)
![plugin-modal-details](https://user-images.githubusercontent.com/51195492/136164486-9b422d2a-0d63-4b85-bf48-45c7eaf74667.png)
