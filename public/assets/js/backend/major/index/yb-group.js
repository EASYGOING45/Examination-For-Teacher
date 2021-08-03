/* map兼容性 参考https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/Array/map
*/
Array.prototype.map = Array.prototype.map||function(callback, thisArg){
	var T;
  var _self = this;
  var length = _self.length;
  var A = new Array(length);
	if (thisArg) {
  		T = thisArg;
  }
  for(var i=0; i<_self.length; i++){
  	a[i] = callback.call(T, _self[i]);
  }
  return A;
};

/*
  Group general js function.
*/

var supportHtml5 = !!window.history.pushState;
var href = location.href.toString();
var groupData = $('#group-data');
var groupId = parseInt(groupData.attr('data-group-id'));
var urlPrefix = '/group/'+groupId;
var auth = groupData.attr('data-auth');
var csrf = groupData.attr('data-csrf');
var re = /group\/\d+\/ceping|courses|topics|homeworks|exams|documents|grade|survey|jobs|lightapp\/[0-9A-Za-z\-]+|extra\/[0-9A-Za-z\-]+/g;
var noContent = $('#no-content');
var invationCode = location.hash.toString().split('=')[1];
var teams = {};

/* alert方法
 * t: 标题
 * m: 内容
 */
function xAlert(t, m){
  return new jDialog().show({body: m, closable: true, title: t, width:650})
};

/* 课群首页单应用state保存 */
var saveState = function(url){
  stateObj = {
    'url': url
  }
  history.pushState(stateObj, null, url);
};

/* hook课群首页的A链接 */
function hookA(ele){
  var url = ele.attr('href');
  if(url==='javascript:;'){
    // $('.ctx').html($('#group-more').html());
    // ele.parent().removeClass('ac').siblings().addClass('ac');
    return window.location.href = ele.attr('data-href');
  }else if(url.search(re)!==-1&&supportHtml5){
    if(ele.parent().hasClass('tabs')&&!ele.parent().hasClass('ac')) return;
    saveState(url);
    pjax(url);
    ele.parent().removeClass('ac').siblings().addClass('ac');

  }else{
    location.href = url;
  }
};

/* ajax请求失败次数 */
var errorTimes = 1;

/* 课群模块初始化事件绑定 */
function bindEvent(url){
  url.replace(/group\/\d+\/([a-z]+)/g, function(c, d){
    //console.log(d);
    if(d==='ceping'){
      return initCepingRemove(groupId, csrf);
    }else if(d=='courses'){
      return initCourseRemove(groupId, csrf);
    }
  });
};

/* 课群首页模块pjax请求 */
function pjax(u){
  var container = $('div.ctx');
  // container.html('<p class="lodding" style="text-align: center;font-size: 14px;margin-top: 25px;">加载中，请稍后...</p>');
  $.ajax({
    beforeSend:function(xhr){
      xhr.setRequestHeader('X-PJAX', 'true');
    },
    url: u /*(new Date().getTime()).toString()*/,
    type: 'get',
    cache: false,
    success: function(data){
      errorTimes = 1;
      if(data.result){
        container.html(data.contents);
        $('html,body').animate({scrollTop: 0}, 3e2);
        $('.ctx .page a').each(function(){
          var _self = $(this);
          _self.on('click', function(){
            hookA(_self);
            return false;
          });
        });
      }else{
        location.href = u;
      }
    }
  })
}

/*
 * 激活课群左边菜单样式
 */
function tabActive(){
  $('.tabs').each(function(){
    var _self = $(this);
    location.href.toString().replace(/group\/\d+\/([a-z]+)(?:\/{0,1})([a-zA-Z0-9\-]*)/g, function(a, b, c){
      if (b === 'lightapp') {
        if (c === _self.data("lightapp-id")){
            _self.removeClass('ac').siblings().addClass('ac');
        }
      } else if (b === 'extra') {
        if (_self.hasClass(c)){
            _self.removeClass('ac').siblings().addClass('ac');
        }
      } else if (b==='' || _self.hasClass(b)){
        _self.removeClass('ac').siblings().addClass('ac');
      }
    });
  });
};

/* 加入课群 */
function joinGroup(code, csrf){
  $.ajax({
    url: '/group/join',
    type: "POST",
    data: {'csrfmiddlewaretoken': csrf, 'code': code},
    success: function(data){
      if(data['result']){
        location.reload();
      }else{
        xAlert('失败', data['message']);
      }
    },
    error: function(){
      xAlert('失败', '加入课群失败。')
    }
  })
};

/* 发布话题弹窗
 * g: 课群ID
 * m: 弹窗中文信息
 * t: 标题
 * a: 是否登录
 * u: 登录跳转url
 */
