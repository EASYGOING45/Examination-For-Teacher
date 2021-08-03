/*
 * hovercard插件。
 * 改写jquery.hovercard.js插件。http://designwithpc.com/Plugins/Hovercard
 * 依赖jDialog插件
 */

(function ($) {
  var cacheCardJSON = {};

  $.fn.hovercard = function (options) {

    //Set defauls for the control
    var defaults = {
      width: 367,
      openOnLeft: false,
      detailsHTML: "",
      showCustomCard: false,
      customDataUrl: '',
      background: "#ffffff",
      delay: 300,
      autoAdjust: true,
      onHoverIn: function () { },
      onHoverOut: function () { }
    };
    //Update unset options with defaults if needed
    var options = $.extend(defaults, options);

    //CSS for hover card. Change per your need, and move these styles to your stylesheet (recommended).
    if ($('#css-hovercard').length <= 0) {
      var hovercardTempCSS = '<style id="css-hovercard" type="text/css">' +
        '#hc-preview { position: relative; display:inline-block; }' +
        '#hc-preview .hc-name {position:relative; display:inline-block; }' +
        '#hc-preview .hc-details { left:45px; text-align:left; font-family:Sans-serif !important; font-size:12px !important; color:#666 !important; line-height:1.5em; box-shadow:0px 0px 5px rgba(0, 0, 0, 0.10); position:absolute;border-radius:3px;top:-80px;padding:10px;display:none;z-index:200;height: 125px;}' +
        '#hc-preview .hc-pic { width:70px; margin-top:-1em; float:right;  }' +
        '#hc-preview .hc-details-open-left { left: auto; right:48px; text-align:left; margin-right:0; } ' +
        '#hc-preview .hc-details-open-left > .hc-pic { float:left; } ' +
        '#hc-preview .hc-details-open-top { bottom:-75px; top:auto; right:45px;} ' +
        '#hc-preview .hc-details-open-top > .hc-pic { margin-top:10px; float:right;  }' +
        '#hc-preview .hc-details .s-action{ position: absolute; top:8px; right:5px; } ' +
        '#hc-preview .hc-details .s-card-pad{overflow: hidden;} ' +
        '#hc-preview .hc-details-open-top .s-card-pad { border:none; margin-top:0;padding-top:0;}' +
        '#hc-preview .hc-details .s-card .s-strong{ font-weight:bold; color: #555; } ' +
        '#hc-preview .hc-details .s-img{ float: left; margin-right: 10px;width:120px;height:120px;border-radius:0%;}' +
        '#hc-preview .hc-details .s-name{ color:#222; font-weight:bold;} ' +
        '#hc-preview .hc-details .s-loc{ float:right;}' +
        '#hc-preview .hc-details-open-left .s-loc{ float:left;} ' +
        '#hc-preview .hc-details .s-href{ clear:both; float:left;} ' +
        '#hc-preview .hc-details .s-desc{ float:left; font-family: Georgia; font-style: italic; margin-top:5px;width:100%;} ' +
        '#hc-preview .hc-details .s-username{ text-decoration:none;} ' +
        '#hc-preview .hc-details .s-stats { display:block; float:left; margin-top:5px; clear:both; padding:0px;}' +
        '#hc-preview .hc-details ul.s-stats li{ list-style:none; float:left; display:block; padding:0px 10px !important; border-left:solid 1px #eaeaea;} ' +
        '#hc-preview .hc-details ul.s-stats li:first-child{ border:none; padding-left:0 !important;} ' +
        '#hc-preview .hc-details .s-count { font-weight: bold;} ' +
        '#hc-preview .s-card-profile{display: block;margin-left: 140px;}' +
        '#hc-preview .s-card-profile span.school{background: url("/static/themes/yiban/img/school_verify.png"); width: 20px;height: 20px;display: inline-block;position: relative;top: 5px;left: 7px;}' +
        '#hc-preview .s-card-profile p.s-nick{min-height:26px;width: 190px;text-align: left;font-size:16px;color:#212121;margin-bottom:25px;margin-top:20px;line-height:25px;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;}' +
        '#hc-preview .buttons{padding-top: 20px;border-top: 1px solid #f6f6f6;}' +
        '#hc-preview .buttons a{position: relative;color: #d67f6b;box-sizing: border-box;padding: 1px 30px;border-radius: 1px;border: 1px solid #f7dbc3;background-color: #fae8da;display: inline-block;margin: 0px 15px 0px 0px;line-height: 18px;font-size:12px;}' +
        '#hc-preview .hover-menu{position: absolute;right: -52px;display:none;}'+
        '#hc-preview .buttons button.menu{cursor:pointer;color: #d67f6b;box-sizing: border-box;padding: 0px 4px;border-radius: 1px;border: 1px solid #f7dbc3;background-color: #fae8da;display: inline-block;outline:none;}' +
        '#hc-preview .buttons div.menu{cursor:pointer;color: #d67f6b;box-sizing: border-box;padding: 0px 4px;border-radius: 1px;border: 1px solid #f7dbc3;background-color: #fae8da;display: inline-block;outline:none;}' +
        '#hc-preview .buttons .remove{padding:1px 5px;}' +
        '#hc-preview span.arrow{position: absolute;z-index: 200;left: -16px;top: 70px;display: block;height: 30px;background: url("/static/themes/yiban/img/usercard/arrow-left.png");width: 16px;}' +
        '#hc-preview .hc-details-open-left span.arrow{background: url("/static/themes/yiban/img/usercard/arrow-right.png");left:386px;}' +
        '#hc-preview .buttons a.block{cursor:default;width:86px;height:22px;color: #aaaaaa;border: 1px solid #f0f0f0;background-color: #f8f8f8;}' +
        '#hc-preview .buttons button.block{line-height:18px;width:20px;display:inline-block;margin:0px;color: #aaaaaa;border: 1px solid #f0f0f0;background-color: #f8f8f8;outline: none;padding: 0px 4px;}' +
        '#hc-preview .s-message{text-align: center;font-size: 14px;vertical-align: middle;margin-top: 50px;width: 100%;color: #212121;}' +
      '.</style>")';

      $(hovercardTempCSS).appendTo('head');
    }
    //Executing functionality on all selected elements
    return this.each(function () {
      var obj = $(this);

      //wrap a parent span to the selected element
      obj.wrap('<div id="hc-preview" class="hc-preview" />');

      //add a relatively positioned class to the selected element
      obj.addClass("hc-name");

      //if card image src provided then generate the image elementk
      var arrow = '<span class="arrow"></span>'
      //generate details span with html provided by the user
      var hcDetails = '<div class="hc-details" >' + arrow + options.detailsHTML + '</div>';

      //append this detail after the selected element
      obj.after(hcDetails);
      obj.siblings(".hc-details").eq(0).css({ 'width': options.width, 'background': options.background });

      //toggle hover card details on hover
      obj.closest(".hc-preview").hover(function () {

        var $this = $(this);
        adjustToViewPort($this);

        //Up the z indiex for the .hc-name to overlay on .hc-details

        //$this.siblings().css('zIndex', '0');
        //$('.hc-preview').css('zIndex', '0');
        $this.css("zIndex", "200");
        // obj.css("zIndex", "100").find('.hc-details').css("zIndex", "50");
        obj.find('.hc-details').css("zIndex", "200");

        var curHCDetails = $this.find(".hc-details").eq(0);
        curHCDetails.stop(true, true).delay(options.delay).fadeIn();

        //Default functionality on hoverin, and also allows callback
        if (typeof options.onHoverIn == 'function') {

          //check for custom profile. If already loaded don't load again
          if (options.showCustomCard && curHCDetails.find('.s-card').length <= 0) {

            //Read data-hovercard url from the hovered element, otherwise look in the options. For custom card, complete url is required than just username.
            var dataUrl = options.customDataUrl + '/' + obj.attr('data-user-id');
            if (typeof obj.attr('data-hovercard') == 'undefined') {
              //do nothing. detecting typeof obj.attr('data-hovercard') != 'undefined' didn't work as expected.
            } else if (obj.attr('data-hovercard').length > 0) {
              dataUrl = obj.attr('data-hovercard');
            }

            LoadSocialProfile("custom", dataUrl, curHCDetails);
          }

          //Callback function
          options.onHoverIn.call(this);
        }

      }, function () {
        //Undo the z indices
        var $this = $(this);

        $this.find(".hc-details").eq(0).stop(true, true).fadeOut(300, function () {
          $this.css("zIndex", "0");
          obj.css("zIndex", "0").find('.hc-details').css("zIndex", "0");

          if (typeof options.onHoverOut == 'function') {
            options.onHoverOut.call(this);
          }
        });
      });

      //Opening Directions adjustment
      function adjustToViewPort(hcPreview) {
        var hcDetails = hcPreview.find('.hc-details').eq(0);
        var hcPreviewRect = hcPreview[0].getBoundingClientRect();
        // var hcdTop = hcPreviewRect.top - 20; //Subtracting 20px of padding;
        var hcdRight = hcPreviewRect.left + 85 + hcDetails.width(); //Adding 35px of padding;
        // var hcdBottom = hcPreviewRect.top + 55 + hcDetails.height(); //Adding 35px of padding;
        // var hcdLeft = hcPreviewRect.top - 10; //Subtracting 10px of padding;
        var height = hcPreview.height();
        var width = hcPreview.width();
        //Check for forced open directions, or if need to be autoadjusted
        if (options.openOnLeft || (options.autoAdjust && (hcdRight > window.innerWidth))) {
          hcDetails.addClass("hc-details-open-left");
          hcDetails.css({'top': (height/2.5-80)+'px', 'right': (width/2+45)+'px'});
        } else {
          hcDetails.removeClass("hc-details-open-left");
          hcDetails.css({'top': (height/2.5-80)+'px', 'left': (width/3+40)+'px'});
        }
      };

      //Private base function to load any social profile
      function LoadSocialProfile(type, username, curHCDetails) {
        var cardHTML, urlToRequest, customCallback, loadingHTML, errorHTML;

        switch (type) {
          case "custom":
            {
              urlToRequest = username,
              cardHTML = function (profileData) {
                return '<div class="s-card s-card-pad" data-user-id='+profileData.user_id+'>'+
                  '<img class="s-img" src="'+profileData.image+'" />'+
                    '<div class="s-card-profile">'+
                      '<p class="s-nick">'+profileData.nick+(profileData.is_schoolVerify ? '<span class="school"></span>' : '')+'</p>'+
                      '<div class="buttons">'+
                        '<a class="view" href="'+profileData.user_info_url+'" target="_blank">查看</a>'+
                        (profileData.is_block ? '<a class="block" onclick="return false;">私信</a><button onclick="return false;" class="block">=</button>' : '<a class="message" href="'+profileData.message_url+'" target="_blank">私信</a>'+
                        '<div class="menu" onclick="return false;">='+
                        '<div class="hover-menu">'+
                          '<a href="javascript:;" target="_blank" class="remove" >加入黑名单</a>'+
                        '</div></div>')+
                      '</div>'+
                    '</div>'+
                  '</div>';
              };
              loadingHTML = "加载中，请稍后...";
              errorHTML = "很抱歉，加载失败，请重试。";
              key = 'usercard:'+urlToRequest;
              customCallback = function () { };
            }
            break;
          default: { } break;
        }

        if (cacheCardJSON[key] === undefined) {
          $.ajax({
            url: urlToRequest,
            type: 'GET',
            timeout: 4000, //timeout if cross domain request didn't respond, or failed silently
            beforeSend: function () {
              curHCDetails.find('.s-message').remove();
              curHCDetails.append('<p class="s-message">' + loadingHTML + '</p>');
              // 取消全局loading效果
              if($('#mask').length > 0){
                $('#mask').hide();
              }
            },
            success: function (d) {
              if (!d.result) {
                curHCDetails.find('.s-message').html(errorHTML);
              }else {
                curHCDetails.find('.s-message').remove();
                curHCDetails.prepend(cardHTML(d.data));
                adjustToViewPort(curHCDetails.closest('.hc-preview'));
                if(curHCDetails.closest('.hc-preview').css('z-index') === 200){
                  curHCDetails.stop(true, true).delay(options.delay).fadeIn();
                  customCallback(d);
                }
                cacheCardJSON[key] = d.data;
              }
            },
            error: function (jqXHR, textStatus, errorThrown) {
              return curHCDetails.find('.s-message').html(errorHTML);
            },
            complete: function(xhr,status){
              // 取消全局error弹窗
              if($('.dialog-main').length > 0){
                $('.dialog-main').hide();
                $('.dialog-mask').hide();
              }
              return;
            }
          });
        }
        else {
          curHCDetails.prepend(cardHTML(cacheCardJSON[key]));
        }
      };

    });

  };
})(jQuery);


