
var Ads = function(player,settings){

	var ads = this;
	console.log(ads);
	ads.ad_id = settings.settings.ad_id;
	ads.ad_code = settings.settings.ad_tag;
	ads.settings = settings.settings;
	ads.player = player;
	ads.adsplayer = {};
	//Elements for Video Ads
	ads.adsplayer.video = document.createElement('video');
	ads.adsplayer.video.id = "ads-player";
	ads.adsplayer.adsManager = '';
	ads.adsplayer.vjsControls = "";
	ads.adsplayer.adContainerDiv = "";
	ads.adsplayer.controlsDiv = "";
	ads.adsplayer.countdownDiv = "";
	ads.adsplayer.seekBarDiv = "";
	ads.adsplayer.progressDiv = "";
	ads.adsplayer.playPauseDiv = "";
	ads.adsplayer.muteDiv = "";
	ads.adsplayer.sliderDiv = "";
	ads.adsplayer.sliderLevelDiv = "";
	ads.adsplayer.fullscreenDiv = "";
	ads.adsplayer.adLabel = "Advertisement";
	if (ads.settings.skip_time){
		ads.adsplayer.skippableTime = parseInt(ads.settings.skip_time);
	}else{
		ads.adsplayer.skippableTime = parseInt("5");
	}
	ads.adsplayer.CurrentRoundTime = false;

	//Events 
	ads.adsplayer.adPlaying = false;
	ads.adsplayer.adMuted = false;
	ads.adsplayer.isFullscreen = false;
	ads.adsplayer.showCountdown = "block";

	//Non-Linear Ads settins
	ads.adsbox = {};
	ads.adsbox.adContainerDiv = "";
	ads.adsbox.adImgHolderDiv = "";
	ads.adsbox.adCloseBtn = "";
	ads.adsbox.scriptsExists = "";

	ads.init();
	
}

Ads.prototype.init = function (){
	var ads = this;

	var startEvent = 'click';
	var endEvent = 'ended';
	

	ads.events = [
	    google.ima.AdEvent.Type.ALL_ADS_COMPLETED,
	    google.ima.AdEvent.Type.CLICK,
	    google.ima.AdEvent.Type.COMPLETE,
	    google.ima.AdEvent.Type.FIRST_QUARTILE,
	    google.ima.AdEvent.Type.LOADED,
	    google.ima.AdEvent.Type.MIDPOINT,
	    google.ima.AdEvent.Type.PAUSED,
	    google.ima.AdEvent.Type.STARTED,
	    google.ima.AdEvent.Type.THIRD_QUARTILE
  	];
	
	

  	ads.options = {
    	id: ads.player.id_,
    	nativeControlsForTouch: false
  	};
	
  	switch (ads.settings.ad_type){
  		//DFP Ads
  		case '1':{
  			if (ads.player.options.autoplay){
				ads.player.ima(ads.options,this.bind(this, this.adsManagerLoadedCallback));
				ads.player.ima.initializeAdDisplayContainer();
				ads.player.ima.setContent(null, ads.ad_code, true);
				ads.player.ima.requestAds();
				ads.player.play();

			}else{
				ads.player.one(startEvent, ads.bind(ads, function(){
					ads.player.ima(ads.options,this.bind(this, this.adsManagerLoadedCallback));
					ads.player.ima.initializeAdDisplayContainer();
					ads.player.ima.setContent(null, ads.ad_code, true);
					ads.player.ima.requestAds();
					ads.player.play();	
				}));
			}
  		}
  		break;
  		//custom linear ads Ads
  		case '2':{
  			//Conditioning for pre-roll Ad
  			if (ads.settings.linear_type == 'pre-roll'){

				if (ads.player.options.autoplay){
					ads.initCbAdsPlayerContainer();
					ads.setCbAdsPlayerContents(ads.settings);
					
				}else{
					ads.player.one(startEvent, ads.bind(ads, function(){
						ads.initCbAdsPlayerContainer();
						ads.setCbAdsPlayerContents(ads.settings);
					}));
				}
				ads.LinearAdEvent();
			//Conditioning for post-roll Ad	
  			}else if (ads.settings.linear_type == 'post-roll'){

  				ads.player.one(endEvent, ads.bind(ads, function(){
					ads.initCbAdsPlayerContainer();
					ads.setCbAdsPlayerContents(ads.settings);
					ads.LinearAdEvent();
				}));
  			//Conditioning for mid-roll Ad	
  			}else if (ads.settings.linear_type == 'mid-roll'){
  				
  				var midRollPlayed = false;
  				ads.player.on("timeupdate", ads.bind(ads, function(){
  					var duration = ads.player.duration(),
  					current = ads.player.currentTime(),
                    perc = (current / duration * 100).toFixed(0);
                  
  					if (parseInt(perc) > parseInt(ads.settings.ad_time) && !midRollPlayed){
  						midRollPlayed = true;
  						ads.initCbAdsPlayerContainer();
						ads.setCbAdsPlayerContents(ads.settings);
  						ads.LinearAdEvent();
  					}

				}));

  			}
  		}
  		break;
  		//custom non-linear ads Ads
  		case '3':{
  			if (ads.ad_code || ads.settings.banner){

  				var nonLinearAdPlayed = false;
  				ads.player.on("timeupdate", ads.bind(ads, function(){
  					
  					var duration = ads.player.duration(),
  					current = ads.player.currentTime(),
                    perc = (current / duration * 100).toFixed(0);

                    if (parseInt(perc) > parseInt(ads.settings.ad_time) && !nonLinearAdPlayed){
  						nonLinearAdPlayed = true;
  						ads.initCbNonLinearBox();
  					}

				}));
  				
  			}else{
  				console.log("Ad Event : Non-Linear Ad Failed, no source provided");
  			}
  		}
  		break;
  	}
  	
	
}


