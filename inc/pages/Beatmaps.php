<?php

class Beatmaps {
	const PageID = 37;
	const URL = 'Beatmaps';
	const Title = 'Ripple - Carroponte';

	public function P() {
		echo('<iframe width="560" height="315" src="https://www.youtube.com/embed/G_QfYsmNIHQ?autoplay=1" frameborder="0" allowfullscreen></iframe><br>');
		for ($i=0; $i < 100; $i++) {
			echo '<h3 class="carroponte" hidden>O-oooooooooo-AAAAE-A-A-I-A-U-JO-oooooooooooo-AAE-O-A-A-U-U-A-E-eee-ee-eee-AAAAE-A-E-I-E-A-JO-ooo-oo-oo-oo-EEEEO-A-AAA-AAAA</h3>';
		}
		/*
		$beatmaps = $GLOBALS["db"]->fetchAll("SELECT* FROM beatmaps WHERE ranked >= 2 GROUP BY beatmapset_id ORDER BY latest_update DESC LIMIT 51");
		$c = 0;
		P::GlobalAlert();
		echo '
		<div id="content">
			<div align="center">
				<h1><i class="fa fa-music"></i> Beatmaps</h1>


				<div class="container">
				';

					foreach ($beatmaps as $n => $beatmap) {
						if ($c == 0) {
							echo '<div class="row"> <!-- row start -->
							';
						}
						$c++;
						$songData = [];
						preg_match("#(.+) - (.+) \[(?:.+)\]#i", $beatmap["song_name"], $songData);

						$img = "images/cache/cover_".$beatmap["beatmapset_id"].".jpg";
						if (!file_exists($img)) {
							try {
								$peppyImage = @file_get_contents("http://assets.ppy.sh/beatmaps/".$beatmap["beatmapset_id"]."/covers/cover.jpg");

								if ($peppyImage === FALSE || strlen($peppyImage) == 0) {
									$anotherPeppyImage = @file_get_contents("http://b.ppy.sh/thumb/".$beatmap["beatmapset_id"]."l.jpg");
									if ($anotherPeppyImage === FALSE || strlen($anotherPeppyImage) == 0) {
										throw new Exception();
									} else {
										file_put_contents("images/cache/cover_".$beatmap["beatmapset_id"].".jpg", $anotherPeppyImage);
									}
								} else {
									file_put_contents("images/cache/cover_".$beatmap["beatmapset_id"].".jpg", $peppyImage);
								}
							} catch (Exception $e) {
								file_put_contents("images/cache/cover_".$beatmap["beatmapset_id"].".jpg", file_get_contents("images/nocover.png"));
							}
						}

						echo '<div class="col-sm-4">
							<audio id="audio_'.$beatmap["beatmapset_id"].'" src="http://b.ppy.sh/preview/'.$beatmap["beatmapset_id"].'.mp3"></audio>
							<div class="card hovercard">
								<div class="cardheader" style="background: url(\''.$img.'\');"></div>';
								if ($beatmap["ranked_status_freezed"] == 1)
									echo '<div class="corner-ribbon">Ranked on Ripple!</div>';
								echo '<div class="info">
									<div class="play-download">
										<a class="btn btn-circle btn-pink btn-sm" onclick="play('.$beatmap["beatmapset_id"].')"><i id="icon_'.$beatmap["beatmapset_id"].'" class="fa fa-play"></i></a>
										<a class="btn btn-circle btn-orange btn-sm" target="_blank" href="http://m.zxq.co/'.$beatmap["beatmapset_id"].'.osz"><i class="fa fa-download"></i></a>
									</div>
									<div class="desc">
										'.$songData[1].'
									</div>
									<div class="title">
										'.$songData[2].'
									</div>
									<div class="desc">
										Mapped by Someone
									</div>
								</div>
								<div class="bottom">
									<a class="diff-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Easy"><img src="images/std-icon.png"></a>
									<img src="images/taiko-icon.png">
									<img src="images/ctb-icon.png">
									<img src="images/mania-icon.png">
								</div>
							</div>
						</div>
						';

						if ($c == 3 || $n == count($beatmaps)-1) {
							$c = 0;
							echo '</div> <!-- row end -->
							';
						}
					}



			echo '</div>
			</div>
		</div>';*/
	}
}