/*
 * 全局csrf-token
 */
$(document).ajaxSend(function(event, xhr, options) {
  function getCookie(name) {
    var cookieValue = null;
    if (document.cookie && document.cookie != '') {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = jQuery.trim(cookies[i]);
        // Does this cookie string begin with the name we want?
        if (cookie.substring(0, name.length + 1) == (name + '=')) {
          cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
          break;
        }
      }
    }
    return cookieValue;
  }
  function sameOrigin(url) {
    // url could be relative or scheme relative or absolute
    var host = document.location.host; // host + port
    var protocol = document.location.protocol;
    var sr_origin = '//' + host;
    var origin = protocol + sr_origin;
    // Allow absolute or scheme relative URLs to same origin
    return (url == origin || url.slice(0, origin.length + 1) == origin + '/') ||
        (url == sr_origin || url.slice(0, sr_origin.length + 1) == sr_origin + '/') ||
        // or any other URL that isn't scheme relative or absolute i.e relative.
        !(/^(\/\/|http:|https:).*/.test(url));
  }
  function safeMethod(method) {
    return (/^(GET|HEAD|OPTIONS|TRACE)$/.test(method));
  }

  if (!safeMethod(options.type) && sameOrigin(options.url)) {
      xhr.setRequestHeader("X-CSRFToken", getCookie('csrftoken'));
  }
});