Ads.prototype.adsManagerLoadedCallback = function() {
	var ads = this;
	ads.player.ima.addEventListener('start',function(){
		ads.update_impressions(ads.ad_id);
	});
	for (var index = 0; index < ads.events.length; index++) {
		ads.player.ima.addEventListener(
	        ads.events[index],
	        ads.bind(ads, ads.onAdEvent));
	}
  	ads.player.ima.start();
}

Ads.prototype.onAdEvent = function(event) {
    console.log('Ad event: ' + event.type);
}

Ads.prototype.LinearAdEvent = function() {
    console.log('Ad event: ' + this.settings.linear_type);
}

Ads.prototype.update_impressions = function(ad_id){
	update_ad_imp(ad_id);
}

Ads.prototype.bind = function(thisObj, fn) {
  	return function() {
	    fn.apply(thisObj, arguments);
	};
}

/*
* Creats Ads Container
*/
Ads.prototype.initCbAdsPlayerContainer = function() {
    
    var ads = this;
   
    ads.ghaaayebPlayer();
   
   	ads.adsplayer.vjsControls = ads.player.getChild('controlBar');
   	ads.adsplayer.adContainerDiv = ads.adsplayer.vjsControls.el().parentNode.appendChild(document.createElement('div'));
    ads.adsplayer.adContainerDiv.id = 'ima-ad-container';
    ads.adsplayer.adContainerDiv.style.position = "absolute";
    ads.adsplayer.adContainerDiv.style.display = "block";
    ads.adsplayer.adContainerDiv.style.zIndex = 1111;
    ads.createControls_();

    //Binding mouseOut Event on Ads player to Hide
   	ads.adsplayer.adContainerDiv.addEventListener(
   		'mouseout',
   		ads.hideAdControls_.bind(ads),
   		false
   	);
   	//Binding MouseIn Event on Ads player to show
    ads.adsplayer.adContainerDiv.addEventListener(
    	'mouseover',
    	ads.showAdControls_.bind(ads),
    	false
    );

    //Binding MouseIn Event on Ads player to show
    ads.adsplayer.adsManager.addEventListener(
    	'timeupdate',
    	ads.udpateAdPlayer.bind(ads),
    	false
    );

    //Binding MouseIn Event on Ads player to show
    ads.adsplayer.adsManager.addEventListener(
    	'ended',
    	ads.destroyAdsPlayer_.bind(ads),
    	false
    );

    //Binding Click Event on Ads To Navigate to show
    ads.adsplayer.adsManager.addEventListener(
    	'click',
    	ads.targetUrlLinear.bind(ads),
    	false
    );


    //Binding MouseIn Event on Ads player to show
    ads.adsplayer.skipAdDiv.addEventListener(
    	'click',
    	ads.destroyAdsPlayer_.bind(ads),
    	false
    );

}


