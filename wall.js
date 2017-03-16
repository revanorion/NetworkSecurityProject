/*jslint browser: true, plusplus: true */
/*global $, jQuery, alert*/
$(document).ready(function () {
    'use strict';
    //this loads all the wall posts on the page
    var url = $('#voiceForm').attr('action');
    var data={
        'getPosts':true
    };
    $.post(url, data, function (response) {
        $('#wall-posts').html(response);
    }).fail(function( jqXHR, textStatus ) {
        alert( "Request failed: " + textStatus );
    });

    //this sets up the file input thingy along with the post action
    $("#input-fa").fileinput({
        maxFileCount: 10,
        uploadUrl: "upload2.php"

    });

    //this handles the mouse moving over the image to give a preview
    $(document).on('mousemove','.fileThumb',function(e){
        var offset = $(this).offset();
        var x = e.pageX - $(this).offset().left;
        var y = e.pageY - $(this).offset().top;

        if ($("#ihover").length) {
            $("#ihover").css({'top': y+200, 'left': x+900});
        }
        else{
            var imgLoc = $(this).children('img').prop('src');
            var myImg = "<image id='ihover' src='"+imgLoc+"' style='top: "+y+ "; left: "+x+";'>";
            $(document.body).append(myImg);
        }
    });

    //this removes the image hover thingy
    $(document).on('mouseleave', '.fileThumb', function(){
        if ($("#ihover").length) {
            $("#ihover").remove();
        }
    });


    //this handles when the user posts a new wall post
    $('#postButton').on('click', function(e){
        e.preventDefault();
        var data = {
            'voicePost':true,
            'textValue':$('#voiceInput').val(),
            'picValue': "test"
        };
        $.post(url, data, function (response) {
            $('#voiceInput').val("")
            var wallData= response + $('#wall-posts').html();
            $('#wall-posts').html(wallData);
        }).fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });

    //this deals with the upload button to show the actual field.
    $('#uploadArea').slideToggle(500);
    $('#uploadPictureButton').on('click', function(e){
        e.preventDefault();
       $('#uploadArea').slideToggle(500);
    });
    //this handles the removing of files. this must be done cause session vars have data that must be cleaned
    $('.fileinput-remove-button').on('click',function(){
        var data = {
            'clearUploads':true
        };
        $.post(url, data, function (response) {
            alert("cleared session var");
        }).fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });
    //this is the tiny trashcan for each image
    $(document).on('click','.kv-file-remove',function(){
        alert("clicked file remove. never got around to implementing this. easy tho");
    });

    //this handles the clicking of the like button
    $(document).on('click','.like',function(){
        var dataId = $(this).data('id');
        if($(this).prop('checked'))
        {
            //like func
            var data = {
                'likePost':true,
                'wallSEQ': dataId
            };
            $.post(url, data, function (response) {
                var wallPost= '#WALL-SEQ-'+ dataId;
                $(wallPost).html(response);
            }).fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        } else{
            //unlike func.
            alert("didnt implment this yet lol");
            var data = {
                'unlikePost':true,
                'wallSEQ':dataId
            };
            $.post(url, data, function (response) {
                var wallPost= '#WALL-SEQ-'+dataId;
                $(wallPost).html(response);
            }).fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        }
    });


});
