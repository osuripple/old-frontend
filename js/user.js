$(document).ready(function() {
	if (typeof UserID == "undefined" || typeof APIToken == "undefined" || typeof Mode == "undefined") {
		return;
	}
	getScores("best");
	getScores("recent");
});

var currentPage = {
	best: 1,
	recent: 1,
};

var scores = {};

function getScores(type) {
	$.getJSON("/api/v1/users/scores/" + type, {
		token: APIToken,
		id: UserID,
		l: 20,
		p: currentPage[type],
		mode: Mode,
	}, function(data) {
		$("#" + type + "-plays-table .col-plays-el").last().remove();
		if (data.code != 200) {
			alert("Whoops! We had an error while trying to show scores for this user :(");
		}
		var tb = $("#" + type + "-plays-table");
		$.each(data.scores, function(k, v) {
			scores[k] = v;
			var imemi = "<div class='col-plays-el'>";
			imemi += '<div class="col-md-1">Tua Madre</div>';
			imemi += '<div class="col-md-' + (Mode == 0 ? "7" : "8") + '">' + v.beatmap.song_name + '</div>';
			imemi += '<div class="col-md-1">' + Math.round(v.accuracy * 100) / 100 + "%</div>";
			imemi += '<div class="col-md-2">' + v.score + '</div>';
			if (Mode == 0) {
				imemi += '<div class="col-md-1">' + v.pp + '</td>';
			}
			imemi += "</div>";
			tb.append(imemi);
		});
	});
}
