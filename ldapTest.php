<?php
session_start();
function decodeSID($value)
{
    # revision - 8bit unsigned int (C1)
    # count - 8bit unsigned int (C1)
    # 2 null bytes
    # ID - 32bit unsigned long, big-endian order
    $sid = @unpack('C1rev/C1count/x2/N1id', $value);
    $subAuthorities = [];
    if (!isset($sid['id']) || !isset($sid['rev'])) {
        throw new \UnexpectedValueException(
            'The revision level or identifier authority was not found when decoding the SID.'
        );
    }

    $revisionLevel = $sid['rev'];
    $identifierAuthority = $sid['id'];
    $subs = isset($sid['count']) ? $sid['count'] : 0;

    // The sub-authorities depend on the count, so only get as many as the count, regardless of data beyond it
    for ($i = 0; $i < $subs; $i++) {
        # Each sub-auth is a 32bit unsigned long, little-endian order
        $subAuthorities[] = unpack('V1sub', hex2bin(substr(bin2hex($value), 16 + ($i * 8), 8)))['sub'];
    }

    # Tack on the 'S-' and glue it all together...
    return 'S-'.$revisionLevel.'-'.$identifierAuthority.implode(
        preg_filter('/^/', '-', $subAuthorities)
    );
}

if(isset($_POST['username']) && isset($_POST['password'])){

    $adServer = "ldap://WIN-DR1PJ43FVJ3.TylerMoak.com";
	
    $ldap = ldap_connect($adServer);
    $username = $_POST['username'];
    $password = $_POST['password'];

    $ldaprdn = 'TylerMoak' . "\\" . $username;

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $bind = @ldap_bind($ldap, $ldaprdn, $password);
	
    if ($bind) {
        $filter="(sAMAccountName=$username)";
        $result = ldap_search($ldap,"dc=TylerMoak,dc=COM",$filter);
        ldap_sort($ldap,$result,"sn");
        $info = ldap_get_entries($ldap, $result);
        for ($i=0; $i<$info["count"]; $i++)
        {
            if($info['count'] > 1)
                break;              
            $_SESSION["USER_SID"]= decodeSID($info[$i]["objectsid"][0]);
			echo "<p>Member of <pre>";
			var_dump($info[$i]["memberof"]);
            echo "</pre></p>";
            echo "<p>You are accessing <strong> ". $info[$i]["sn"][0] .", " . $info[$i]["givenname"][0] ."</strong><br /> (" . $info[$i]["samaccountname"][0] .")</p>\n";
            echo '<pre>';
            var_dump($info);
            echo '</pre>';
            $userDn = $info[$i]["distinguishedname"][0]; 
        }
        @ldap_close($ldap);
    } else {
        $msg = "Invalid email address / password";
        echo $msg;
    }

}else{
?>
    <form action="#" method="POST">
        <label for="username">Username: </label>
        <input id="username" type="text" name="username" />
        <label for="password">Password: </label>
        <input id="password" type="password" name="password" />
        <input type="submit" name="submit" value="Submit" />
    </form>
    <?php } 

$sidOrig= "S-1-5-21-555290445-4143228776-974942040-1000";

$ldap_dn="CN=Administrator,CN=Users,DC=TylerMoak,DC=com";
$ldap_password = "51bd-baf";
$adServer = "ldap://WIN-DR1PJ43FVJ3.TylerMoak.com";
$ldap_con = ldap_connect($adServer);
ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0);


$bind = ldap_bind($ldap_con, $ldap_dn, $ldap_password);
if ($bind) {        
    $filter ="(sAMAccountName=Revan Orion)";
    echo $filter;
    $result=ldap_search($ldap_con,"DC=TylerMoak,DC=com",$filter) or exit("unable to search");
    echo $result;
    //$entries = ldap_get_entries($ldap_con,$result);   
    
    //$count = countValues($entries[0]["memberof"], "Users");
    //echo $count;
    
    echo "<pre>";
    //print_r ($entries[0]["memberof"]);
    echo "</pre>";
   // echo decodeSID($entries[0]["objectsid"][0]);
    
    
}
else{
    echo "Nope ";
}

function countValues($arr, $search)
{
    foreach ($arr as &$value) {
        if (substr_count($value, $search)>=1)
            return 1;
    }    
    return 0;
}

?>
