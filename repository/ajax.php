<?php
/*******************************************************\

  This file is a demo page for ajax repository file 
  picker.

\*******************************************************/
require_once('../config.php');
$itempic = $CFG->pixpath.'/i/item.gif';
$meta = <<<EOD
<style type="text/css">
body {
	margin:0;
	padding:0;
}
#demo .yui-resize-handle-br {
    height: 11px;
    width: 11px;
    background-position: -20px -60px;
    background-color: transparent;
}
#panel{padding:0;margin:0; text-align:left;}
#list{line-height: 1.5em}
#list li{
background: url($itempic) no-repeat 0 2px;
padding-left: 24px
}
#list a{
padding: 3px
}
#list li a:hover{
background: gray;
color:white;
}
.t{width:80px; float:left;text-align:center;}
.t div{width: 80px; height: 36px; overflow: hidden}
img{margin:0;padding:0;border:0}
#paging{margin:10px 5px; clear:both}
#paging a{padding: 4px; border: 1px solid gray}
</style>
<link rel="stylesheet" type="text/css" href="../lib/yui/reset-fonts-grids/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="../lib/yui/reset/reset-min.css" />
<link rel="stylesheet" type="text/css" href="../lib/yui/resize/assets/skins/sam/resize.css" />
<link rel="stylesheet" type="text/css" href="../lib/yui/container/assets/skins/sam/container.css" />
<link rel="stylesheet" type="text/css" href="../lib/yui/layout/assets/skins/sam/layout.css" />
<link rel="stylesheet" type="text/css" href="../lib/yui/button/assets/skins/sam/button.css" />
<link rel="stylesheet" type="text/css" href="../lib/yui/menu/assets/skins/sam/menu.css" />
<script type="text/javascript" src="../lib/yui/yahoo/yahoo-min.js"></script>
<script type="text/javascript" src="../lib/yui/event/event-min.js"></script>
<script type="text/javascript" src="../lib/yui/dom/dom-min.js"></script>
<script type="text/javascript" src="../lib/yui/element/element-beta-min.js"></script>
<script type="text/javascript" src="../lib/yui/dragdrop/dragdrop-min.js"></script>
<script type="text/javascript" src="../lib/yui/container/container-min.js"></script>
<script type="text/javascript" src="../lib/yui/resize/resize-beta-min.js"></script>
<script type="text/javascript" src="../lib/yui/animation/animation-min.js"></script>
<script type="text/javascript" src="../lib/yui/layout/layout-beta-min.js"></script>
<script type="text/javascript" src="../lib/yui/connection/connection.js"></script>
<script type="text/javascript" src="../lib/yui/json/json-min.js"></script>
<script type="text/javascript" src="../lib/yui/menu/menu-min.js"></script>
<script type="text/javascript" src="../lib/yui/button/button-min.js"></script>
<script type="text/javascript" src="../lib/yui/selector/selector-beta-min.js"></script>
EOD;
print_header('', '', '', '', $meta, false);
?>
<div id='control' style="margin: 5em">
    <input type="button" id="con1" onclick='openpicker()' value="Open File Picker" /> <br/>
    <textarea rows=12 cols=50 id="result">
    </textarea>
</div>
<div class=" yui-skin-sam">
    <div id="file-picker"></div>
</div>

<script type="text/javascript">
var repositoryid = 0;
var datasource, Dom = YAHOO.util.Dom, Event = YAHOO.util.Event, layout = null, resize = null;
var viewbar  = null;
var viewmode = 0;

/**
 * this function will create a file picker dialog, and resigister all the event
 * of component
 */
