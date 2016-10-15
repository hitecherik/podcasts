<?php
	function predictNextDate($url) {
    	$xml = simplexml_load_file($url);
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
    	return date("l jS F Y", $prediction);
	}

	echo predictNextDate("https://www.relay.fm/cortex/feed");
	echo "<br>";
	echo predictNextDate("http://www.hellointernet.fm/podcast?format=rss");
	echo "<br>";
	echo predictNextDate("http://www.relay.fm/ungeniused/feed");
	echo "<br>";
	echo predictNextDate("http://feeds.megaphone.fm/PSM7954412883");
?>
