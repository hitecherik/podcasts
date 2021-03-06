<?php
	date_default_timezone_set("Europe/London");
	ini_set("user_agent", "hitecherik");

	function predictNextDate($xml) {
    	$times = [];
    	$sum_differences = 0;
    	
    	foreach ($xml->channel->item as $item) {
        	array_push($times, strtotime($item->pubDate));
    	}

		for ($i = 0; $i < (count($times)-1); $i++) {
        	// $difference = floor(($times[$i] - $times[$i+1]) / (60 * 60 * 24));
			// $sum_differences += $difference;
			$sum_differences += $times[$i] - $times[$i+1];
    	}
    	
    	$average = round($sum_differences / (count($times)-1));
		$average_in_days = round($average / (60*60*24));
    	$prediction = strtotime($xml->channel->item[0]->pubDate . " + $average_in_days days");
		return $prediction;
	}
	
	function getRelativeDate($prediction) {
		$now = (int) date("U", time());
		$difference = round(($prediction - $now) / (3600 * 24));
		$time_measure = (abs($difference) == 1? "day" : "days");

		if ($difference > 0) {
			return "in $difference $time_measure";
		} elseif ($difference < 0) {
			$difference  *= -1;
			return "$difference $time_measure ago";
		}

		return "today";
	}

	function getPodcastArtwork($file) {
		$pattern = "/\\<itunes:image.+?href=\"(?P<url>.+?)\"/";
		preg_match($pattern, $file, $matches);

		return $matches["url"];
	}

	function podcastObject($url) {
		$file = file_get_contents($url);
		$xml = simplexml_load_string($file);
		$prediction = predictNextDate($xml);
		$title = $xml->channel->title;
		$absolute_date = date("l jS F", $prediction); 
		$relative_date = getRelativeDate($prediction);
		$artwork = getPodcastArtwork($file);

		return array(
			"formats"=>["<tr><td><img src=\"$artwork\" alt=\"$title\" width=\"100px\"></td><td>$title</td><td>$relative_date</td><td>$absolute_date</td></tr>", "<tr><td><img src=\"$artwork\" alt=\"$title\" width=\"100px\"></td><td>$relative_date</td></tr>"],
			"prediction"=>$prediction
		);
	}

	function compareTimes($a, $b) {
		if ($a["prediction"] == $b["prediction"]) {
			return 0;
		}

		return ($a["prediction"] < $b["prediction"]) ? -1 : 1;
	}
	
	$db = new SQLite3("log.db");
	$date = date("Y-m-d");
	$podcasts = [];
	$podcastsJSON = $db->querySingle("SELECT podcasts FROM log WHERE date='{$date}'");
	$from_db = "no";  // debug
	
	if ($podcastsJSON && !isset($_GET["force_update"])) {
		$podcasts = json_decode($podcastsJSON, true);
		$from_db = "yes";
	} else {
		$podcastURLs = ["https://www.relay.fm/cortex/feed", "http://www.hellointernet.fm/podcast?format=rss", "https://www.relay.fm/ungeniused/feed", "http://www.bbc.co.uk/programmes/p02pc9pj/episodes/downloads.rss", /*"http://feeds.megaphone.fm/PSM7954412883",*/ "https://www.relay.fm/mixedfeelings/feed", "https://www.relay.fm/bonanza/feed", "https://www.relay.fm/liftoff/feed", "http://twobitgeeks.libsyn.com/rss"];

		foreach ($podcastURLs as $podcastURL) {
			array_push($podcasts, podcastObject($podcastURL));
		}

		usort($podcasts, "compareTimes");
		
		//$podcastsJSON = str_replace("'", "\\'", json_encode($podcasts));
		//echo $podcastsJSON;
		$podcastsJSON = json_encode($podcasts);
		$db->exec("DELETE FROM log; INSERT INTO log VALUES ('{$date}', '{$podcastsJSON}')");
	}
?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width">
		<link href="favicon.png" rel="shortcut icon">

		<title>Podcasts</title>
		
		<link href="tables-min.css" rel="stylesheet">
		<style>
			table {
				margin: auto;
				font-family: Helvetica, sans-serif;
			}

			a {
				color: #005ab3;
				text-decoration: none;
			}

			a:hover {
				border-bottom: 1px solid currentColor;
			}

			.mobile-screen {
				display: none;
			}
			
			.copyright {
				font-size: 0.8em !important;
				line-height: 1.5em;
			}

			@media (max-width: 649px) {
				.mobile-screen {
					width: 95%;
					display: table;
				}

				.wide-screen {
					display: none;
				}
			}
		</style>
		
		<script>
			console.log("From script: <?php echo $from_db; ?>.");
		</script>
	</head>
	<body>
		<table class="pure-table pure-table-horizontal wide-screen">
			<thead>
				<tr>
					<th colspan="2">Podcast</th>
					<th colspan="2">Prediction</th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach ($podcasts as $podcast) {
						echo $podcast["formats"][0];
					}
				?>

				<tr>
					<td colspan="4" class="copyright">Copyright &copy; <a href="http://hitecherik.net" target="_blank">Alexander Nielsen</a>, 2016. Licenced under a <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank">CC BY 4.0 Licence</a>.</td>
				</tr>
			</tbody>
		</table>

		<table class="pure-table pure-table-horizontal mobile-screen">
			<thead>
				<th>Podcast</th>
				<th>Prediction</th>
			</thead>
			<tbody>
				<?php 
					foreach ($podcasts as $podcast) {
						echo $podcast["formats"][1];
					}
				?>
				
				<tr>
					<td colspan="2" class="copyright">Copyright &copy; <a href="http://hitecherik.net" target="_blank">Alexander Nielsen</a>, 2016. Licenced under a <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank">CC BY 4.0 Licence</a>.</td>
				</tr>
			</tbody>
		</table>
	</body>
</html>
