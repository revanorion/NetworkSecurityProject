<?php
session_start();

function is_ajax() {
  return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

//This checks which ajax post is called.
if (is_ajax()) {
    if(isset($_POST['signup']) && !empty($_POST['username']) && !empty($_POST['password'])) {
        echo registerUser($_POST['username'], $_POST['password']);
    }
    if(isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
        echo loginUser($_POST['username'], $_POST['password']);
    }
    if(isset($_POST['voicePost']) && (!empty($_POST['textValue']) || !empty($_SESSION['image_posts']))) {
        echo postVoice($_POST['textValue'], $_POST['picValue']);
    }
    if(isset($_POST['getPosts'])) {
        echo loadPosts();
    }
    if(isset($_POST['clearUploads'])) {
        echo $_SESSION['image_posts']=null;
    }
    if(isset($_POST['likePost']) && (!empty($_POST['wallSEQ']))){
        echo likePost($_POST['wallSEQ']);
    }
}

function likePost($wallSEQ){
    require_once './php/db_connect.php';
    $userSEQ = $_SESSION['login_user_id'];
    $checkWallExists = "SELECT W.WALL_SEQ FROM WALL W WHERE W.WALL_SEQ = ".$wallSEQ;
    $checkResult = $db->query($checkWallExists);
    if (mysqli_num_rows($checkResult) > 0) {
        $checkLikeWallNotExists = "SELECT W.WALL_SEQ FROM WALL_LIKE W WHERE W.WALL_SEQ = ".$wallSEQ." AND W.USER_SEQ = ".$userSEQ;
        $checkLikeResult = $db->query($checkLikeWallNotExists);
        if (mysqli_num_rows($checkLikeResult) == 0) {
            $insertWallLikeStmt = "INSERT INTO WALL_LIKE (WALL_SEQ, USER_SEQ) VALUES (".$wallSEQ.", ".$userSEQ.")";
            $insertResult = $db->query($insertWallLikeStmt);
            if (mysqli_affected_rows($db) > -1) {
                return loadPost($wallSEQ);
            }
            return "LIKE FAILED! ".$wallSEQ." ".$userSEQ;
        }
        return "ALREADY LIKED!";
    }
    return "WALL POST DOESNT EXIST!";
}

//This will load a single post based off the wall seq
function loadPost($wallSEQ){
    require './php/db_connect.php';
    $userSEQ = $_SESSION['login_user_id'];
    //this will get the wall post plus the user who posted it
    $selectStmt = "SELECT W.WALL_SEQ, W.USER_SEQ, W.STATUS_TEXT, W.CREATED_ON, U.USERNAME FROM WALL W JOIN USERS U ON W.USER_SEQ = U.USER_SEQ WHERE W.WALL_SEQ = ".$wallSEQ;
    $result = $db->query($selectStmt);
    // output data of each row
    $getResults="";
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $imageHTML="";
            $likeCount ="0";
            $likeHTML="";
            //this will get all the images based on the wall post.
            $selectImageStmt = "SELECT I.FILE_SEQ, I.FILE_NAME FROM WALL_FILE WI JOIN FILE I ON WI.FILE_SEQ = I.FILE_SEQ WHERE WI.WALL_SEQ =".$wallSEQ;
            $ImageResult = $db->query($selectImageStmt);

            if (mysqli_num_rows($ImageResult) > 0) {
                while($rowImg = mysqli_fetch_assoc($ImageResult)) {
                    //this will add construct the html elements for each image
                        $imageHTML.="<p><a class='fileThumb' href='".$rowImg["FILE_NAME"]."' target='_blank'><image class='image-post' src='".$rowImg["FILE_NAME"]."'></image></a></p>";
                }
            }
            //this will select the likes of each wall post.
            $selectCountLikes = "SELECT COUNT(WALL_SEQ) AS LIKES FROM WALL_LIKE WHERE WALL_SEQ = ".$wallSEQ;
            $resultCountLikes = $db->query($selectCountLikes);
            //this will check to see if the current user has liked the post. this will determine the checked status of the like button
            $selectLike = "SELECT WALL_SEQ FROM WALL_LIKE WHERE WALL_SEQ = ".$wallSEQ." AND USER_SEQ = ".$userSEQ;
            $resultLike = $db->query($selectLike);
            if (mysqli_num_rows($resultLike) > 0) {
                $likeHTML = "<input data-id=".$wallSEQ." id='like-".$wallSEQ."' class='like' type='checkbox' checked=true />";
            } else{
                $likeHTML ="<input data-id=".$wallSEQ." id='like-".$wallSEQ."' class='like' type='checkbox' />";
            }
            if (mysqli_num_rows($resultCountLikes) > 0) {
                while($rowLikes = mysqli_fetch_assoc($resultCountLikes)) {
                        $likeCount=$rowLikes["LIKES"];
                }
            }

            //this builds the post
            $getResults.= "<div class='col-md-offset-3 col-md-3'>
                        <form class='well'>
                            <p>".$row["USERNAME"]." ".$row["CREATED_ON"]."</p>
                            <p>".$row["STATUS_TEXT"]."</p>
                            ".$imageHTML."
                            <p>
                                <div class='well'>
                                ".$likeHTML."
                                <label for='like-".$wallSEQ."' class='like-label glyphicon glyphicon-thumbs-up'></label>
                                <span class='badge'>".$likeCount."</span>
                                </div>
                            </p>
                        </form>
                    </div>
                    <div class='col-md-2'>
                        <form class='well'>
                            <p> this is a reply</p>
                            <button class='btn btn-default'><span class='glyphicon glyphicon-thumbs-up'></span> <span class='badge'>4</span></button>
                        </form>
                    </div>";
        }
        return $getResults;
    }
    return "Error getting post!";
}


