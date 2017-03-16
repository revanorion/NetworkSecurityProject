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
    $selectStmt = "SELECT W.WALL_SEQ, W.USER_SEQ, W.STATUS_TEXT, W.TIME_STAMP, U.USERNAME FROM WALL W JOIN USER U ON W.USER_SEQ = U.USER_SEQ WHERE W.WALL_SEQ = ".$wallSEQ;
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
            $selectImageStmt = "SELECT I.IMAGE_SEQ, I.IMAGE_NAME FROM WALL_IMAGE WI JOIN IMAGE I ON WI.IMAGE_SEQ = I.IMAGE_SEQ WHERE WI.WALL_SEQ =".$wallSEQ;
            $ImageResult = $db->query($selectImageStmt);

            if (mysqli_num_rows($ImageResult) > 0) {
                while($rowImg = mysqli_fetch_assoc($ImageResult)) {
                    //this will add construct the html elements for each image
                        $imageHTML.="<p><a class='fileThumb' href='".$rowImg["IMAGE_NAME"]."' target='_blank'><image class='image-post' src='".$rowImg["IMAGE_NAME"]."'></image></a></p>";
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
                            <p>".$row["USERNAME"]." ".$row["TIME_STAMP"]."</p>
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
    $selectStmt = "SELECT W.WALL_SEQ, W.USER_SEQ, W.STATUS_TEXT, W.TIME_STAMP, U.USERNAME FROM WALL W JOIN USER U ON W.USER_SEQ = U.USER_SEQ ORDER BY TIME_STAMP DESC LIMIT 5 ";
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
            $selectImageStmt = "SELECT I.IMAGE_SEQ, I.IMAGE_NAME FROM WALL_IMAGE WI JOIN IMAGE I ON WI.IMAGE_SEQ = I.IMAGE_SEQ WHERE WI.WALL_SEQ =".$wallSEQ;
            $ImageResult = $db->query($selectImageStmt);

            if (mysqli_num_rows($ImageResult) > 0) {
                while($rowImg = mysqli_fetch_assoc($ImageResult)) {
                    //this will add construct the html elements for each image
                        $imageHTML.="<p><a class='fileThumb' href='".$rowImg["IMAGE_NAME"]."' target='_blank'><image class='image-post' src='".$rowImg["IMAGE_NAME"]."'></image></a></p>";
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
                            <p>".$row["USERNAME"]." ".$row["TIME_STAMP"]."</p>
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
    $insertStmt = "INSERT INTO WALL (USER_SEQ, STATUS_TEXT) VALUES(".$userid.", '".$textValue."')";
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
                        $insertImgStmt="INSERT INTO IMAGE (IMAGE_NAME) VALUES ('".$target_file."')";
                        $insertImgresult = $db->query($insertImgStmt);
                        if (mysqli_affected_rows($db) > -1) {
                            $selectImgStmt = "SELECT MAX(IMAGE_SEQ) as IMAGE_SEQ FROM IMAGE";
                            $selectImgresult = $db->query($selectImgStmt);
                            $imgSeq=mysqli_fetch_assoc($selectImgresult)["IMAGE_SEQ"];
                            $insertWallImageStmt = "INSERT INTO WALL_IMAGE (IMAGE_SEQ, WALL_SEQ) VALUES (".$imgSeq.", ".$wallSeq.")";
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
    $selectStmt = "SELECT USER_SEQ FROM USER WHERE USERNAME ='".$username."'";
    $result = $db->query($selectStmt);
    if (mysqli_num_rows($result) > 0) {
        return -1;
    }
    else
    {
        $insertStmt= "INSERT INTO USER (USERNAME, PASSWORD) VALUES ('".$username."', '".password_hash($password, PASSWORD_DEFAULT)."')";
        $result = $db->query($insertStmt);
        if (mysqli_affected_rows($db) > -1) {
            return 1;
        }
        return 0;
    }
}


function loginUser($username, $password){
    require_once './php/db_connect.php';
    $selectStmt = "SELECT USER_SEQ, PASSWORD FROM USER WHERE USERNAME ='".$username."'";
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
    }
}
?>