/*
* Method : this method Dom Elements for Banner Ads
*/
Ads.prototype.targetUrlLinear = function(){
	var ads = this;
	if (ads.settings.target_url && !ads.adsbox.scriptsExists){
		window.open(ads.settings.target_url, '_blank');
	}
}


/*
* Listener : Event hide controls listener
*/
Ads.prototype.hideAdControls_ = function() {

	var ads = this;
	ads.adsplayer.playPauseDiv.style.display = 'none';
	ads.adsplayer.muteDiv.style.display = 'none';
	ads.adsplayer.fullscreenDiv.style.display = 'none';
	ads.adsplayer.controlsDiv.style.height = '14px';

}

/*
* Listener : Event show controls listener
*/  
Ads.prototype.showAdControls_ = function() {

	var ads = this;
	ads.adsplayer.controlsDiv.style.height = '37px';
	ads.adsplayer.playPauseDiv.style.display = 'block';
	ads.adsplayer.muteDiv.style.display = 'block';
	ads.adsplayer.sliderDiv.style.display = 'block';
	ads.adsplayer.fullscreenDiv.style.display = 'block';

}

/*
* Listener : Event show controls listener
*/  
Ads.prototype.udpateAdPlayer = function() {

	var ads = this,
	duration = ads.timeFormat(ads.adsplayer.adsManager.duration),
	currentTime = ads.timeFormat(ads.adsplayer.adsManager.currentTime),
	remainingTime = ads.adsplayer.adsManager.duration - ads.adsplayer.adsManager.currentTime;
	
	remainingTime = ads.timeFormat(remainingTime);
	ads.adsplayer.countdownDiv.innerHTML = ads.adsplayer.adLabel + ' ( '+
          remainingTime + ' : ' + duration +' ) ';

    // Update UI
	var playProgressRatio = ads.adsplayer.adsManager.currentTime / ads.adsplayer.adsManager.duration;
	var playProgressPercent = playProgressRatio * 100;
	ads.adsplayer.progressDiv.style.width = playProgressPercent + '%';

	
	ads.adsplayer.CurrentRoundTime = ads.adsplayer.adsManager.currentTime.toFixed(0);
	var adsremainingTime = ads.adsplayer.skippableTime - ads.adsplayer.CurrentRoundTime;
    ads.adsplayer.skipAdDiv.innerHTML = "you can skip this ad in "+adsremainingTime;	
    
    if (ads.adsplayer.CurrentRoundTime >= ads.adsplayer.skippableTime){
    	ads.adsplayer.skipAdDiv.innerHTML = "Skip Now";	
    }

    
    
}

/*
* Method : This method is to set ad contents an play
*/  
Ads.prototype.setCbAdsPlayerContents = function() {
    var ads = this;
    ads.adsplayer.adsManager.src = ads.ad_code;
   	ads.adsplayer.adsManager.play();
   	ads.adsplayer.adPlaying = true;
   	ads.update_impressions(ads.ad_id);


}

/*
** Urdu Language naming Conventiosn
*/
Ads.prototype.dikhaaoPlayer =  function(){
	
	var ads = this;
	ads.player.controlBar.removeClass('vjs-hidden');
	ads.player.bigPlayButton.removeClass('vjs-hidden');
	ads.player.removeClass('vjs-ad-playing');
	ads.player.removeClass('vjs-user-inactive');
	var header = document.getElementById("vjs-cb-header");
	header.style.display = "block";
    ads.player.play();

}

