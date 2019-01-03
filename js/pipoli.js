$(document).ready(function() {
	$("#pipoli").append("<div><i class='fa fa-circle-o-notch fa-spin fa-3x'></i></div><div>Loading Celeste Pipoli</div>");
	$.get("https://status.ripple.moe/backend/status_data.php", function(data) {
		$("#pipoli").html("");
		for(var key in data) {
			if (!data.hasOwnProperty(key) || data[key].secondary) continue;
			$("#pipoli").append(
				`<div class="col-lg-3 col-md-4">
					<div class="panel panel-${data[key].up ? 'green' : 'red'}">
						<div class="panel-heading">
							<div class="row">
								<div class="col-xs-3"><i class="fa ${data[key].up ? 'fa-check-square' : 'fa-exclamation-triangle'} fa-5x"></i></div>
								<div class="col-xs-9 text-right">
<!-- KAMEHAMEHA!!!!  ))))) -->		<div class="huge">${escapeHtml(key)}</div>
									<div>${data[key].up ? 'Up' : 'Down'}</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				`)
		}
	})
})