$(document).ready(function(){
  // 添加黑名单
  $('body').off('click', '.s-card .remove').on('click', '.s-card .remove', function(){
    var _self = $(this).closest('.s-card');
    var name = _self.find('.s-nick').html();
    var block_user = _self.attr('data-user-id');
    return (new jDialog()).show({
      title: '提示',
      width: 650,
      body: '<p>您确定将<x style="color: #fe8333;">'+name+'</x>加入黑名单中吗？加入后您将无法收到TA的任何私信。</p>',
      buttons: jDialog.BUTTON_OK_CANCEL
    }, function(result){
      if(result==='ok'){
        $.ajax({
          url: '/message/block',
          type: 'post',
          data: {
            block_user: block_user
          },
          success: function(d){
            if(d.result){
              return (new jDialog()).show({
                title: '提示',
                width: 650,
                body: '<p style="text-align:center;">加入黑名单成功。</p>',
                buttons: {ok: {text:"知道了",primary:!0,"default":!0}}
              }, function(){
                return location.reload();
              })
            }
            alert(d.message);
          }
        });
      }
    });
  });

  $('body').on('mouseover', 'div.menu', function(){
    $(this).children().show();
    //$(this).children().hide();
  });

  $('body').on('mouseout', 'div.menu', function(){
    $(this).children().hide();
  });
  
  $('.hovercard').hovercard({
    showCustomCard: true,
    customDataUrl: '/usercard'
  });
})