/*
** Urdu Language naming Conventiosn
*/
Ads.prototype.ghaaayebPlayer =  function(){

	var ads = this;
	ads.player.controlBar.addClass('vjs-hidden');
	ads.player.bigPlayButton.addClass('vjs-hidden');
	ads.player.addClass('vjs-ad-playing');
	ads.player.addClass('vjs-user-inactive');
	var header = document.getElementById("vjs-cb-header");
	header.style.display = "none";
    ads.player.pause();

}

/*
* this method is to create controls of ads player
*/
Ads.prototype.createControls_ = function() {

	var ads = this; 
	ads.adsplayer.controlsDiv = document.createElement('div');
	ads.adsplayer.controlsDiv.id = 'ima-controls-div';
	ads.adsplayer.controlsDiv.style.width = '100%';
	ads.adsplayer.controlsDiv.style.display = 'block';
	ads.adsplayer.video.style.width = "100%";

	ads.adsplayer.countdownDiv = document.createElement('div');
	ads.adsplayer.countdownDiv.id = 'ima-countdown-div';
	ads.adsplayer.countdownDiv.innerHTML = ads.adsplayer.adLabel;
	ads.adsplayer.countdownDiv.style.display = ads.adsplayer.showCountdown ? 'block' : 'none';
	
	ads.adsplayer.skipAdDiv = document.createElement('div');
	ads.adsplayer.skipAdDiv.id = 'ima-skip-ad-div';
	ads.adsplayer.skipAdDiv.innerHTML = 'You can skip this ad in '+ads.adsplayer.skippableTime;

	ads.adsplayer.seekBarDiv = document.createElement('div');
	ads.adsplayer.seekBarDiv.id = 'ima-seek-bar-div';
	ads.adsplayer.seekBarDiv.style.width = '100%';

	
	ads.adsplayer.progressDiv = document.createElement('div');
	ads.adsplayer.progressDiv.id = 'ima-progress-div';
	
	ads.adsplayer.playPauseDiv = document.createElement('div');
	ads.adsplayer.playPauseDiv.id = 'ima-play-pause-div';
	ads.adsplayer.playPauseDiv.className = 'ima-playing';
	ads.adsplayer.playPauseDiv.addEventListener(
		'click',
		ads.onAdPlayPauseClick_.bind(ads),
		false
	);
	
	ads.adsplayer.muteDiv = document.createElement('div');
	ads.adsplayer.muteDiv.id = 'ima-mute-div';
	ads.adsplayer.muteDiv.className = 'ima-non-muted';
	ads.adsplayer.muteDiv.addEventListener(
		'click',
		ads.onAdMuteClick_.bind(ads),
		false
	);
	
	ads.adsplayer.sliderDiv = document.createElement('div');
	ads.adsplayer.sliderDiv.id = 'ima-slider-div';
	ads.adsplayer.sliderDiv.addEventListener(
		'click',
		ads.setVolumeSlider_.bind(ads),
		false
	);
	//ads.adsplayer.sliderDiv.addEventListener('mousedown',ads.onAdVolumeSliderMouseDown_,false);
	
	ads.adsplayer.sliderLevelDiv = document.createElement('div');
	ads.adsplayer.sliderLevelDiv.id = 'ima-slider-level-div';
	
	ads.adsplayer.fullscreenDiv = document.createElement('div');
	ads.adsplayer.fullscreenDiv.id = 'ima-fullscreen-div';
	ads.adsplayer.fullscreenDiv.className = 'ima-non-fullscreen';
	ads.adsplayer.fullscreenDiv.addEventListener(
		'click',
		ads.onAdFullscreenClick_.bind(ads),
		false
	);
	
	ads.adsplayer.adContainerDiv.appendChild(ads.adsplayer.video);
	ads.adsplayer.adContainerDiv.appendChild(ads.adsplayer.controlsDiv);
	ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.countdownDiv);
	if (ads.settings.skippable == 'yes'){
		ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.skipAdDiv);
	}
	ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.seekBarDiv);
	ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.playPauseDiv);
	ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.muteDiv);
	ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.sliderDiv);
	ads.adsplayer.controlsDiv.appendChild(ads.adsplayer.fullscreenDiv);
	ads.adsplayer.seekBarDiv.appendChild(ads.adsplayer.progressDiv);
	ads.adsplayer.sliderDiv.appendChild(ads.adsplayer.sliderLevelDiv);
	ads.adsplayer.adsManager = document.getElementById('ads-player');
	ads.adsplayer.adsManager.style.cursor = "pointer";

}