function loadPosts(){
    require_once './php/db_connect.php';
    //this grabs all posts for the when the page loadds
    $selectStmt = "SELECT W.WALL_SEQ, W.USER_SEQ, W.STATUS_TEXT, W.CREATED_ON, U.USERNAME FROM WALL W JOIN USERS U ON W.USER_SEQ = U.USER_SEQ ORDER BY CREATED_ON DESC LIMIT 5 ";
    $result = $db->query($selectStmt);
    // output data of each row
    $getResults="";
    $userSEQ = $_SESSION['login_user_id'];
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $imageHTML="";
            $wallSEQ=$row["WALL_SEQ"];
            $likeCount ="0";
            $likeHTMl="";
            //this selects all images for each wall post
            $selectImageStmt = "SELECT I.FILE_SEQ, I.FILE_NAME FROM WALL_FILE WI JOIN FILE I ON WI.FILE_SEQ = I.FILE_SEQ WHERE WI.WALL_SEQ =".$wallSEQ;
            $ImageResult = $db->query($selectImageStmt);

            if (mysqli_num_rows($ImageResult) > 0) {
                while($rowImg = mysqli_fetch_assoc($ImageResult)) {
                    //this will add construct the html elements for each image
                        $imageHTML.="<p><a class='fileThumb' href='".$rowImg["FILE_NAME"]."' target='_blank'><image class='image-post' src='".$rowImg["FILE_NAME"]."'></image></a></p>";
                }
            }			
            //this will select the likes of each wall post.
            $selectCountLikes = "SELECT COUNT(WALL_SEQ) AS LIKES FROM WALL_LIKE WHERE WALL_SEQ = ".$wallSEQ;
            $resultCountLikes = $db->query($selectCountLikes);
            //this will check to see if the current user has liked the post. this will determine the checked status of the like button
            $selectLike = "SELECT WALL_SEQ FROM WALL_LIKE WHERE WALL_SEQ = ".$wallSEQ." AND USER_SEQ = ".$userSEQ;
            $resultLike = $db->query($selectLike);
            if (mysqli_num_rows($resultLike) > 0) {
                $likeHTML = "<input data-id=".$wallSEQ." id='like-".$wallSEQ."' class='like' type='checkbox' checked=true />";
            } else{
                $likeHTML ="<input data-id=".$wallSEQ." id='like-".$wallSEQ."' class='like' type='checkbox' />";
            }
            if (mysqli_num_rows($resultCountLikes) > 0) {
                while($rowLikes = mysqli_fetch_assoc($resultCountLikes)) {
                        $likeCount=$rowLikes["LIKES"];
                }
            }
            //this builds the post
            $getResults.= "<div class='row'>
                    <div class='col-md-offset-3 col-md-5'>
                        <hr/> </div>
                </div>
                <div id='WALL-SEQ-".$row["WALL_SEQ"]."' class='row'>
                    <div class='col-md-offset-3 col-md-3'>
                        <form class='well'>
                            <p>".$row["USERNAME"]." ".$row["CREATED_ON"]."</p>
                            <p>".$row["STATUS_TEXT"]."</p>
                            ".$imageHTML."
                            <p>
                                <div class='well'>
                                ".$likeHTML."
                                <label for='like-".$wallSEQ."' class='like-label glyphicon glyphicon-thumbs-up'></label>
                                <span class='badge'>".$likeCount."</span>
                                </div>
                            </p>
                        </form>
                    </div>
                    <div class='col-md-2'>
                        <form class='well'>
                            <p> this is a reply</p>
                            <button class='btn btn-default'><span class='glyphicon glyphicon-thumbs-up'></span> <span class='badge'>4</span></button>
                        </form>
                    </div>
                </div>";
        }
        return $getResults;
    }
    return "Error getting posts!";
}


