/* This file is loaded when Organizr is loaded */
// Load once Organizr loads
$('body').arrive('#activeInfo', {onceOnly: true}, function() {
	sonarrThrottlingPluginLaunch();
});
// FUNCTIONS
function sonarrThrottlingPluginLaunch(){
	organizrAPI2('GET','api/v2/plugins/sonarrthrottling/launch').success(function(data) {
		try {
			var menuList = `<li><a href="javascript:void(0)" onclick="togglesonarrThrottlingPlugin();"><i class="fa fa-tv fa-fw"></i> <span lang="en">Throttled TV Shows</span></a></li>`;
			$('.append-menu').after(menuList);
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function togglesonarrThrottlingPlugin(){
	let div = `
		<div class="panel bg-org panel-info" id="sonarrThrottling-area">
			<div class="panel-heading">
				<span lang="en">Throttled TV Shows</span>
			</div>
			<div class="panel-body">
				
				<div id="sonarrThrottlingTable">
					<div class="white-box m-b-0">
						<h2 class="text-center loadingsonarrThrottling" lang="en"><i class="fa fa-spin fa-spinner"></i></h2>
						<div class="row">
							<div class="col-lg-12">
								<select class="form-control" name="tvShows" id="tvShows">
									<option value="">Choose a TV Show</option>
								</select><br>
							</div>
						</div>
						<div class="table-responsive sonarrThrottlingTableList hidden" id="sonarrThrottlingTableList">
							<table class="table color-bordered-table purple-bordered-table text-left">
								<thead>
									<tr>
										<th width="10%">#</th>
										<th width="25%">Name</th>
										<th width="65%">Progress</th>
									</tr>
								</thead>
								<tbody id="sonarrThrottling"></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		`;
	swal({
		content: createElementFromHTML(div),
		button: false,
		className: 'orgAlertTransparent',
	});
	sonarrThrottlingPluginLoadShows();
}
function sonarrThrottlingPluginLoadShows(){
	organizrAPI2('GET','api/v2/plugins/sonarrthrottling/throttled').success(function(data) {
		$('.loadingsonarrThrottling').remove();
		try {
			$.each(data.response.data, function(_, tvShow) {
				sonarrThrottlingPluginLoadShowItem(tvShow);
			});
			const thtml = $("#sonarrThrottling ");
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		$('.loadingPlexLibraries').remove();
		OrganizrApiError(xhr);
	});
	const thtml = $("#sonarrThrottling ");
	thtml.append('<script>sonarrThrottlingPluginOnSelect();</script>');
}

function sonarrThrottlingPluginLoadShowItem(tvShow){
	const thtml = $("#sonarrThrottling ");
	var title = tvShow.Title;
	var tvdbId = tvShow.tvdbId;
	var imageUrl = tvShow.ImageUrl;
	var progress = tvShow.Progress;
	var progressFriendly = Math.round(tvShow.Progress);
	var episodeCount = tvShow.EpisodeCount;
	var episodeFileCount = tvShow.EpisodeFileCount;
	let libItem = `
		<tr class="tvShow hidden" id="${tvdbId}">
			<td><img src="${imageUrl}" alt="image" width="96" height="128"></td>
			<td>${title}</td>
			<td>
				<div class="progress position-relative">
    				<div class="progress-bar" role="progressbar" style="width: ${progress}%" aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100"></div>
    				<span>${episodeFileCount} / ${episodeCount} (${progressFriendly}%)</span>
				</div>
		  	</td>
		</tr>
	`;
	thtml.append(libItem);
	const tvhtml = $("#tvShows ");
	tvhtml.append('<option value="'+tvdbId+'">'+title+'</option>');
}

function sonarrThrottlingPluginOnSelect() {
    $('#tvShows').change(function () {
		Array.from(document.getElementsByClassName('tvShow')).forEach(
			function(element, index, array) {
				element.style.display = "none";
			}
		);
		if($('.sonarrThrottlingTableList').hasClass('hidden')){
			$('.sonarrThrottlingTableList').removeClass('hidden');
		}
		document.getElementById(this.value).style.display = "table-row";
		if($('.tvShow').hasClass('hidden')){
			$('.tvShow').removeClass('hidden');
		}
    });
}
// EVENTS and LISTENERS
