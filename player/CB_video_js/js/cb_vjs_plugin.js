//Starting CB logo custom elements class
var cbVjsMigrate = function(player,options){
	var cbVjsMigrate = this;

	cbVjsMigrate.path = options.branding_logo;
	cbVjsMigrate.link = options.product_link;
	cbVjsMigrate.show = options.show_logo;
	cbVjsMigrate.videoid = options.videoid;
	cbVjsMigrate.userid = options.userid;
	cbVjsMigrate.uploader = options.uploader;
	cbVjsMigrate.videotitle = options.videotitle;
	cbVjsMigrate.thumbnail = options.thumbnail;
	cbVjsMigrate.description = options.description;
	cbVjsMigrate.views = options.views;
	cbVjsMigrate.player = player;
	cbVjsMigrate.nextPlaylistLink = options.next_playlist_link;
	
	cbVjsMigrate.init();
}

cbVjsMigrate.prototype.init = function(){
	var cbVjsMigrate = this;
	var player = cbVjsMigrate.player;
	//Creating header for player
	cbVjsMigrate.createCbVjsHeader();
	cbVjsMigrate.transformCbVjsControlBar();
	cbVjsMigrate.updateVideoViews();
	cbVjsMigrate.bindNextLink();
		
	
}

cbVjsMigrate.prototype.createCbVjsHeader = function(){
	var cbVjsMigrate = this;
	var player = cbVjsMigrate.player;

	videojs.registerComponent('CbVjsHeader', videojs.extend(videojs.getComponent('Button'), {}));
	
	cbVjsMigrate.cbVjsHeader = player.addChild('CbVjsHeader', {
		'el':videojs.createEl('div', 
			{ 	
				className: 'vjs-cb-header-caption hidden-xs',
				dir: 'ltr',
				id : 'vjs-cb-header'
			},
			{ role : 'button'}
		)
	});

	if (cbVjsMigrate.videotitle.length > 50){
		cbVjsMigrate.videotitle = cbVjsMigrate.videotitle.substring(0,50)+' ....'
	}
	if (cbVjsMigrate.description.length > 50){
		cbVjsMigrate.description = cbVjsMigrate.description.substring(0,100)+' ....'
	}

	cbVjsMigrate.cbVjsHeader.el_.innerHTML = 
	'<div class="vjs-header-left-sec">'+
		'<img src="'+cbVjsMigrate.thumbnail+'">'+
	'</div>'+
	'<div class="vjs-header-main-sec">'+
		'<h2><a target="_blank" href="'+baseurl+'/watch_video.php?v='+cbVjsMigrate.videoid+'">'+cbVjsMigrate.videotitle+'</a></h2>'+
		'<p>'+cbVjsMigrate.description+'</p>'+
		'<span>'+cbVjsMigrate.views+' views</span>'+
	'</div>'+
	'<div class="vjs-header-right-sec" data-toggle="modal" data-target="#share">'+
		'<a  href="javascript:void(0);" class="Share_vid btn" data-toggle="modal" data-target="#share"></a>'+
	'</div>';
	
	cbVjsMigrate.player.el_.insertBefore(cbVjsMigrate.cbVjsHeader.el_,player.getChild('bigPlayButton').el_);

}