function postVoice($textValue, $pictureUrl){
    
    require_once './php/db_connect.php';
    $userid= $_SESSION['login_user_id'];
    $sid="0";
    if(isset($_SESSION['USER_SID']))
    {
        $sid = $_SESSION['USER_SID'];   
    }
    $insertStmt = "INSERT INTO WALL (USER_SEQ, STATUS_TEXT, USER_SID, CREATED_BY, CREATED_ON) VALUES(".$userid.", '".$textValue."', '".$sid."', '".$sid."', '".date("Y-m-d H:i:s")."')";
    $result = $db->query($insertStmt);



    if (mysqli_affected_rows($db) > -1) {
        $selectWallStmt = "SELECT MAX(WALL_SEQ) as WALL_SEQ FROM WALL";
        $selectWallresult = $db->query($selectWallStmt);
        $wallSeq=mysqli_fetch_assoc($selectWallresult)["WALL_SEQ"];

        $imageHtml="";
        if(!empty($_SESSION['image_posts']))
        {
            foreach($_SESSION['image_posts'] as $file)
            {
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($file['name']);
                $uploadOk=1;
                if (file_exists($target_file)) {
                    echo "{\"error\":\"Sorry, file already exists.\"}";
                    $uploadOk = 0;
                }
                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 0) {
                    echo "{\"error\":\"Sorry, your file wasn't uploaded.\"}";
                    // if everything is ok, try to upload file
                } else {
                    if (!copy($file['target_file'], $target_file)) {
                        echo "{\"Error\":\"Sorry, there was an error uploading your file.\"}";
                    }
                    else{
                        //file uploaded
                        $insertImgStmt="INSERT INTO FILE (FILE_NAME) VALUES ('".$target_file."')";
                        $insertImgresult = $db->query($insertImgStmt);
                        if (mysqli_affected_rows($db) > -1) {
                            $selectImgStmt = "SELECT MAX(FILE_SEQ) as FILE_SEQ FROM FILE";
                            $selectImgresult = $db->query($selectImgStmt);
                            $imgSeq=mysqli_fetch_assoc($selectImgresult)["FILE_SEQ"];
                            $insertWallImageStmt = "INSERT INTO WALL_FILE (FILE_SEQ, WALL_SEQ) VALUES (".$imgSeq.", ".$wallSeq.")";
                            $db->query($insertWallImageStmt);
                            $imageHtml.="<p><a class='fileThumb' href='".$target_file."' target='_blank'><image class='image-post' src='".$target_file."'></image></a></p>";
                            unlink($file['target_file']);
                        }
                    }
                }
            }
            $_SESSION['image_posts']=null;
        }
        //this builds the post
       return "<div id='".$wallSeq."' class='row'>
                    <div class='col-md-offset-3 col-md-5'>
                        <hr/> </div>
                </div>
                <div data-id='".$userid."' class='row'>
                    <div class='col-md-offset-3 col-md-3'>
                        <form class='well'>
                            <p>".$textValue."</p>
                            ".$imageHtml."
                            <p>
                                <button class='btn btn-default'><span class='glyphicon glyphicon-thumbs-up'></span> <span class='badge'>4</span></button>
                            </p>
                        </form>
                    </div>
                    <div class='col-md-2'>
                        <form class='well'>
                            <p> this is a reply</p>
                            <button class='btn btn-default'><span class='glyphicon glyphicon-thumbs-up'></span> <span class='badge'>4</span></button>
                        </form>
                    </div>
                </div>";
    }
    return 0;
}