function xDialog(csrf, g, m, t, a, u) {
  var c = $('#group-data').attr('data-code') || location.hash.toString().split('=')[1];
  if (!a){
    location.href = u.replace(/\#/g, '%23');
  }else{
    (new jDialog()).show(
      {
        title: t||'提醒',
        width: 650,
        body: [{
          label:m,
          type:'textbox',
          maxlength:8,
          name:'code',
          placeholder:'请输入课群邀请码',
          value:c
        }]
      },
      function(result){
        var _self = this;
        var code = $('.dialog-textbox input[type=text]').val();
        if(result==='ok'){
          if(code!==''){
            $.ajax({
              url: '/group/join',
              type: "POST",
              data: {'csrfmiddlewaretoken': csrf, 'code': code, 'group_id': g},
              success: function(data){
                if(data.result){
                  _self.close();
                  if(data.has_team){
                    location.href='/group/'+data.group_id+'/teams';
                  }else{
                    location.reload();
                 }
                }else{
                  var e = $('#join-error');
                  if(e.length===0){
                    var l = '<label id="join-error" style="font-size:12px;color:#dc3131">'+data.message+'</label>';
                    $('.dialog-textbox').after(l);
                  }else{
                    e.html(data.message);
                  }
                }
              },
              error: function(){
                alert('加入课群失败。')
              }
            });
          }
        }else{
          _self.close();
        }
        return false;
      }
    );
  }
};

/* 课群首页模块切换绑定 */
function hookBind(){
  $('.aside-nav a, .ctx .page a').each(function(){
    var _self = $(this);
    _self.on('click', function(){
      if($('.ctx p.lodding').length===0){
        hookA(_self);
      }
      return false;
    });
  });
};

/* 课群首页捕捉浏览器前进后退行为，pjax刷新页面 */
function popstate () {
  window.onpopstate = function(event){
    var url;
    try{
      url = event.state.url;
    }catch(e){
      url = location.href.toString();
    }
    console.log(url);
    if(url!==undefined&&url.search(re)!==-1){
      url.replace(/group\/\d+\/([a-z]+)\/*/g, function(a, b){
        $('.'+b).removeClass('ac').siblings().addClass('ac');
      });
      pjax(url);
    }else{
      location.href = url;
    }
  };
};

/* 课群邀请码变更弹窗
 * g: 课群ID
 */
function codeCDialog(g){
  return new jDialog().show(
    {
      title: '邀请码变更',
      body: '\
        <p style="text-align:center;">您是否确认变更？</p>\
        <p style="text-align:center;">(确认变更后旧的邀请码将无法使用)</p>\
      ',
      buttons: jDialog.BUTTON_OK_CANCEL,
      width: 650
    },
    function(result){
      var _self = this;
      if(result==='ok'){
        $.ajax({
          url: '/group/'+g+'/settings/code_change',
          type: 'get',
          success: function(data){
            if(data.result){
              location.reload();
            }else{
              alert('邀请码变更失败！');
            }
          }
        })
      }else{
        _self.close();
      }
    }
  );
};

/* 课群访问 */
function visit(groupId){
  $.ajax({
    beforeSend: function(xhr){
      // 取消掉全局loading效果
      if($('#mask').length>0){
        $('#mask').hide();
      }
    },
    url: '/group/'+groupId+'/visit?_=' + (new Date().getTime()).toString(),
    type: 'GET',
    async: true,
    complete: function(xhr,status){
      cancelError();
      return;
    }
  });
};

/* 课群首页访问（先访问后渲染来访页面） */
function getVisit(groupId, size){
  var page = 1;
  $.ajax({
    url: '/group/'+groupId+'/visit?_=' + (new Date().getTime()).toString(),
    type: 'GET',
    data: {'page': page, 'size': size, 'extra': 'get'},
    success: function(data){
      if(data.result){
        page++;
        $('.visitor').prepend(data.html);
        if (data.total!==undefined){
          $('.recently-block .h1-container p').html('今日访问：'+data.total);
        }
      }
    },
    complete: function(xhr,status){
      // 取消全局error弹窗
      cancelError();
      return;
    }
  })
};

function initFrame(frameName){
  $('iframe[name=frameName]').remove();
  $('body').append('<iframe name='+frameName+' style="display:none;"></iframe>');
};

/*
 * 激活loading
 */
function maskShow(){
  var mask = $('#mask');
  if(mask.length > 0) return mask.show();
  $('body').append('\
    <div id="mask" style="position: fixed;width: 100%;height: 100%;left: 0px;top: 0px;z-index: 10000;display: block;background-color: rgba(255,255,255,.8);opacity: 1;">\
      <img src="/static/themes/yiban/images/loading.gif" style="position: absolute;opacity: .8;left: 50%;top: 45%;margin: -35px 0 0 -35px;">\
    </div>\
  ')
  return;
};

/*
 * 隐藏lodding
 */
function hideShow(){
  var mask = $('#mask');
  if(mask.length!==0) return mask.hide();
};

/*
 * 注册课群全局ajaxStart钩子
 */
$(document).ajaxStart(function(){
  return maskShow();
});

/*
 * 课群全局csrf-token
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

/*
 * 注册课群全局ajaxSuccess钩子
 */
$(document).ajaxSuccess(function(event, xhr, options){
  return hideShow();
});

/*
 * 注册课群全局ajaxError钩子
 */
$(document).ajaxError(function(event, xhr, options, exc){

  hideShow();
  if(options.url.indexOf('page=') != -1 ){
      return (new jDialog()).show({
      title: '提示',
      width: 650,
      body: $('<p id="error_dialog" style="text-align:center;">没有下一页</p>'),
      buttons: {ok: {text:"知道了",primary:!0,"default":!0}}
    })
  }
  return (new jDialog()).show({
    title: '提示',
    width: 650,
    body: $('<p id="error_dialog" style="text-align:center;">检测到您当前网络不佳，请稍后重试。</p>'),
    buttons: {ok: {text:"知道了",primary:!0,"default":!0}}
  })
})

$(window).load(function() {
    var minHeight = Math.max($(".aside-nav").height(), $(".right-aside").height()).toString() + "px";
    $(".ctx-container>.ctx").css('min-height', minHeight);
});

/*
 * 去掉全局ajax失败弹窗提示
 */
function cancelError(){
  var eDialog = $('#error_dialog');
  if(eDialog.length > 0){
    eDialog.closest('div.dialog-main').hide();
    $('.dialog-mask').hide();
  }
  return
};

/*
 弹窗小组信息
 */
function getBody(d, title){
  var modules = d.modules || [];
  var html = title || '<p>请设置要发布的小组：</p>';
  var ol = $('<ol>');
  for(var i=0; i<d.teams.length; i++){
    var li = $('<li>');
    if(modules.indexOf(d['teams'][i].id)!==-1){
      li.append('<input type="checkbox" checked data-team-id="'+d['teams'][i].id+'"><label>'+d['teams'][i].name+'</label>');
    }else{
      li.append('<input type="checkbox" data-team-id="'+d['teams'][i].id+'"><label>'+d['teams'][i].name+'</label>');
    }
    ol.append(li);
  }
  html += '<ol class="team-dialog">' + ol.html() + '</ol>';
  html += '\
    <script type="text/javascript">\
    $(document).ready(function(){\
        $(".team-dialog input").on("click", function(){\
            var _self = $(this);\
            var tId = _self.data("team-id");\
            if (parseInt(tId) === 0){\
                $(".team-dialog input").prop("checked", false);\
                _self.prop("checked", true);\
            }else{\
                $($(".team-dialog input")[0]).prop("checked", false);\
            }\
            return;\
        })\
    });\
    </script>';
  return html
}

/*
 * 设置小组
 */
function setTeam(url, body, redirect, buttons, cancel){
  return (new jDialog()).show({title: '提示', width: 650, body: body, buttons: buttons || jDialog.BUTTON_OK_CANCEL}, function(result){
      if(result==='ok'){
        if($(".team-dialog input:checked").length===0){
          xAlert('提示', '请勾选小组。');
          return false;
        }
        var tids = (function(){
          var _t = [];
          var input = $(".team-dialog input:checked");
          for(var _i=0; _i<input.length; _i++){
            _t.push($(input[_i]).data('team-id'));
          }
          return _t.join(',');
        })();
        $.ajax({url: url, type: 'post', data: {tids: tids}}).success(function(d){
          if(d.result){
            location.href = redirect || location.href;
          }else{
            xAlert('失败', d.message);
          }
        })
      }
      if(cancel!==undefined) return cancel();
      this.close();
    })
}

/*
 * 发布弹窗
 * :gId: 课群id
 * :url: 确定后与服务器交互地址
 * :message: 弹窗消息
 * :redirect: 发布成功后回调地址
 * :buttons: 弹窗按钮样式
 * :cancel: 弹窗取消回调函数
 */
function publishDialog(gId, url, message, redirect, buttons, cancel){
  var dialogUrl = '/group/'+gId+'/teams/dialog';
  if (teams[gId]!==undefined){
    var html = teams[gId];
    html += message || '';
    return setTeam(url, html, redirect, buttons, cancel);
  }
  $.get(dialogUrl).success(function(d){
    var html = getBody(d);
    teams[gId] = html;
    html += message || '';
    return setTeam(url, html, redirect, buttons, cancel);
  }).complete(function(){
    cancelError();
    return;
  });
  return false;
}


/*
 * 小组按钮弹窗
 */
function getTeam(url, title){
  $.get(url).success(function(d){
    var html = getBody(d, title || '<p>请勾选要设置的小组：</p>');
    return setTeam(url, html);
  });
}