cbVjsMigrate.prototype.transformCbVjsControlBar = function(){
	var cbVjsMigrate = this;
	var player = cbVjsMigrate.player;
	var controlBar = player.getChild('controlBar');
	var progressBar = controlBar.getChild('progressControl');

	videojs.registerComponent('CbVjsLeftControl', videojs.extend(videojs.getComponent('Button'), {}));
	videojs.registerComponent('CbVjsRightControl', videojs.extend(videojs.getComponent('Button'), {}));


	cbVjsMigrate.CbVjsLeftControl = controlBar.addChild('CbVjsLeftControl', {
		'el':videojs.createEl('div', 
			{ 	
				className: 'vjs-cb-left-controlbar-sec',
				dir: 'ltr',
				id : 'vjs-cb-left-controlbar-sec'
			},
			{ role : 'div'}
		)
	});

	cbVjsMigrate.CbVjsLeftControl = controlBar.addChild('CbVjsRightControl', {
		'el':videojs.createEl('div', 
			{ 	
				className: 'vjs-cb-right-controlbar-sec',
				dir: 'ltr',
				id : 'vjs-cb-right-controlbar-sec'
			},
			{ role : 'div'}
		)
	});
	
	controlBar.el_.insertBefore(progressBar.el_,controlBar.getChild('playToggle').el_);

	controlBar.getChild('CbVjsLeftControl').el_.appendChild(controlBar.getChild('playToggle').el_);
	controlBar.getChild('CbVjsLeftControl').el_.appendChild(controlBar.getChild('volumeMenuButton').el_);
	controlBar.getChild('CbVjsLeftControl').el_.appendChild(controlBar.getChild('currentTimeDisplay').el_);
	controlBar.getChild('CbVjsLeftControl').el_.appendChild(controlBar.getChild('timeDivider').el_);
	controlBar.getChild('CbVjsLeftControl').el_.appendChild(controlBar.getChild('durationDisplay').el_);
	controlBar.getChild('CbVjsLeftControl').el_.appendChild(controlBar.getChild('remainingTimeDisplay').el_);
	

	var cbLogoBrand = document.createElement("div");
	cbLogoBrand.id = "vjs-cb-logo";
	cbLogoBrand.className = "vjs-cblogo-brand";
	cbLogoBrand.className += " vjs-menu-button";
	cbLogoBrand.className += " vjs-control";
	cbLogoBrand.className += " vjs-button";
	cbLogoBrand.innerHTML = '<img style="display:block !important; cursor : pointer;margin:5px 0 0 4px;" src="data:image/png;base64, '+cbVjsMigrate.path+'" alt="">';

	cbLogoBrand.addEventListener('click',function(){
		window.open(cbVjsMigrate.link,"_blank");
	});

	
	
	//controlBar.getChild('CbVjsRightControl').el_.appendChild(controlBar.getChild('fullscreenToggle').el_);
	controlBar.getChild('CbVjsRightControl').el_.appendChild(controlBar.getChild('captionsButton').el_);
    
	
	var vjsIconCog = "";
	//processing for icon 
	var myInterval = setInterval(function(){
		controlBar.getChild('CbVjsRightControl').el_.insertBefore(cbLogoBrand, controlBar.getChild('captionsButton').el_);
		for (var i = 0; i < controlBar.el_.childNodes.length; i++) {
		    if (cbVjsMigrate.hasClass(controlBar.el_.childNodes[i],'vjs-icon-cog')) {
				vjsIconCog = controlBar.el_.childNodes[i];
				controlBar.getChild('CbVjsRightControl').el_.insertBefore(vjsIconCog,controlBar.getChild('captionsButton').el_);
				clearInterval(myInterval);
		        break;
		    }        
		}
	controlBar.getChild('CbVjsRightControl').el_.appendChild(controlBar.getChild('fullscreenToggle').el_);
	},500);
}

cbVjsMigrate.prototype.updateVideoViews = function(){
	var cbVjsMigrate = this;
	var videoid = cbVjsMigrate.videoid;
	var userid = cbVjsMigrate.userid;
	if (cbVjsMigrate.player.options_.autoplay == true){
		updateVideoViews(videoid,userid);
	}else{
		cbVjsMigrate.player.one("play", function(){
			updateVideoViews(videoid,userid);
		});
	}
	
}

cbVjsMigrate.prototype.bindNextLink = function(){
	var cbVjsMigrate = this;
	cbVjsMigrate.player.on('ended',function(){
		if (cbVjsMigrate.nextPlaylistLink.length > 1){
			window.location = cbVjsMigrate.nextPlaylistLink;
		}
	});
}

cbVjsMigrate.prototype.hasClass = function(ele,cls) {
     return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
}


function cb_vjs_elements(settings){

	var cbVjsMigrate_ = new cbVjsMigrate(this,settings);

}

videojs.plugin('cb_vjs_elements', cb_vjs_elements);

    