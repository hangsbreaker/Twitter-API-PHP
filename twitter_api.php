<?php
ini_set('max_execution_time', 1600);
/* Register and create app in
*	https://apps.twitter.com/
*	Documentation:
*	https://dev.twitter.com/rest/reference/get/search/tweets
*	Contoh untuk mendapatkan tweet:
*	twitter_api.php?count=10&page=10&lang=id&q=quote%20-RT
*/
// Replace with your token and consumer key from apps.twitter.com
$token = "<your token>";
$token_secret = "<your token_secret>";
$consumer_key = "<your consumer_key>";
$consumer_secret = "<your consumer_secret>";
$host = 'api.twitter.com';
$method = 'GET';
//$path = '/1.1/statuses/user_timeline.json'; // api call path
$path = '/1.1/search/tweets.json'; // api call path search
$max_id="";
$since_id=0;$since_id_new=0;
// also not necessary, but twitter's demo does this too
function add_quotes($str) { return '"'.$str.'"'; }
$count = $_GET['count'];
$page = $_GET['page'];
$dtquery = $_GET['q'];
$lang = $_GET['lang'];
$awal = microtime(true);$bar=1;
echo "<!-- Progress bar holder -->
		<div id='progress' style='width:100%;'></div><br>";
$total = $page;
for($i=0;$i<$page;$i++){
	echo '<hr>Page '.$i.' ----------'.$max_id.'<hr>';
	if($max_id != ""){
		$query = array( // query parameters
			//'screen_name' => 'Hangs_',
			'q' => $dtquery,
			'count' => $count,
			'lang' => $lang,
			//'since_id' => $max_id-$count,
			'max_id' => $max_id
			//'result_type' => 'mixed'
			//'until' => '2016-09-17'
		);
	}else{
		$query = array( // query parameters
			'q' => $dtquery,
			'count' => $count,
			'lang' => $lang
		);
	}
	$oauth = array(
		'oauth_consumer_key' => $consumer_key,
		'oauth_token' => $token,
		'oauth_nonce' => (string)mt_rand(), // a stronger nonce is recommended
		'oauth_timestamp' => time(),
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_version' => '1.0'
	);
	$oauth = array_map("rawurlencode", $oauth); // must be encoded before sorting
	$query = array_map("rawurlencode", $query);
	$arr = array_merge($oauth, $query); // combine the values THEN sort
	asort($arr); // secondary sort (value)
	ksort($arr); // primary sort (key)
	// http_build_query automatically encodes, but our parameters
	// are already encoded, and must be by this point, so we undo
	// the encoding step
	$querystring = urldecode(http_build_query($arr, '', '&'));
	$url = "https://$host$path";
	// mash everything together for the text to hash
	$base_string = $method."&".rawurlencode($url)."&".rawurlencode($querystring);
	// same with the key
	$key = rawurlencode($consumer_secret)."&".rawurlencode($token_secret);
	// generate the hash
	$signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));
	// this time we're using a normal GET query, and we're only encoding the query params
	// (without the oauth params)
	$url .= "?".http_build_query($query);
	$url=str_replace("%25","%",str_replace("&amp;","&",$url)); //Patch by @Frewuill
	$oauth['oauth_signature'] = $signature; // don't want to abandon all that work!
	ksort($oauth); // probably not necessary, but twitter's demo does it
	$oauth = array_map("add_quotes", $oauth);
	// this is the full value of the Authorization line
	$auth = "OAuth " . urldecode(http_build_query($oauth, '', ', '));
	// if you're doing post, you need to skip the GET building above
	// and instead supply query parameters to CURLOPT_POSTFIELDS
	$options = array( CURLOPT_HTTPHEADER => array("Authorization: $auth"),
					  //CURLOPT_POSTFIELDS => $postfields,
					  CURLOPT_HEADER => false,
					  CURLOPT_URL => $url,
					  CURLOPT_RETURNTRANSFER => true,
					  CURLOPT_SSL_VERIFYPEER => false);
	// do our business
	$feed = curl_init();
	curl_setopt_array($feed, $options);
	$json = curl_exec($feed);
	curl_close($feed);
	$twitter_data = json_decode($json);
	//print it out echo $url;
	//echo '<pre>',print_r($twitter_data),'</pre>';
	$n=$i*$count;
	if(count($twitter_data->statuses)>0){
		foreach($twitter_data->statuses as $items){
			echo ++$n.". ".$items->created_at." __ ".$items->user->screen_name."<br />";
			echo $items->id_str."<br>";
			echo $items->text."<br/>";
			echo "<br/>";
			$max_id = $items->id_str;
		}
	}
	$since_id = $twitter_data->search_metadata->since_id_str;
	//$max_id = $twitter_data->search_metadata->max_id_str;
	//$max_id = substr($max_id,0,-4).((int)substr($max_id,-4)-$count);
	//echo '<pre>',print_r($twitter_data->search_metadata),'</pre>';
	
	
	//======= Calculate the percentation
	$percent = (intval($bar/$total * 100)>=100?100:(intval($bar/$total * 100)-1))."%";
	// Javascript for updating the progress bar and information
	echo '<script language="javascript">
	document.getElementById("progress").innerHTML="<div class=\"progress\"><div id=\"progressval\" style=\"padding-top:5px;padding-bottom:5px;background:#3498db;color:#fff;font-weight:bold;text-align:center;width:'.$percent.'\">'.$percent.' Complete</div></div>";
	</script>';
	// This is for the buffer achieve the minimum size in order to flush data
	echo str_repeat(' ',1024*64);
	// Send output to browser immediately
	flush();
	// Sleep one second so we can see the delay
	//sleep(1);
	$bar++;
}
// Tell user that the process is completed
$akhir = microtime(true);
$lama = $akhir - $awal;
echo '<script language="javascript">document.getElementById("progressval").innerHTML="Process Complete. Execution time: '.(round($lama,2)).' second";</script>';
?>
