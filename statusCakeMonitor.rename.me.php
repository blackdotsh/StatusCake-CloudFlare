<?php
// created by blackdotsh @ github
// MIT Licnese


//global vars
$SS_key=""; // your statuscake key
$SS_username=""; // your statuscake username


/*$CF_domains expects an associated array with domain as key;

backup IP,
1 to proxy through cloudflare 0 otherwise
as value

for example:
$CF_domains = array( "google.com" => "8.8.8.8,1" );

sets the domain google.com's backup IP adress to 8.8.8.8 and enabling cloudflare proxy (orange cloud)

*/

$CF_domains = array(
			"google.com" => "8.8.8.8,0",
			"www.google.com" => "8.8.4.4,1"
		);

//API key to your cloudflare account
$CF_key = "";

//Cloudflare email address
$CF_email= "";


//end of global vars
//////////////////////////////////////////////////////////
//global functions

//checks if the domain exists in $CF_domains and that it's up via HTTP
function checkDomain ($domains, $domain){
	foreach ( $domains as $key => $value ){
		if (strcmp($key, $domain) == 0 ){
			$vars=explode(",",$domains["$domain"]);
			$ip=$vars[0];
			if (checkHost($ip)) {
				return true;
			} else {
				echo "backup server unreachable via HTTP\n";
				return false;
			}
		}
	}
	return false;
}


//checks if host returns "HTTP/1.1 200 OK" within a reasonable time frame
function checkHost ($host) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $host);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch,CURLOPT_TIMEOUT,5);
	$result=curl_exec($ch);

	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($result, 0, $headerSize);
	if (strpos($header, "HTTP/1.1 200 OK") !== false){
		return TRUE;	
	} else {
		return FALSE;
	}

}

//interacts with CF API to switch to the backup IP
function cfBkup ($domains, $domain, $subdomain, $cfkey, $cfemail){
	$vars=explode(",",$domains["$domain"]);
	//get DNSID
	$ch= curl_init("https://www.cloudflare.com/api_json.html");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$postVars= array('a' => 'rec_load_all', 'tkn' => "$cfkey", 'email' => "$cfemail", 'z' => "$domain");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postVars));
	$results= json_decode(curl_exec($ch), TRUE);
	curl_close($ch);
//	var_dump ($results);
	$dnsList=$results['response']['recs']['objs'];
//	var_dump ($dnsList);	
	$DNSID="";
	for ($i=0; $i < sizeof($dnsList); $i++){
		if ( strcmp($dnsList[$i]['name'], $subdomain) == 0 && strcmp($dnsList[$i]['type'], "A") == 0 ){
			$DNSID=$dnsList[$i]['rec_id'];
			break;
		}  	
	}
	if (strcmp($DNSID, "" != 0)){
//		echo "DNSID: ".$DNSID;
		$ch= curl_init("https://www.cloudflare.com/api_json.html");
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$postVars=array ('a' => "rec_edit", 'tkn' => "$cfkey", 'id' => "$DNSID", 'email' => "$cfemail", 
			'z' => "$domain", 'type' => "A", 'name' => "$subdomain", 'content' => "$vars[0]", 'service_mode' => "$vars[1]", 'ttl' => "1");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postVars));
		$results= json_decode(curl_exec($ch), TRUE);
		//var_dump ($results);
		
		$result=$results['result'];
		echo "result:".$result;
		if (strcmp($result, "success") != 0){
			echo "an error occured in record edit for domain: ".$subdomain;
		} else {
			//maybe write some logs saying it's successful or some sort of notification saying the record has been changed
		}
	} else { echo "error in finding DNSID"; };
}

//end of global functions
////////////////////////////////////////////////////////

if (strcmp(md5($SS_username.$SS_key),$token) != 0){
	//header('Location:  http://speedtest.tele2.net/1000GB.zip');
	echo "token validation failed";
	die;
}

if (!empty($_POST['URL']) && !empty($_POST['Status'])){
	//strip http://
	$checkDomain=str_replace("http://","",urldecode($_POST['URL']));

	echo "checkdomain: ".$checkDomain."\n\n";
	//check to see if $checkURL is in the $CF_domain list and make sure it's a down alert
	if ( checkDomain($CF_domains, $checkDomain) && strcmp($_POST['Status'], "Down") == 0){
		//do more stuff here for subdomains
		$FQDN=explode(".",$checkDomain);
		$FQDN=$FQDN[sizeof($FQDN)-2].".".$FQDN[sizeof($FQDN)-1];
		echo $FQDN;
		cfBkup ($CF_domains, $FQDN, $checkDomain, $CF_key, $CF_email);
	} else { echo "invalid url, alert type, or the backup server is unreachable";};
}else {echo "missing required post variables";}