function registerUser($username, $password){
    require_once './php/db_connect.php';
    $selectStmt = "SELECT USER_SEQ FROM USERS WHERE USERNAME ='".$username."'";
    $result = $db->query($selectStmt);
    if (mysqli_num_rows($result) > 0) {
        return -1;
    }
    else
    {
        $insertStmt= "INSERT INTO USERS (USERNAME, PASSWORD) VALUES ('".$username."', '".password_hash($password, PASSWORD_DEFAULT)."')";
        $result = $db->query($insertStmt);
        if (mysqli_affected_rows($db) > -1) {
            return 1;
        }
        return 0;
    }
}


function loginUser($username, $password){
    /*require_once './php/db_connect.php';
    $selectStmt = "SELECT USER_SEQ, PASSWORD FROM USERS WHERE USERNAME ='".$username."'";
    $result = $db->query($selectStmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if(password_verify($password, $row["PASSWORD"])){
            //this will store the session vars
            $_SESSION["login_user"] = $username;
            $_SESSION["login_user_id"] = $row["USER_SEQ"];
            $_SESSION["image_posts"] = null;
            echo "your in".$_SESSION["login_user"];
        }
        else{
            echo "try again";
        }
    }
    else
    {
        echo "User doesnt exist";
    }*/
    $ldap = connectLDAP($username, $password);

    if ($ldap!=0){
        //$sid = getAccountSID($ldap,$username);
        
        
        $filter ="(sAMAccountName=".$username.")";
        $result=ldap_search($ldap,"DC=TylerMoak,DC=com",$filter) or exit("unable to search");
        $entries = ldap_get_entries($ldap_con,$result);
        $sid= decodeSID($entries[0]["objectsid"][0]);
    
        
        
        
        
        $_SESSION["USER_SID"]= $sid;
        $_SESSION["login_user"] = getAccountName($ldap, $sid);
        $_SESSION["login_user_id"] = 1;
        $_SESSION["image_posts"] = null;        
        //add user rights here        
        @ldap_close($ldap);
    }
    else {
        $msg = "Invalid email address / password";
        echo $msg;
    } 
    
    /*
    $adServer = "ldap://WIN-DR1PJ43FVJ3.TylerMoak.com";	
    $ldap = ldap_connect($adServer);   
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
            $_SESSION["login_user"] = $info[$i]["samaccountname"][0];
            $_SESSION["login_user_id"] = 1;
            $_SESSION["image_posts"] = null;        
            //add user rights here
        }
        @ldap_close($ldap);
    } else {
        $msg = "Invalid email address / password";
        echo $msg;
    }     
    */
}



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



function connectLDAP($username, $password){
    $ldap_dn="CN=".$username.",CN=Users,DC=TylerMoak,DC=com";
    $adServer = "ldap://WIN-DR1PJ43FVJ3.TylerMoak.com";
    $ldap_con = ldap_connect($adServer);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    $bind = ldap_bind($ldap_con, $ldap_dn, $password);
    if ($bind) {
        return $ldap_con;
    }
    else 
    {
        return 0;
    }
}

function getAccountSID($ldap_con,$username)
{
    $filter ="(samaccountname=".$username.")";
    $result=ldap_search($ldap_con,"DC=TylerMoak,DC=com",$filter) or exit("unable to search");
    $entries = ldap_get_entries($ldap_con,$result);
    return decodeSID($entries[0]["objectSID"][0]);
}


function getAccountName($ldap_con, $sid){
    $filter ="(objectSID=".$sid.")";
    $result=ldap_search($ldap_con,"DC=TylerMoak,DC=com",$filter) or exit("unable to search");
    $entries = ldap_get_entries($ldap_con,$result);
    return $entries[0]["samaccountname"][0];
}




?>