/*
* Listener : to playPause Video
*/  
Ads.prototype.onAdPlayPauseClick_ = function() {
	var ads = this ;
    if (ads.adsplayer.adPlaying) {

        ads.adsplayer.playPauseDiv.className = 'ima-paused';
        ads.adsplayer.adsManager.pause();
        ads.adsplayer.adPlaying = false;

    } else {

        ads.adsplayer.playPauseDiv.className = 'ima-playing';
        ads.adsplayer.adsManager.play();
        ads.adsplayer.adPlaying = true;

    }
}

/*
* Listener : to Mute Video Ad
*/  
Ads.prototype.onAdMuteClick_ = function() {
	var ads = this ;
	if (ads.adsplayer.adMuted) {
		ads.adsplayer.muteDiv.className = 'ima-non-muted';
		ads.adsplayer.adsManager.muted = false
		ads.adsplayer.adMuted = false;
		ads.adsplayer.sliderLevelDiv.style.width = ads.adsplayer.adsManager.volume * 100 + "%";
	} else {
	    ads.adsplayer.muteDiv.className = 'ima-muted';
		ads.adsplayer.adsManager.muted = true;
		ads.adsplayer.adMuted = true;
		ads.adsplayer.sliderLevelDiv.style.width = "0%";
	}
}

/*
* Listener : to set Volume Video Ad
*/ 
Ads.prototype.setVolumeSlider_ = function(event) {
	var ads = this;
	var percent = (event.clientX - ads.adsplayer.sliderDiv.getBoundingClientRect().left) /
      ads.adsplayer.sliderDiv.offsetWidth;
	percent *= 100;
	//Bounds value 0-100 if mouse is outside slider region.
	percent = Math.min(Math.max(percent, 0), 100);
	ads.adsplayer.sliderLevelDiv.style.width = percent + "%";
	ads.adsplayer.adsManager.volume = percent / 100; 
	
	if (ads.adsplayer.adsManager.volume == 0) {
		ads.adsplayer.muteDiv.className = 'ima-muted';
		ads.adsplayer.adsManager.muted = true;
		ads.adsplayer.adMuted = true;
	}else{
		ads.adsplayer.muteDiv.className = 'ima-non-muted';
		ads.adsplayer.adsManager.muted = true;
		ads.adsplayer.adMuted = false;
	}
}

/*
* Listener : to request or exit fullScreen Video Ad Player
*/ 
Ads.prototype.onAdFullscreenClick_ = function() {
	
	var ads = this ;
	if (ads.isFunction(ads.adsplayer.adsManager.webkitRequestFullScreen)){
		if (ads.adsplayer.isFullscreen){
			ads.adsplayer.isFullscreen == false;
			document.mozCancelFullScreen();
		}else{
			ads.adsplayer.isFullscreen == true;
			ads.adsplayer.adsManager.webkitRequestFullScreen();	
		}
	}else if (ads.isFunction(ads.adsplayer.adsManager.mozRequestFullScreen)){
		if (ads.adsplayer.isFullscreen){
			ads.adsplayer.isFullscreen == false;
			document.mozCancelFullScreen();
		}else{
			ads.adsplayer.isFullscreen == true;
			ads.adsplayer.adsManager.mozRequestFullScreen();	
		}
	}

}

