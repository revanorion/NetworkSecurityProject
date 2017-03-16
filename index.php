<?php
session_start();
 // Includes Login Script
if(isset($_SESSION['login_user'])){
    header("location: wall.php");
}

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible">
        <title>Image Sharing</title>
        <meta name="description" content="Currency Converter, initial-scale=1">
        <link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <link rel=stylesheet href="content/main.css">
        <link rel="stylesheet" type="text/css" href="main.css"> </head>

    <body>
        <div id="myModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <div id="modal-header-content"></div>
                    </div>
                    <div id="modal-body-content" class="modal-body"> </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <nav class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar"> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button> <a class="navbar-brand" href="#">Tyler's Cool Site</a> </div>
            </div>
        </nav>
        <div class="container">
            <div class="row content">
                <form id="loginForm" class="form-group" action="main.php">
                    <div class="col-sm-12 col-md-4 col-md-offset-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Username: </label>
                            </div>
                            <div class="col-md-6">
                                <input id="username" class="form-control" type="text" placeholder="Username" /> </div>
                        </div>
                        <div class="row">&nbsp;</div>
                        <div class="row">
                            <div class="col-md-6">
                                <label>Password: </label>
                            </div>
                            <div class="col-md-6">
                                <input id="password" class="form-control" type="password" placeholder="Password" /> </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <button id="sign-up" type="button" class="btn btn-info btn-md">Sign Up</button>
                        </div>
                        <div class="row">&nbsp;</div>
                        <div class="row">
                            <button id="login" type="button" class="btn btn-success btn-md">Login</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
        <script src="main.js" type="text/javascript"></script>
    </body>

    </html>
