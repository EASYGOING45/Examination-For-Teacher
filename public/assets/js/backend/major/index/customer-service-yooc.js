/**
 * Created by 10000513 on 2015/2/25.
 */
$(document).ready(function() {
  //新建div
  var parentDiv = $('<div></div>');
  parentDiv.attr('id', 'ser-aside');
  //div添加到body中
  parentDiv.appendTo('body');
  //创建html细节
  var contents =
    '<div id="ser-aside" style="max-width:115px;"><div class="nail"><p class="w-txt info-txt">关注优课</p></div><div class="ser-all"><div style="height: 21px;"><div class="btn" title="收起">—</div><img id="erweima-img" style="cursor:pointer;width:120px;" src="/static/themes/yiban/img/erweima.png"></div>';
  var bigger =
    '<img id="erweima-bigger" style="cursor:pointer;display:none;position:absolute;top:22%;left:38%;z-index:9999999;" src="/static/themes/yiban/img/erweima.png"><div id="maske" style="position:absolute;left:0;top:0;z-index:100000;height:100%;width:100%;background:black;opacity:0.4;filter:alpha(opacity=40);display:none;"></div>';

  //添加细节到父div中
  $('#ser-aside').html(contents);
  $('body').append(bigger);

  //二维码点击放大
  $('#erweima-img').click(function() {
    $('#maske').show(100);
    $('#erweima-bigger').show(100);
  });
  $('#erweima-bigger').click(function() {
    $('#erweima-bigger').hide(100);
    $('#maske').hide(100);
  });

  //检测cookie第一次放大二维码
  // console.log(window.document.cookie);
  // window.cookies = window.document.cookie.split(";");
  // window.flogin = 0;
  // for(i=0;i<window.cookies.length;i++){
  //     window.cookie = cookies[i].split("=")[0];
  //     if(window.cookie=='flogin'||window.cookie==' flogin'){
  //         window.flogin=1;
  //         break;
  //     }
  // }
  // if(window.flogin==0){
  //     var date=new Date();
  //     date.setTime(date.getTime()+365*24*60*60*1000);
  //     window.document.cookie='flogin=1;expires='+date.toGMTString()+";path=/";
  //     $("#maske").show(100);
  //     $("#erweima-bigger").show(100);
  // }

  //客服面板收起与展开
  $('.nail').bind('click', function() {
    $(this).css('display', 'none');
    $('.ser-all').slideDown();
  });
  $('.btn').bind('click', function() {
    $('.ser-all').slideUp();
    $('.nail')
      .delay(300)
      .fadeIn(200);
  });

  //边框变色
  $('.cnt').bind('mouseenter', function() {
    $(this).css('border', '1px solid #fe8333');
    $(this)
      .next()
      .css({
        'border-top': 'none',
        'margin-top': '0'
      });
  });
  $('.cnt').bind('mouseleave', function() {
    $(this).css('border', '1px solid #c8cfd7');
    $(this)
      .next()
      .css({
        'border-top': '1px solid #c8cfd7',
        'margin-top': '-1px'
      });
  });

  //返回top功能
  //滚动条处于距顶端100px以下时出现，否则消失
  $(window).scroll(function() {
    if ($(window).scrollTop() > 200) {
      $('.back-top-btn').fadeIn(500);
    } else {
      $('.back-top-btn').fadeOut(500);
    }
  });

  //点击返回页面顶部
  $('.back-top-btn').click(function() {
    $('body,html').animate({ scrollTop: 0 }, 800);
    return false;
  });

  $('.more-category').click(function() {
    let parentNode = this.parentNode;
    $(parentNode).removeClass('retracted');
    parentNode.removeChild(this);
  });
});