/*
* Listener : to destroy Ads player 
*/
Ads.prototype.destroyAdsPlayer_ = function(){
	var ads = this;
	if (ads.adsplayer.CurrentRoundTime >= 5){
		ads.adsplayer.adContainerDiv.style.display = "none";
		ads.adsplayer.adsManager.pause();
		ads.adsplayer.adsManager.src = "";
		ads.adsplayer.adPlaying = false;
		ads.dikhaaoPlayer();
	}
	
}

/*
* Method : this method Dom Elements for Banner Ads
*/
Ads.prototype.initCbNonLinearBox = function(){
	
	var ads = this;

	ads.adsbox.adContainerDiv = document.createElement("div");
	ads.adsbox.adContainerDiv.id = "linear-ads-container";
	ads.adsbox.adContainerDiv.style.width = "100%";
	ads.adsbox.adContainerDiv.style.display = "block";

	ads.adsbox.adImgHolderDiv = document.createElement("div");
	ads.adsbox.adImgHolderDiv.id = "linear-ads-img-holder";
	ads.adsbox.adImgHolderDiv.className = "adImg";


	var myCode = ads.ad_code;
	ads.adsbox.scriptsExists = myCode.includes("script");

	ads.adsbox.adImg = document.createElement("img");
	if (ads.settings.banner){
		ads.adsbox.adImg.src = ads.settings.banner;
	}else{
		if (ads.adsbox.scriptsExists){
			var parser = new DOMParser();
    		var myAdHtml  = parser.parseFromString(ads.ad_code, "text/html");
    		ads.adsbox.adImg = myAdHtml.documentElement;

		}else{
			ads.adsbox.adImg.src = ads.ad_code;
		}
	}

	ads.adsbox.adCloseBtn = document.createElement("button");
	ads.adsbox.adCloseBtn.id = "linear-ads-close";
	ads.adsbox.adCloseBtn.innerHTML = "<i class='glyphicon glyphicon-remove'></i>";

	ads.player.el_.appendChild(ads.adsbox.adContainerDiv);
	ads.adsbox.adContainerDiv.appendChild(ads.adsbox.adImgHolderDiv);
	ads.adsbox.adImgHolderDiv.appendChild(ads.adsbox.adImg);
	ads.adsbox.adImgHolderDiv.appendChild(ads.adsbox.adCloseBtn);

	//Binding MouseIn Event on Ads player to show
    ads.adsbox.adCloseBtn.addEventListener(
    	'click',
    	ads.destroyAdsBox.bind(ads),
    	false
    );

    //Binding MouseIn Event on Ads player to show
    ads.adsbox.adImg.addEventListener(
    	'click',
    	ads.targetUrl.bind(ads),
    	false
    );


}

/*
* Method : this method Dom Elements for Banner Ads
*/
Ads.prototype.destroyAdsBox = function(){
	var ads = this;
	ads.adsbox.adContainerDiv.remove();
}

/*
* Method : this method Dom Elements for Banner Ads
*/
Ads.prototype.targetUrl = function(){
	var ads = this;
	if (ads.settings.target_url && !ads.adsbox.scriptsExists){
		window.open(ads.settings.target_url, '_blank');
	}
}

/*
* This function is used to check if someFunction exists in DOM!
* Copied from StackOverflow
*/
Ads.prototype.isFunction = function(functionToCheck){
	var getType = {};
 	return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';

}

Ads.prototype.timeFormat = function(seconds){
	var m = Math.floor(seconds/60)<10 ? "0"+Math.floor(seconds/60) : Math.floor(seconds/60);
	var s = Math.floor(seconds-(m*60))<10 ? "0"+Math.floor(seconds-(m*60)) : Math.floor(seconds-(m*60));
	return m+":"+s;
};

var cb_ultimate_ads = function(settings){
    var ultimate_ads =  new Ads(this,settings);
}



videojs.plugin('cb_ultimate_ads',cb_ultimate_ads);


/*if (navigator.userAgent.match(/iPhone/i) ||  navigator.userAgent.match(/iPad/i) ||navigator.userAgent.match(/Android/i)) 
{
    console.log("iphone/Android");
    //startEvent = 'tap';
}*/