function openpicker(){
    // QUIRKS FLAG, FOR BOX MODEL
    var IE_QUIRKS = (YAHOO.env.ua.ie && document.compatMode == "BackCompat");
    // UNDERLAY/IFRAME SYNC REQUIRED
    var IE_SYNC = (YAHOO.env.ua.ie == 6 || (YAHOO.env.ua.ie == 7 && IE_QUIRKS));
    // PADDING USED FOR BODY ELEMENT (Hardcoded for example)
    var PANEL_BODY_PADDING = (10*2);
    // 10px top/bottom padding applied to Panel body element. The top/bottom border width is 0
    var panel = new YAHOO.widget.Panel('file-picker', {
        draggable: true,
        underlay: 'none',
        width: '510px',
        xy: [100, 100]
    });
    panel.setHeader('Moodle Repository Picker');
    panel.setBody('<div id="layout"></div>');
    panel.beforeRenderEvent.subscribe(function() {
        Event.onAvailable('layout', function() {
            layout = new YAHOO.widget.Layout('layout', {
                height: 400,
                width: 490,
                units: [
                    {position: 'top', height: 32, resize: false, body:'<div class="yui-buttongroup" id="viewbar"></div>', gutter: '2'},
                    { position: 'left', width: 150, resize: true, body: '<ul id="list"></ul>', gutter: '0 5 0 2', minWidth: 150, maxWidth: 300 },
                    { position: 'bottom', 
                    height: 30, 
                    body: '<div id="toolbar">'+
                    '<input type="button" id="select" value="Select" />'+
                    '<input type="button" id="search" value="Search" />'+
                    '<input type="button" id="logout" value="Logout" />'+
                    '</div>', 
                    gutter: '2'},
                    { position: 'center', body: '<div id="panel"></div>', scroll: true, gutter: '0 2 0 0' }
                ]
            });

            layout.render();
        });
    });
    panel.render();
    resize = new YAHOO.util.Resize('file-picker', {
        handles: ['br'],
        autoRatio: true,
        status: true,
        minWidth: 380,
        minHeight: 400
    });
    resize.on('resize', function(args) {
        var panelHeight = args.height;
        var headerHeight = this.header.offsetHeight; // Content + Padding + Border
        var bodyHeight = (panelHeight - headerHeight);
        var bodyContentHeight = (IE_QUIRKS) ? bodyHeight : bodyHeight - PANEL_BODY_PADDING;
        YAHOO.util.Dom.setStyle(this.body, 'height', bodyContentHeight + 'px');
        if (IE_SYNC) {
            this.sizeUnderlay();
            this.syncIframe();
        }
        layout.set('height', bodyContentHeight);
        layout.set('width', (args.width - PANEL_BODY_PADDING));
        layout.resize();
        
    }, panel, true);
    var list = new YAHOO.util.Element('list');
    list.on('contentReady', function(e){
            // TODO
            // Should call a function to generate
            // repository list
            var li = document.createElement('li');
            li.innerHTML = '<a href="###">Box.net</a>';
            li.id = 'repo-1';
            this.appendChild(li);
            var i = new YAHOO.util.Element('repo-1');
            i.on('click', function(e){
                cr(1, 1, 0);
                });
            li = document.createElement('li');
            li.innerHTML = '<a href="###">Flickr</a>';
            li.id = 'repo-2';
            this.appendChild(li);
            i = new YAHOO.util.Element('repo-2');
            i.on('click', function(e){
                cr(2, 1, 0);
                });
        });
    YAHOO.util.Event.addListener('logout', 'click', function(e){
            cr(repositoryid, 1, 1);
            });
    viewbar = new YAHOO.widget.ButtonGroup({
            id: 'btngroup',
            name: 'buttons',
            disabled: true,
            container: 'viewbar'
            });
    var btn_list = {label: 'List', value: 'l', checked: true, onclick: {fn: viewlist}};
    var btn_thumb = {label: 'Thumbnail', value: 't', onclick: {fn: viewthumb}};
    viewbar.addButtons([btn_list, btn_thumb]);
    var select = new YAHOO.util.Element('select');
    select.on('click', function(e){
        var nodes = YAHOO.util.Selector.query('input:checked'); 
        var str = '';
        for(k in nodes){
            str += (nodes[k].value+'\n');
        }
        // TODO
        // Call ws.php to download these files
        alert(str);
            })
    var search = new YAHOO.util.Element('search');
    search.on('click', function(e){
        // TODO
        // Call get_listing to search
            })
};

function postdata(obj) {
    var str = '';
    for(k in obj) {
        if(str == ''){
            str += '?';
        } else {
            str += '&';
        }
        str += encodeURIComponent(k) +'='+encodeURIComponent(obj[k]);
    }
    return str;
}

// XXX: A ugly hack to show paging for flickr
function makepage(){
    var str = '';
    if(datasource.pages){
        str += '<div id="paging">';
        for(var i = 1; i <= datasource.pages; i++) {
            str += '<a onclick="cr('+repositoryid+', '+i+', 0)" href="###">';
            str += String(i);
            str += '</a> ';
        }
        str += '</div>';
    }  
    return str;
}

// display a loading picture
function loading(){
    var panel = new YAHOO.util.Element('panel');
    panel.get('element').innerHTML = '<img src="<?php echo $CFG->pixpath.'/i/loading.gif'?>" alt="loading..." />';
}

// produce thumbnail view
function viewthumb(){
    viewbar.check(1);
    obj = datasource.list;
    if(!obj){
        return;
    }
    var panel = new YAHOO.util.Element('panel');
    var str = '';
    str += makepage();
    for(k in obj){
        str += '<div class="t">';
        str += '<img title="'+obj[k].title+'" src="'+obj[k].thumbnail+'" />';
        str += '<div style="text-align:center"><input type="checkbox" name="selected-files" value="'+obj[k].source+'"/><br/>'
        str += obj[k].title+'</div>';
        str += '</div>';
    }
    panel.get('element').innerHTML = str;
    viewmode = 1;
    return str;
}

// produce list view
function viewlist(){
    var str = '';
    viewbar.check(0);
    obj = datasource.list;
    if(!obj){
        return;
    }
    var panel = new YAHOO.util.Element('panel');
    str += makepage();
    for(k in obj){
        str += '<input type="checkbox" name="selected-files" value="'+obj[k].source+'" />';
        str += obj[k].title;
        str += '<br/>';
    }
    panel.get('element').innerHTML = str;
    viewmode = 0;
    return str;
}

// produce login html 
function print_login(){
    var panel = new YAHOO.util.Element('panel');
    var data = datasource.l;
    panel.get('element').innerHTML = data;
}

var callback = {
success: function(o) {
    var ret = YAHOO.lang.JSON.parse(o.responseText);
    datasource = ret;
    if(datasource.l){
        print_login();
    } else if(datasource.list) {
        if(viewmode) {
            viewthumb();
        } else {
            viewlist();
        }
    }
  }
}

function cr(id, path, reset){
    viewbar.set('disabled', false);
    if(id != 0) {
        repositoryid = id;
    }
    loading();
    var trans = YAHOO.util.Connect.asyncRequest('GET', 'ws.php?id='+id+'&p='+path+'&reset='+reset, callback);
}

function dologin(){
    YAHOO.util.Connect.setForm('moodle-repo-login');
    loading();
    var trans = YAHOO.util.Connect.asyncRequest('POST', 'ws.php', callback);
}
</script>
<?php
print_footer('empty');