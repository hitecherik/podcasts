<?php
	date_default_timezone_set("Europe/London");
	ini_set("user_agent", "hitecherik");

	function predictNextDate($xml) {
    	// $xml = simplexml_load_file($url);
    	$times = [];
    	$sum_differences = 0;
    	
    	foreach ($xml->channel->item as $item) {
        	array_push($times, strtotime($item->pubDate));
    	}

		for ($i = 0; $i < (count($times)-1); $i++) {
        	$difference = floor(($times[$i] - $times[$i+1]) / (60 * 60 * 24));
			$sum_differences += $difference;
    	}
    	
    	$average = round($sum_differences / (count($times)-1));
    	$prediction = strtotime($xml->channel->item[0]->pubDate . " + $average days");
    	// return date("l jS F Y", $prediction);
		return $prediction;
	}
	
	function getRelativeDate($prediction) {
		$now = (int) date("U", time());
		$difference = floor(($prediction - $now) / (3600 * 24));

		if ($difference > 0) {
			return "in $difference days";
		} elseif ($difference < 0) {
			return "$difference days ago";
		}

		return "today";
	}

	function getPodcastArtwork($file) {
		$pattern = "/\\<itunes:image.+?href=\"(?P<url>.+?)\"/";
		preg_match($pattern, $file, $matches);

		return $matches["url"];
	}

	function formatPodcast($url, $mobile) {
		$file = file_get_contents($url);
		$xml = simplexml_load_string($file);
		$prediction = predictNextDate($xml);
		$title = $xml->channel->title;
		$absolute_date = date("l jS F", $prediction); 
		$relative_date = getRelativeDate($prediction);
		$artwork = getPodcastArtwork($file);
		
		if (!$mobile) {
			return "<tr><td><img src='$artwork' alt='$title' width='100px'></td><td>$title</td><td>$relative_date</td><td>$absolute_date</td></tr>";
		}

		return "<tr><td><img src='$artwork' alt='$title' width='100px'></td><td>$relative_date</td></tr>";
	}

	/* echo predictNextDate("https://www.relay.fm/cortex/feed");
	echo "<br>";
	echo predictNextDate("http://www.hellointernet.fm/podcast?format=rss");
	echo "<br>";
	echo predictNextDate("http://www.relay.fm/ungeniused/feed");
	echo "<br>";
	echo predictNextDate("http://feeds.megaphone.fm/PSM7954412883"); */

	$podcasts = ["https://www.relay.fm/cortex/feed", "http://www.hellointernet.fm/podcast?format=rss", "https://www.relay.fm/ungeniused/feed", "http://www.bbc.co.uk/programmes/p02pc9pj/episodes/downloads.rss", "http://feeds.megaphone.fm/PSM7954412883", "https://www.relay.fm/bonanza/feed", "https://www.relay.fm/liftoff/feed"];
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
						echo formatPodcast($podcast, false);
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
						echo formatPodcast($podcast, true);
					}
				?>
				
				<tr>
					<td colspan="2" class="copyright">Copyright &copy; <a href="http://hitecherik.net" target="_blank">Alexander Nielsen</a>, 2016. Licenced under a <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank">CC BY 4.0 Licence</a>.</td>
				</tr>
			</tbody>
		</table>
	</body>
</html>
