/**
 * All in one files with all necessary and and required plugins which are must for the player
*/

/**
 * Videojs.ads.js plugin for ads
*/

/**
 * Basic Ad support plugin for video.js.
 *
 * Common code to support ad integrations.
 */
(function(window, videojs, undefined) {
'use strict';

var

  VIDEO_EVENTS = videojs.getComponent('Html5').Events,

  /**
   * If ads are not playing, pauses the player at the next available
   * opportunity. Has no effect if ads have started. This function is necessary
   * because pausing a video element while processing a `play` event on iOS can
   * cause the video element to continuously toggle between playing and paused
   * states.
   *
   * @param {object} player The video player
   */
  cancelContentPlay = function(player) {
    if (player.ads.cancelPlayTimeout) {
      // another cancellation is already in flight, so do nothing
      return;
    }
    player.ads.cancelPlayTimeout = window.setTimeout(function() {
      // deregister the cancel timeout so subsequent cancels are scheduled
      player.ads.cancelPlayTimeout = null;

      // pause playback so ads can be handled.
      if (!player.paused()) {
        player.pause();
      }

      // add a contentplayback handler to resume playback when ads finish.
      player.one('contentplayback', function() {
        if (player.paused()) {
          player.play();
        }
      });
    }, 1);
  },

  /**
   * Returns an object that captures the portions of player state relevant to
   * video playback. The result of this function can be passed to
   * restorePlayerSnapshot with a player to return the player to the state it
   * was in when this function was invoked.
   * @param {object} player The videojs player object
   */
  getPlayerSnapshot = function(player) {
    var
      tech = player.$('.vjs-tech'),
      tracks = player.remoteTextTracks ? player.remoteTextTracks() : [],
      track,
      i,
      suppressedTracks = [],
      snapshot = {
        ended: player.ended(),
        currentSrc: player.currentSrc(),
        src: player.src(),
        currentTime: player.currentTime(),
        type: player.currentType()
      };

    if (tech) {
      snapshot.nativePoster = tech.poster;
      snapshot.style = tech.getAttribute('style');
    }

    i = tracks.length;
    while (i--) {
      track = tracks[i];
      suppressedTracks.push({
        track: track,
        mode: track.mode
      });
      track.mode = 'disabled';
    }
    snapshot.suppressedTracks = suppressedTracks;

    return snapshot;
  },

  /**
   * Attempts to modify the specified player so that its state is equivalent to
   * the state of the snapshot.
   * @param {object} snapshot - the player state to apply
   */
  restorePlayerSnapshot = function(player, snapshot) {
    var
      // the playback tech
      tech = player.$('.vjs-tech'),

      // the number of remaining attempts to restore the snapshot
      attempts = 20,

      suppressedTracks = snapshot.suppressedTracks,
      trackSnapshot,
      restoreTracks =  function() {
        var i = suppressedTracks.length;
        while (i--) {
          trackSnapshot = suppressedTracks[i];
          trackSnapshot.track.mode = trackSnapshot.mode;
        }
      },

      // finish restoring the playback state
      resume = function() {
        var
          ended = false,
          updateEnded = function() {
            ended = true;
          };
        player.currentTime(snapshot.currentTime);

        // Resume playback if this wasn't a postroll
        if (!snapshot.ended) {
          player.play();
        } else {
          // On iOS 8.1, the "ended" event will not fire if you seek
          // directly to the end of a video. To make that behavior
          // consistent with the standard, fire a synthetic event if
          // "ended" does not fire within 250ms. Note that the ended
          // event should occur whether the browser actually has data
          // available for that position
          // (https://html.spec.whatwg.org/multipage/embedded-content.html#seeking),
          // so it should not be necessary to wait for the seek to
          // indicate completion.
          player.ads.resumeEndedTimeout = window.setTimeout(function() {
            if (!ended) {
              player.play();
            }
            player.off('ended', updateEnded);
            player.ads.resumeEndedTimeout = null;
          }, 250);
          player.on('ended', updateEnded);

          // Need to clear the resume/ended timeout on dispose. If it fires
          // after a player is disposed, an error will be thrown!
          player.on('dispose', function() {
            window.clearTimeout(player.ads.resumeEndedTimeout);
          });
        }
      },

      // determine if the video element has loaded enough of the snapshot source
      // to be ready to apply the rest of the state
      tryToResume = function() {

        // tryToResume can either have been called through the `contentcanplay`
        // event or fired through setTimeout.
        // When tryToResume is called, we should make sure to clear out the other
        // way it could've been called by removing the listener and clearing out
        // the timeout.
        player.off('contentcanplay', tryToResume);
        if (player.ads.tryToResumeTimeout_) {
          player.clearTimeout(player.ads.tryToResumeTimeout_);
          player.ads.tryToResumeTimeout_ = null;
        }

        // Tech may have changed depending on the differences in sources of the
        // original video and that of the ad
        tech = player.el().querySelector('.vjs-tech');

        if (tech.readyState > 1) {
          // some browsers and media aren't "seekable".
          // readyState greater than 1 allows for seeking without exceptions
          return resume();
        }

        if (tech.seekable === undefined) {
          // if the tech doesn't expose the seekable time ranges, try to
          // resume playback immediately
          return resume();
        }

        if (tech.seekable.length > 0) {
          // if some period of the video is seekable, resume playback
          return resume();
        }

        // delay a bit and then check again unless we're out of attempts
        if (attempts--) {
          window.setTimeout(tryToResume, 50);
        } else {
          (function() {
            try {
              resume();
            } catch(e) {
              videojs.log.warn('Failed to resume the content after an advertisement', e);
            }
          })();
        }
      },

      // whether the video element has been modified since the
      // snapshot was taken
      srcChanged;

    if (snapshot.nativePoster) {
      tech.poster = snapshot.nativePoster;
    }

    if ('style' in snapshot) {
      // overwrite all css style properties to restore state precisely
      tech.setAttribute('style', snapshot.style || '');
    }

    // Determine whether the player needs to be restored to its state
    // before ad playback began. With a custom ad display or burned-in
    // ads, the content player state hasn't been modified and so no
    // restoration is required

    srcChanged = player.src() !== snapshot.src || player.currentSrc() !== snapshot.currentSrc;

    if (srcChanged) {
      // on ios7, fiddling with textTracks too early will cause safari to crash
      player.one('contentloadedmetadata', restoreTracks);

      // if the src changed for ad playback, reset it
      player.src({ src: snapshot.currentSrc, type: snapshot.type });
      // safari requires a call to `load` to pick up a changed source
      player.load();
      // and then resume from the snapshots time once the original src has loaded
      // in some browsers (firefox) `canplay` may not fire correctly.
      // Reace the `canplay` event with a timeout.
      player.one('contentcanplay', tryToResume);
      player.ads.tryToResumeTimeout_ = player.setTimeout(tryToResume, 2000);
    } else if (!player.ended() || !snapshot.ended) {
      // if we didn't change the src, just restore the tracks
      restoreTracks();
      // the src didn't change and this wasn't a postroll
      // just resume playback at the current time.
      player.play();
    }
  },

  /**
   * Remove the poster attribute from the video element tech, if present. When
   * reusing a video element for multiple videos, the poster image will briefly
   * reappear while the new source loads. Removing the attribute ahead of time
   * prevents the poster from showing up between videos.
   * @param {object} player The videojs player object
   */
  removeNativePoster = function(player) {
    var tech = player.$('.vjs-tech');
    if (tech) {
      tech.removeAttribute('poster');
    }
  },

  // ---------------------------------------------------------------------------
  // Ad Framework
  // ---------------------------------------------------------------------------

  // default framework settings
  defaults = {
    // maximum amount of time in ms to wait to receive `adsready` from the ad
    // implementation after play has been requested. Ad implementations are
    // expected to load any dynamic libraries and make any requests to determine
    // ad policies for a video during this time.
    timeout: 5000,

    // maximum amount of time in ms to wait for the ad implementation to start
    // linear ad mode after `readyforpreroll` has fired. This is in addition to
    // the standard timeout.
    prerollTimeout: 100,

    // maximum amount of time in ms to wait for the ad implementation to start
    // linear ad mode after `contentended` has fired.
    postrollTimeout: 100,

    // when truthy, instructs the plugin to output additional information about
    // plugin state to the video.js log. On most devices, the video.js log is
    // the same as the developer console.
    debug: false
  },

  adFramework = function(options) {
    var player = this;
    var settings = videojs.mergeOptions(defaults, options);
    var fsmHandler;

    // prefix all video element events during ad playback
    // if the video element emits ad-related events directly,
    // plugins that aren't ad-aware will break. prefixing allows
    // plugins that wish to handle ad events to do so while
    // avoiding the complexity for common usage
    (function() {
      var videoEvents = VIDEO_EVENTS.concat([
        'firstplay',
        'loadedalldata'
      ]);

      var returnTrue = function() { return true; };

      var triggerEvent = function(type, event) {
        // pretend we called stopImmediatePropagation because we want the native
        // element events to continue propagating
        event.isImmediatePropagationStopped = returnTrue;
        event.cancelBubble = true;
        event.isPropagationStopped = returnTrue;
        player.trigger({
          type: type + event.type,
          state: player.ads.state,
          originalEvent: event
        });
      };

      player.on(videoEvents, function redispatch(event) {
        if (player.ads.state === 'ad-playback') {
          triggerEvent('ad', event);
        } else if (player.ads.state === 'content-playback' && event.type === 'ended') {
          triggerEvent('content', event);
        } else if (player.ads.state === 'content-resuming') {
          if (player.ads.snapshot) {
            // the video element was recycled for ad playback
            if (player.currentSrc() !== player.ads.snapshot.currentSrc) {
              if (event.type === 'loadstart') {
                return;
              }
              return triggerEvent('content', event);

            // we ended playing postrolls and the video itself
            // the content src is back in place
            } else if (player.ads.snapshot.ended) {
              if ((event.type === 'pause' ||
                  event.type === 'ended')) {
                // after loading a video, the natural state is to not be started
                // in this case, it actually has, so, we do it manually
                player.addClass('vjs-has-started');
                // let `pause` and `ended` events through, naturally
                return;
              }
              // prefix all other events in content-resuming with `content`
              return triggerEvent('content', event);
            }
          }
          if (event.type !== 'playing') {
            triggerEvent('content', event);
          }
        }
      });
    })();

    // We now auto-play when an ad gets loaded if we're playing ads in the same video element as the content.
    // The problem is that in IE11, we cannot play in addurationchange but in iOS8, we cannot play from adcanplay.
    // This will allow ad-integrations from needing to do this themselves.
    player.on(['addurationchange', 'adcanplay'], function() {
      if (player.currentSrc() === player.ads.snapshot.currentSrc) {
        return;
      }

      player.play();
    });

    player.on('nopreroll', function() {
      player.ads.nopreroll_ = true;
    });

    player.on('nopostroll', function() {
      player.ads.nopostroll_ = true;
    });

    // replace the ad initializer with the ad namespace
    player.ads = {
      state: 'content-set',

      // Call this when an ad response has been received and there are
      // linear ads ready to be played.
      startLinearAdMode: function() {
        if (player.ads.state === 'preroll?' ||
            player.ads.state === 'content-playback' ||
            player.ads.state === 'postroll?') {
          player.trigger('adstart');
        }
      },

      // Call this when a linear ad pod has finished playing.
      endLinearAdMode: function() {
        if (player.ads.state === 'ad-playback') {
          player.trigger('adend');
        }
      },

      // Call this when an ad response has been received but there are no
      // linear ads to be played (i.e. no ads available, or overlays).
      // This has no effect if we are already in a linear ad mode.  Always
      // use endLinearAdMode() to exit from linear ad-playback state.
      skipLinearAdMode: function() {
        if (player.ads.state !== 'ad-playback') {
          player.trigger('adskip');
        }
      }
    };

    fsmHandler = function(event) {
      // Ad Playback State Machine
      var fsm = {
        'content-set': {
          events: {
            'adscanceled': function() {
              this.state = 'content-playback';
            },
            'adsready': function() {
              this.state = 'ads-ready';
            },
            'play': function() {
              this.state = 'ads-ready?';
              cancelContentPlay(player);
              // remove the poster so it doesn't flash between videos
              removeNativePoster(player);
            },
            'adserror': function() {
              this.state = 'content-playback';
            },
            'adskip': function() {
              this.state = 'content-playback';
            }
          }
        },
        'ads-ready': {
          events: {
            'play': function() {
              this.state = 'preroll?';
              cancelContentPlay(player);
            },
            'adskip': function() {
              this.state = 'content-playback';
            },
            'adserror': function() {
              this.state = 'content-playback';
            }
          }
        },
        'preroll?': {
          enter: function() {
            if (player.ads.nopreroll_) {
              // This will start the ads manager in case there are later ads
              player.trigger('readyforpreroll');
              // Don't wait for a preroll
              player.trigger('nopreroll');
            } else {
              // change class to show that we're waiting on ads
              player.addClass('vjs-ad-loading');
              // schedule an adtimeout event to fire if we waited too long
              player.ads.adTimeoutTimeout = window.setTimeout(function() {
                player.trigger('adtimeout');
              }, settings.prerollTimeout);
              // signal to ad plugin that it's their opportunity to play a preroll
              player.trigger('readyforpreroll');
            }
          },
          leave: function() {
            window.clearTimeout(player.ads.adTimeoutTimeout);
            player.removeClass('vjs-ad-loading');
          },
          events: {
            'play': function() {
              cancelContentPlay(player);
            },
            'adstart': function() {
              this.state = 'ad-playback';
            },
            'adskip': function() {
              this.state = 'content-playback';
            },
            'adtimeout': function() {
              this.state = 'content-playback';
            },
            'adserror': function() {
              this.state = 'content-playback';
            },
            'nopreroll': function() {
              this.state = 'content-playback';
            }
          }
        },
        'ads-ready?': {
          enter: function() {
            player.addClass('vjs-ad-loading');
            player.ads.adTimeoutTimeout = window.setTimeout(function() {
              player.trigger('adtimeout');
            }, settings.timeout);
          },
          leave: function() {
            window.clearTimeout(player.ads.adTimeoutTimeout);
            player.removeClass('vjs-ad-loading');
          },
          events: {
            'play': function() {
              cancelContentPlay(player);
            },
            'adscanceled': function() {
              this.state = 'content-playback';
            },
            'adsready': function() {
              this.state = 'preroll?';
            },
            'adskip': function() {
              this.state = 'content-playback';
            },
            'adtimeout': function() {
              this.state = 'content-playback';
            },
            'adserror': function() {
              this.state = 'content-playback';
            }
          }
        },
        'ad-playback': {
          enter: function() {
            // capture current player state snapshot (playing, currentTime, src)
            this.snapshot = getPlayerSnapshot(player);

            // add css to the element to indicate and ad is playing.
            player.addClass('vjs-ad-playing');

            // remove the poster so it doesn't flash between ads
            removeNativePoster(player);

            // We no longer need to supress play events once an ad is playing.
            // Clear it if we were.
            if (player.ads.cancelPlayTimeout) {
              window.clearTimeout(player.ads.cancelPlayTimeout);
              player.ads.cancelPlayTimeout = null;
            }
          },
          leave: function() {
            player.removeClass('vjs-ad-playing');
            restorePlayerSnapshot(player, this.snapshot);
            // trigger 'adend' as a consistent notification
            // event that we're exiting ad-playback.
            if (player.ads.triggerevent !== 'adend') {
              player.trigger('adend');
            }
          },
          events: {
            'adend': function() {
              this.state = 'content-resuming';
            },
            'adserror': function() {
              this.state = 'content-resuming';
            }
          }
        },
        'content-resuming': {
          enter: function() {
            if (this.snapshot.ended) {
              window.clearTimeout(player.ads._fireEndedTimeout);
              // in some cases, ads are played in a swf or another video element
              // so we do not get an ended event in this state automatically.
              // If we don't get an ended event we can use, we need to trigger
              // one ourselves or else we won't actually ever end the current video.
              player.ads._fireEndedTimeout = window.setTimeout(function() {
                player.trigger('ended');
              }, 1000);
            }
          },
          leave: function() {
            window.clearTimeout(player.ads._fireEndedTimeout);
          },
          events: {
            'contentupdate': function() {
              this.state = 'content-set';
            },
            contentresumed: function() {
              this.state = 'content-playback';
            },
            'playing': function() {
              this.state = 'content-playback';
            },
            'ended': function() {
              this.state = 'content-playback';
            }
          }
        },
        'postroll?': {
          enter: function() {
            this.snapshot = getPlayerSnapshot(player);

            player.addClass('vjs-ad-loading');

            player.ads.adTimeoutTimeout = window.setTimeout(function() {
              player.trigger('adtimeout');
            }, settings.postrollTimeout);
          },
          leave: function() {
            window.clearTimeout(player.ads.adTimeoutTimeout);
            player.removeClass('vjs-ad-loading');
          },
          events: {
            'adstart': function() {
              this.state = 'ad-playback';
            },
            'adskip': function() {
              this.state = 'content-resuming';
              window.setTimeout(function() {
                player.trigger('ended');
              }, 1);
            },
            'adtimeout': function() {
              this.state = 'content-resuming';
              window.setTimeout(function() {
                player.trigger('ended');
              }, 1);
            },
            'adserror': function() {
              this.state = 'content-resuming';
              window.setTimeout(function() {
                player.trigger('ended');
              }, 1);
            }
          }
        },
        'content-playback': {
          enter: function() {
            // make sure that any cancelPlayTimeout is cleared
            if (player.ads.cancelPlayTimeout) {
              window.clearTimeout(player.ads.cancelPlayTimeout);
              player.ads.cancelPlayTimeout = null;
            }
            // this will cause content to start if a user initiated
            // 'play' event was canceled earlier.
            player.trigger({
              type: 'contentplayback',
              triggerevent: player.ads.triggerevent
            });
          },
          events: {
            // in the case of a timeout, adsready might come in late.
            'adsready': function() {
              player.trigger('readyforpreroll');
            },
            'adstart': function() {
              this.state = 'ad-playback';
            },
            'contentupdate': function() {
              if (player.paused()) {
                this.state = 'content-set';
              } else {
                this.state = 'ads-ready?';
              }
            },
            'contentended': function() {
              this.state = 'postroll?';
            }
          }
        }
      };

      (function(state) {
        var noop = function() {};

        // process the current event with a noop default handler
        ((fsm[state].events || {})[event.type] || noop).apply(player.ads);

        // check whether the state has changed
        if (state !== player.ads.state) {

          // record the event that caused the state transition
          player.ads.triggerevent = event.type;

          // execute leave/enter callbacks if present
          (fsm[state].leave || noop).apply(player.ads);
          (fsm[player.ads.state].enter || noop).apply(player.ads);

          // output debug logging
          if (settings.debug) {
            videojs.log('ads', player.ads.triggerevent + ' triggered: ' + state + ' -> ' + player.ads.state);
          }
        }

      })(player.ads.state);

    };

    // register for the events we're interested in
    player.on(VIDEO_EVENTS.concat([
      // events emitted by ad plugin
      'adtimeout',
      'contentupdate',
      'contentplaying',
      'contentended',
      'contentresumed',

      // events emitted by third party ad implementors
      'adsready',
      'adserror',
      'adscanceled',
      'adstart',  // startLinearAdMode()
      'adend',    // endLinearAdMode()
      'adskip',   // skipLinearAdMode()
      'nopreroll'
    ]), fsmHandler);

    // keep track of the current content source
    // if you want to change the src of the video without triggering
    // the ad workflow to restart, you can update this variable before
    // modifying the player's source
    player.ads.contentSrc = player.currentSrc();

    // implement 'contentupdate' event.
    (function(){
      var
        // check if a new src has been set, if so, trigger contentupdate
        checkSrc = function() {
          var src;
          if (player.ads.state !== 'ad-playback') {
            src = player.currentSrc();
            if (src !== player.ads.contentSrc) {
              player.trigger({
                type: 'contentupdate',
                oldValue: player.ads.contentSrc,
                newValue: src
              });
              player.ads.contentSrc = src;
            }
          }
        };
      // loadstart reliably indicates a new src has been set
      player.on('loadstart', checkSrc);
      // check immediately in case we missed the loadstart
      window.setTimeout(checkSrc, 1);
    })();

    // kick off the fsm
    if (!player.paused()) {
      // simulate a play event if we're autoplaying
      fsmHandler({type:'play'});
    }

  };

  // register the ad plugin framework
  videojs.plugin('ads', adFramework);

})(window, videojs);


/**
 * Videojs.ima.js plugin
*/

 /**
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * IMA SDK integration plugin for Video.js. For more information see
 * https://www.github.com/googleads/videojs-ima
 */

(function(vjs) {
  'use strict';
  var extend = function(obj) {
    var arg;
    var index;
    var key;
    for (index = 1; index < arguments.length; index++) {
      arg = arguments[index];
      for (key in arg) {
        if (arg.hasOwnProperty(key)) {
          obj[key] = arg[key];
        }
      }
    }
    return obj;
  },

  ima_defaults = {
    debug: false,
    timeout: 5000,
    prerollTimeout: 100,
    adLabel: 'Advertisement'
  },

  imaPlugin = function(options, readyCallback) {
    var player = this;

    /**
     * Creates the ad container passed to the IMA SDK.
     * @private
     */
    player.ima.createAdContainer_ = function() {
      // The adContainerDiv is the DOM of the element that will house
      // the ads and ad controls.
      vjsControls = player.getChild('controlBar');
      adContainerDiv =
          vjsControls.el().parentNode.appendChild(
              document.createElement('div'));
      adContainerDiv.id = 'ima-ad-container';
      adContainerDiv.style.position = "absolute";
      adContainerDiv.style.zIndex = 1111;
      adContainerDiv.addEventListener(
          'mouseover',
          player.ima.showAdControls_,
          false);
      adContainerDiv.addEventListener(
          'mouseout',
          player.ima.hideAdControls_,
          false);
      player.ima.createControls_();
      adDisplayContainer =
          new google.ima.AdDisplayContainer(adContainerDiv, contentPlayer);
    };

    /**
     * Creates the controls for the ad.
     * @private
     */
    player.ima.createControls_ = function() {
      controlsDiv = document.createElement('div');
      controlsDiv.id = 'ima-controls-div';
      controlsDiv.style.width = '100%';
      countdownDiv = document.createElement('div');
      countdownDiv.id = 'ima-countdown-div';
      countdownDiv.innerHTML = settings.adLabel;
      countdownDiv.style.display = showCountdown ? 'block' : 'none';
      seekBarDiv = document.createElement('div');
      seekBarDiv.id = 'ima-seek-bar-div';
      seekBarDiv.style.width = '100%';
      progressDiv = document.createElement('div');
      progressDiv.id = 'ima-progress-div';
      playPauseDiv = document.createElement('div');
      playPauseDiv.id = 'ima-play-pause-div';
      playPauseDiv.className = 'ima-playing';
      playPauseDiv.addEventListener(
          'click',
          player.ima.onAdPlayPauseClick_,
          false);
      muteDiv = document.createElement('div');
      muteDiv.id = 'ima-mute-div';
      muteDiv.className = 'ima-non-muted';
      muteDiv.addEventListener(
          'click',
          player.ima.onAdMuteClick_,
          false);
      sliderDiv = document.createElement('div');
      sliderDiv.id = 'ima-slider-div';
      sliderDiv.addEventListener(
          'mousedown',
          player.ima.onAdVolumeSliderMouseDown_,
          false);
      sliderLevelDiv = document.createElement('div');
      sliderLevelDiv.id = 'ima-slider-level-div';
      fullscreenDiv = document.createElement('div');
      fullscreenDiv.id = 'ima-fullscreen-div';
      fullscreenDiv.className = 'ima-non-fullscreen';
      fullscreenDiv.addEventListener(
          'click',
          player.ima.onAdFullscreenClick_,
          false);
      adContainerDiv.appendChild(controlsDiv);
      controlsDiv.appendChild(countdownDiv);
      controlsDiv.appendChild(seekBarDiv);
      controlsDiv.appendChild(playPauseDiv);
      controlsDiv.appendChild(muteDiv);
      controlsDiv.appendChild(sliderDiv);
      controlsDiv.appendChild(fullscreenDiv);
      seekBarDiv.appendChild(progressDiv);
      sliderDiv.appendChild(sliderLevelDiv);
    };

    /**
     * Initializes the AdDisplayContainer. On mobile, this must be done as a
     * result of user action.
     */
    player.ima.initializeAdDisplayContainer = function() {
      adDisplayContainerInitialized = true;
      adDisplayContainer.initialize();
    }

    /**
     * Creates the AdsRequest and request ads through the AdsLoader.
     */
    player.ima.requestAds = function() {
      if (!adDisplayContainerInitialized) {
        adDisplayContainer.initialize();
      }
      var adsRequest = new google.ima.AdsRequest();
      adsRequest.adTagUrl = settings.adTagUrl;
      if (settings.forceNonLinearFullSlot) {
        adsRequest.forceNonLinearFullSlot = true;
      }

      adsRequest.linearAdSlotWidth = player.ima.getPlayerWidth();
      adsRequest.linearAdSlotHeight = player.ima.getPlayerHeight();
      adsRequest.nonLinearAdSlotWidth =
          settings.nonLinearWidth || player.ima.getPlayerWidth();
      adsRequest.nonLinearAdSlotHeight =
          settings.nonLinearHeight || (player.ima.getPlayerHeight() / 3);

      adsLoader.requestAds(adsRequest);
    };

    /**
     * Listener for the ADS_MANAGER_LOADED event. Creates the AdsManager,
     * sets up event listeners, and triggers the 'adsready' event for
     * videojs-ads-contrib.
     * @private
     */
    player.ima.onAdsManagerLoaded_ = function(adsManagerLoadedEvent) {
      adsManager = adsManagerLoadedEvent.getAdsManager(
          contentPlayheadTracker, adsRenderingSettings);

      adsManager.addEventListener(
          google.ima.AdErrorEvent.Type.AD_ERROR,
          player.ima.onAdError_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.AD_BREAK_READY,
          player.ima.onAdBreakReady_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.CONTENT_PAUSE_REQUESTED,
          player.ima.onContentPauseRequested_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.CONTENT_RESUME_REQUESTED,
          player.ima.onContentResumeRequested_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.ALL_ADS_COMPLETED,
          player.ima.onAllAdsCompleted_);

      adsManager.addEventListener(
          google.ima.AdEvent.Type.LOADED,
          player.ima.onAdLoaded_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.STARTED,
          player.ima.onAdStarted_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.CLICK,
          player.ima.onAdPlayPauseClick_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.COMPLETE,
          player.ima.onAdComplete_);
      adsManager.addEventListener(
          google.ima.AdEvent.Type.SKIPPED,
          player.ima.onAdComplete_);

      if (!autoPlayAdBreaks) {
        try {
          var initWidth = player.ima.getPlayerWidth();
          var initHeight = player.ima.getPlayerHeight();
          adsManagerDimensions.width = initWidth;
          adsManagerDimensions.height = initHeight;
          adsManager.init(
              initWidth,
              initHeight,
              google.ima.ViewMode.NORMAL);
          adsManager.setVolume(player.muted() ? 0 : player.volume());
        } catch (adError) {
          player.ima.onAdError_(adError);
        }
      }

      player.trigger('adsready');
    };

    /**
     * Start ad playback, or content video playback in the absence of a
     * pre-roll.
     */
    player.ima.start = function() {
      if (autoPlayAdBreaks) {
        try {
          adsManager.init(
              player.ima.getPlayerWidth(),
              player.ima.getPlayerHeight(),
              google.ima.ViewMode.NORMAL);
          adsManager.setVolume(player.muted() ? 0 : player.volume());
          adsManager.start();
        } catch (adError) {
          player.ima.onAdError_(adError);
        }
      }
    };

    /**
     * Listener for errors fired by the AdsLoader.
     * @param {google.ima.AdErrorEvent} event The error event thrown by the
     *     AdsLoader. See
     *     https://developers.google.com/interactive-media-ads/docs/sdks/html5/v3/apis#ima.AdError.Type
     * @private
     */
    player.ima.onAdsLoaderError_ = function(event) {
      window.console.log('AdsLoader error: ' + event.getError());
      if (adsManager) {
        adsManager.destroy();
      }
      player.trigger('adserror');
    };

    /**
     * Listener for errors thrown by the AdsManager.
     * @param {google.ima.AdErrorEvent} adErrorEvent The error event thrown by
     *     the AdsManager.
     * @private
     */
    player.ima.onAdError_ = function(adErrorEvent) {
      window.console.log('Ad error: ' + adErrorEvent.getError());
      vjsControls.show();
      adsManager.destroy();
      adContainerDiv.style.display = 'none';
      player.trigger('adserror');
    };

    /**
     * Listener for AD_BREAK_READY. Passes event on to publisher's listener.
     * @param {google.ima.AdEvent} adEvent AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onAdBreakReady_ = function(adEvent) {
      adBreakReadyListener(adEvent);
    };

    /**
     * Called by publishers in manual ad break playback mode to start an ad
     * break.
     */
    player.ima.playAdBreak = function() {
      if (!autoPlayAdBreaks) {
        adsManager.start();
      }
    }

    /**
     * Pauses the content video and displays the ad container so ads can play.
     * @param {google.ima.AdEvent} adEvent The AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onContentPauseRequested_ = function(adEvent) {
      adsActive = true;
      adPlaying = true;
      player.off('ended', localContentEndedListener);
      if (adEvent.getAd().getAdPodInfo().getPodIndex() != -1) {
        // Skip this call for post-roll ads
        player.ads.startLinearAdMode();
      }
      adContainerDiv.style.display = 'block';
      controlsDiv.style.display = 'block';
      vjsControls.hide();
      player.pause();
    };

    /**
     * Resumes content video and hides the ad container.
     * @param {google.ima.AdEvent} adEvent The AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onContentResumeRequested_ = function(adEvent) {
      adsActive = false;
      adPlaying = false;
      player.on('ended', localContentEndedListener);
      if (currentAd && currentAd.isLinear()) {
        adContainerDiv.style.display = 'none';
      }
      vjsControls.show();
      if (!currentAd) {
        // Something went wrong playing the ad
        player.ads.endLinearAdMode();
      } else if (!contentComplete &&
          // Don't exit linear mode after post-roll or content will auto-replay
          currentAd.getAdPodInfo().getPodIndex() != -1 ) {
        player.ads.endLinearAdMode();
      }
      countdownDiv.innerHTML = '';
    };

    /**
     * Records that ads have completed and calls contentAndAdsEndedListeners
     * if content is also complete.
     * @param {google.ima.AdEvent} adEvent The AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onAllAdsCompleted_ = function(adEvent) {
      allAdsCompleted = true;
      if (contentComplete == true) {
        for (var index in contentAndAdsEndedListeners) {
          contentAndAdsEndedListeners[index]();
        }
      }
    }

    /**
     * Starts the content video when a non-linear ad is loaded.
     * @param {google.ima.AdEvent} adEvent The AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onAdLoaded_ = function(adEvent) {
      if (!adEvent.getAd().isLinear()) {
        player.play();
      }
    };

    /**
     * Starts the interval timer to check the current ad time when an ad starts
     * playing.
     * @param {google.ima.AdEvent} adEvent The AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onAdStarted_ = function(adEvent) {
      currentAd = adEvent.getAd();
      if (currentAd.isLinear()) {
        adTrackingTimer = setInterval(
            player.ima.onAdPlayheadTrackerInterval_, 250);
        // Don't bump container when controls are shown
        adContainerDiv.className = '';
      } else {
        // Bump container when controls are shown
        adContainerDiv.className = 'bumpable-ima-ad-container';
      }
    };

    /**
     * Clears the interval timer for current ad time when an ad completes.
     * @param {google.ima.AdEvent} adEvent The AdEvent thrown by the AdsManager.
     * @private
     */
    player.ima.onAdComplete_ = function(adEvent) {
      if (currentAd.isLinear()) {
        clearInterval(adTrackingTimer);
      }
    };

    /**
     * Gets the current time and duration of the ad and calls the method to
     * update the ad UI.
     * @private
     */
    player.ima.onAdPlayheadTrackerInterval_ = function() {
      var remainingTime = adsManager.getRemainingTime();
      var duration =  currentAd.getDuration();
      var currentTime = duration - remainingTime;
      currentTime = currentTime > 0 ? currentTime : 0;
      var isPod = false;
      var adPosition, totalAds;
      if (currentAd.getAdPodInfo()) {
        isPod = true;
        adPosition = currentAd.getAdPodInfo().getAdPosition();
        totalAds = currentAd.getAdPodInfo().getTotalAds();
      }

      // Update countdown timer data
      var remainingMinutes = Math.floor(remainingTime / 60);
      var remainingSeconds = Math.floor(remainingTime % 60);
      if (remainingSeconds.toString().length < 2) {
        remainingSeconds = '0' + remainingSeconds;
      }
      var podCount = ': ';
      if (isPod) {
        podCount = ' (' + adPosition + ' of ' + totalAds + '): ';
      }
      countdownDiv.innerHTML =
          settings.adLabel + podCount +
          remainingMinutes + ':' + remainingSeconds;

      // Update UI
      var playProgressRatio = currentTime / duration;
      var playProgressPercent = playProgressRatio * 100;
      progressDiv.style.width = playProgressPercent + '%';
    };

    player.ima.getPlayerWidth = function() {
      var retVal = parseInt(getComputedStyle(player.el()).width, 10) ||
          player.width();
      return retVal;
    };

    player.ima.getPlayerHeight = function() {
      var retVal = parseInt(getComputedStyle(player.el()).height, 10) ||
          player.height();
      return retVal;
    }

    /**
     * Hides the ad controls on mouseout.
     * @private
     */
    player.ima.hideAdControls_ = function() {
      playPauseDiv.style.display = 'none';
      muteDiv.style.display = 'none';
      fullscreenDiv.style.display = 'none';
      controlsDiv.style.height = '14px';
    };

    /**
     * Shows ad controls on mouseover.
     * @private
     */
    player.ima.showAdControls_ = function() {
      controlsDiv.style.height = '37px';
      playPauseDiv.style.display = 'block';
      muteDiv.style.display = 'block';
      sliderDiv.style.display = 'block';
      fullscreenDiv.style.display = 'block';
    };

    /**
     * Listener for clicks on the play/pause button during ad playback.
     * @private
     */
    player.ima.onAdPlayPauseClick_ = function() {
      if (adPlaying) {
        playPauseDiv.className = 'ima-paused';
        adsManager.pause();
        adPlaying = false;
      } else {
        playPauseDiv.className = 'ima-playing';
        adsManager.resume();
        adPlaying = true;
      }
    };

    /**
     * Listener for clicks on the mute button during ad playback.
     * @private
     */
    player.ima.onAdMuteClick_ = function() {
      if (adMuted) {
        muteDiv.className = 'ima-non-muted';
        adsManager.setVolume(1);
        // Bubble down to content player
        player.muted(false);
        adMuted = false;
        sliderLevelDiv.style.width = player.volume() * 100 + "%";
      } else {
        muteDiv.className = 'ima-muted';
        adsManager.setVolume(0);
        // Bubble down to content player
        player.muted(true);
        adMuted = true;
        sliderLevelDiv.style.width = "0%";
      }
    };

    /* Listener for mouse down events during ad playback. Used for volume.
     * @private
     */
    player.ima.onAdVolumeSliderMouseDown_ = function() {
       document.addEventListener('mouseup', player.ima.onMouseUp_, false);
       document.addEventListener('mousemove', player.ima.onMouseMove_, false);
    }

    /* Mouse movement listener used for volume slider.
     * @private
     */
    player.ima.onMouseMove_ = function(event) {
      player.ima.setVolumeSlider_(event);
    }

    /* Mouse release listener used for volume slider.
     * @private
     */
    player.ima.onMouseUp_ = function(event) {
      player.ima.setVolumeSlider_(event);
      document.removeEventListener('mousemove', player.ima.onMouseMove_);
      document.removeEventListener('mouseup', player.ima.onMouseUp_);
    }

    /* Utility function to set volume and associated UI
     * @private
     */
    player.ima.setVolumeSlider_ = function(event) {
      var percent =
          (event.clientX - sliderDiv.getBoundingClientRect().left) /
              sliderDiv.offsetWidth;
      percent *= 100;
      //Bounds value 0-100 if mouse is outside slider region.
      percent = Math.min(Math.max(percent, 0), 100);
      sliderLevelDiv.style.width = percent + "%";
      player.volume(percent / 100); //0-1
      adsManager.setVolume(percent / 100);
      if (player.volume() == 0) {
        muteDiv.className = 'ima-muted';
        player.muted(true);
        adMuted = true;
      }
      else
      {
        muteDiv.className = 'ima-non-muted';
        player.muted(false);
        adMuted = false;
      }
    }

    /**
     * Listener for clicks on the fullscreen button during ad playback.
     * @private
     */
    player.ima.onAdFullscreenClick_ = function() {
      if (player.isFullscreen()) {
        player.exitFullscreen();
      } else {
        player.requestFullscreen();
      }
    };

    /**
     * Listens for the video.js player to change its fullscreen status. This
     * keeps the fullscreen-ness of the AdContainer in sync with the player.
     * @private
     */
    player.ima.onFullscreenChange_ = function() {
      if (player.isFullscreen()) {
        fullscreenDiv.className = 'ima-fullscreen';
        if (adsManager) {
          adsManager.resize(
              window.screen.width,
              window.screen.height,
              google.ima.ViewMode.FULLSCREEN);
        }
      } else {
        fullscreenDiv.className = 'ima-non-fullscreen';
        if (adsManager) {
          adsManager.resize(
              player.ima.getPlayerWidth(),
              player.ima.getPlayerHeight(),
              google.ima.ViewMode.NORMAL);
        }
      }
    };

    /**
     * Listens for the video.js player to change its volume. This keeps the ad
     * volume in sync with the content volume if the volume of the player is
     * changed while content is playing
     * @private
     */
    player.ima.onVolumeChange_ = function() {
      var newVolume = player.muted() ? 0 : player.volume();
      if (adsManager) {
        adsManager.setVolume(newVolume);
      }
      // Update UI
      if (newVolume == 0) {
        adMuted = true;
        muteDiv.className = 'ima-muted';
        sliderLevelDiv.style.width = '0%';
      } else {
        adMuted = false;
        muteDiv.className = 'ima-non-muted';
        sliderLevelDiv.style.width = newVolume * 100 + '%';
      }
    };

    /**
     * Seeks content to 00:00:00. This is used as an event handler for the
     * loadedmetadata event, since seeking is not possible until that event has
     * fired.
     * @private
     */
    player.ima.seekContentToZero_ = function() {
      player.off('loadedmetadata', player.ima.seekContentToZero_);
      player.currentTime(0);
    };

    /**
     * Seeks content to 00:00:00 and starts playback. This is used as an event
     * handler for the loadedmetadata event, since seeking is not possible until
     * that event has fired.
     * @private
     */
    player.ima.playContentFromZero_ = function() {
      player.off('loadedmetadata', player.ima.playContentFromZero_);
      player.currentTime(0);
      player.play();
    };

    /**
     * Destroys the AdsManager, sets it to null, and calls contentComplete to
     * reset correlators. Once this is done it requests ads again to keep the
     * inventory available.
     * @private
     */
    player.ima.resetIMA_ = function() {
      adsActive = false;
      adPlaying = false;
      player.on('ended', localContentEndedListener);
      if (currentAd && currentAd.isLinear()) {
        adContainerDiv.style.display = 'none';
      }
      vjsControls.show();
      player.ads.endLinearAdMode();
      if (adTrackingTimer) {
        // If this is called while an ad is playing, stop trying to get that
        // ad's current time.
        clearInterval(adTrackingTimer);
      }
      if (adsManager) {
        adsManager.destroy();
        adsManager = null;
      }
      if (adsLoader && !contentComplete) {
        adsLoader.contentComplete();
      }
      contentComplete = false;
      allAdsCompleted = false;
    };

    /**
     * Ads an EventListener to the AdsManager. For a list of available events,
     * see
     * https://developers.google.com/interactive-media-ads/docs/sdks/html5/v3/apis#ima.AdEvent.Type
     * @param {google.ima.AdEvent.Type} event The AdEvent.Type for which to listen.
     * @param {function} callback The method to call when the event is fired.
     */
    player.ima.addEventListener = function(event, callback) {
      if (adsManager) {
        adsManager.addEventListener(event, callback);
      }
    };

    /**
     * Returns the instance of the AdsManager.
     * @return {google.ima.AdsManager} The AdsManager being used by the plugin.
     */
    player.ima.getAdsManager = function() {
      return adsManager;
    };

    /**
     * Sets the content of the video player. You should use this method instead
     * of setting the content src directly to ensure the proper ad tag is
     * requested when the video content is loaded.
     * @param {?string} contentSrc The URI for the content to be played. Leave
     *     blank to use the existing content.
     * @param {?string} adTag The ad tag to be requested when the content loads.
     *     Leave blank to use the existing ad tag.
     * @param {?boolean} playOnLoad True to play the content once it has loaded,
     *     false to only load the content but not start playback.
     */
    player.ima.setContent =
        function(contentSrc, adTag, playOnLoad) {
      player.ima.resetIMA_();
      settings.adTagUrl = adTag ? adTag : settings.adTagUrl;
      //only try to pause the player when initialised with a source already
      if (!!player.currentSrc()) {
        player.currentTime(0);
        player.pause();
      }
      if (contentSrc) {
        player.src(contentSrc);
      }
      if (playOnLoad) {
        player.on('loadedmetadata', player.ima.playContentFromZero_);
      } else {
        player.on('loadedmetadata', player.ima.seekContentToZero_);
      }
    };

    /**
     * Adds a listener for the 'ended' event of the video player. This should be
     * used instead of setting an 'ended' listener directly to ensure that the
     * ima can do proper cleanup of the SDK before other event listeners
     * are called.
     * @param {function} listener The listener to be called when content completes.
     */
    player.ima.addContentEndedListener = function(listener) {
      contentEndedListeners.push(listener);
    };

    /**
     * Adds a listener that will be called when content and all ads have
     * finished playing.
     * @param {function} listener The listener to be called when content and ads complete.
     */
    player.ima.addContentAndAdsEndedListener = function(listener) {
      contentAndAdsEndedListeners.push(listener);
    }

    /**
     * Sets the listener to be called to trigger manual ad break playback.
     * @param {function} listener The listener to be called to trigger manual ad break playback.
     */
    player.ima.setAdBreakReadyListener = function(listener) {
      adBreakReadyListener = listener;
    }

    /**
     * Pauses the ad.
     */
    player.ima.pauseAd = function() {
      if (adsActive && adPlaying) {
        playPauseDiv.className = 'ima-paused';
        adsManager.pause();
        adPlaying = false;
      }
    };

    /**
     * Resumes the ad.
     */
    player.ima.resumeAd = function() {
      if (adsActive && !adPlaying) {
        playPauseDiv.className = 'ima-playing';
        adsManager.resume();
        adPlaying = true;
      }
    };

    /**
     * Set up intervals to check for seeking and update current video time.
     * @private
     */
    player.ima.setUpPlayerIntervals_ = function() {
      updateTimeIntervalHandle =
          setInterval(player.ima.updateCurrentTime_, seekCheckInterval);
      seekCheckIntervalHandle =
          setInterval(player.ima.checkForSeeking_, seekCheckInterval);
      resizeCheckIntervalHandle =
          setInterval(player.ima.checkForResize_, resizeCheckInterval);
    };

    /**
     * Updates the current time of the video
     * @private
     */
    player.ima.updateCurrentTime_ = function() {
      if (!contentPlayheadTracker.seeking) {
        contentPlayheadTracker.currentTime = player.currentTime();
      }
    };

    /**
     * Detects when the user is seeking through a video.
     * This is used to prevent mid-rolls from playing while a user is seeking.
     *
     * There *is* a seeking property of the HTML5 video element, but it's not
     * properly implemented on all platforms (e.g. mobile safari), so we have to
     * check ourselves to be sure.
     *
     * @private
     */
    player.ima.checkForSeeking_ = function() {
      var tempCurrentTime = player.currentTime();
      var diff = (tempCurrentTime - contentPlayheadTracker.previousTime) * 1000;
      if (Math.abs(diff) > seekCheckInterval + seekThreshold) {
        contentPlayheadTracker.seeking = true;
      } else {
        contentPlayheadTracker.seeking = false;
      }
      contentPlayheadTracker.previousTime = player.currentTime();
    };

    /**
     * Detects when the player is resized (for fluid support) and resizes the
     * ads manager to match.
     *
     * @private
     */
    player.ima.checkForResize_ = function() {
      var currentWidth = player.ima.getPlayerWidth();
      var currentHeight = player.ima.getPlayerHeight();

      if (adsManager && (currentWidth != adsManagerDimensions.width ||
          currentHeight != adsManagerDimensions.height)) {
        adsManagerDimensions.width = currentWidth;
        adsManagerDimensions.height = currentHeight;
        adsManager.resize(currentWidth, currentHeight, google.ima.ViewMode.NORMAL);
      }
    }

    /**
     * Changes the flag to show or hide the ad countdown timer.
     *
     * @param {boolean} showCountdownIn Show or hide the countdown timer.
     */
    player.ima.setShowCountdown = function(showCountdownIn) {
      showCountdown = showCountdownIn;
      countdownDiv.style.display = showCountdown ? 'block' : 'none';
    };

    /**
     * Current plugin version.
     */
    var VERSION = '0.2.0';

    /**
     * Stores user-provided settings.
     */
    var settings;

    /**
     * Video element playing content.
     */
    var contentPlayer;

    /**
     * Boolean flag to show or hide the ad countdown timer.
     */
    var showCountdown;

    /**
     * Boolena flag to enable manual ad break playback.
     */
    var autoPlayAdBreaks;

    /**
     * Video.js control bar.
     */
    var vjsControls;

    /**
     * Div used as an ad container.
     */
    var adContainerDiv;

    /**
     * Div used to display ad controls.
     */
    var controlsDiv;

    /**
     * Div used to display ad countdown timer.
     */
    var countdownDiv;

    /**
     * Div used to display add seek bar.
     */
    var seekBarDiv;

    /**
     * Div used to display ad progress (in seek bar).
     */
    var progressDiv;

    /**
     * Div used to display ad play/pause button.
     */
    var playPauseDiv;

    /**
     * Div used to display ad mute button.
     */
    var muteDiv;

    /**
     * Div used by the volume slider.
     */
    var sliderDiv;

    /**
     * Volume slider level visuals
     */
    var sliderLevelDiv;

    /**
     * Div used to display ad fullscreen button.
     */
    var fullscreenDiv;

    /**
     * IMA SDK AdDisplayContainer.
     */
    var adDisplayContainer;

    /**
     * True if the AdDisplayContainer has been initialized. False otherwise.
     */
    var adDisplayContainerInitialized = false;

    /**
     * IMA SDK AdsLoader
     */
    var adsLoader;

    /**
     * IMA SDK AdsManager
     */
    var adsManager;

    /**
     * IMA SDK AdsRenderingSettings.
     */
    var adsRenderingSettings = null;

    /**
     * Ad tag URL. Should return VAST, VMAP, or ad rules.
     */
    var adTagUrl;

    /**
     * Current IMA SDK Ad.
     */
    var currentAd;

    /**
     * Timer used to track content progress.
     */
    var contentTrackingTimer;

    /**
     * Timer used to track ad progress.
     */
    var adTrackingTimer;

    /**
     * True if ads are currently displayed, false otherwise.
     * True regardless of ad pause state if an ad is currently being displayed.
     */
    var adsActive = false;

    /**
     * True if ad is currently playing, false if ad is paused or ads are not
     * currently displayed.
     */
    var adPlaying = false;

    /**
     * True if the ad is muted, false otherwise.
     */
    var adMuted = false;

    /**
     * True if our content video has completed, false otherwise.
     */
    var contentComplete = false;

    /**
     * True if ALL_ADS_COMPLETED has fired, false until then.
     */
     var allAdsCompleted = false;

    /**
     * Handle to interval that repeatedly updates current time.
     */
    var updateTimeIntervalHandle;

    /**
     * Handle to interval that repeatedly checks for seeking.
     */
    var seekCheckIntervalHandle;

    /**
     * Interval (ms) on which to check if the user is seeking through the
     * content.
     */
    var seekCheckInterval = 1000;

    /**
     * Handle to interval that repeatedly checks for player resize.
     */
    var resizeCheckIntervalHandle;

    /**
     * Interval (ms) to check for player resize for fluid support.
     */
    var resizeCheckInterval = 250;

    /**
     * Threshold by which to judge user seeking. We check every 1000 ms to see
     * if the user is seeking. In order for us to decide that they are *not*
     * seeking, the content video playhead must only change by 900-1100 ms
     * between checks. Any greater change and we assume the user is seeking
     * through the video.
     */
    var seekThreshold = 100;

    /**
     * Stores data for the content playhead tracker.
     */
    var contentPlayheadTracker = {
      currentTime: 0,
      previousTime: 0,
      seeking: false,
      duration: 0
    };

    /**
     * Stores data for the ad playhead tracker.
     */
    var adPlayheadTracker = {
      currentTime: 0,
      duration: 0,
      isPod: false,
      adPosition: 0,
      totalAds: 0
    };

    /**
     * Stores the dimensions for the ads manager.
     */
    var adsManagerDimensions = {
      width: 0,
      height: 0
    };

    /**
     * Content ended listeners passed by the publisher to the plugin. Publishers
     * should allow the plugin to handle content ended to ensure proper support
     * of custom ad playback.
     */
    var contentEndedListeners = [];

    /**
     * Content and ads ended listeners passed by the publisher to the plugin.
     * These will be called when the plugin detects that content *and all
     * ads* have completed. This differs from the contentEndedListeners in that
     * contentEndedListeners will fire between content ending and a post-roll
     * playing, whereas the contentAndAdsEndedListeners will fire after the
     * post-roll completes.
     */
    var contentAndAdsEndedListeners = [];

     /**
      * Listener to be called to trigger manual ad break playback.
      */
    var adBreakReadyListener = undefined;

    /**
     * Local content ended listener for contentComplete.
     */
    var localContentEndedListener = function() {
      if (adsLoader && !contentComplete) {
        adsLoader.contentComplete();
        contentComplete = true;
      }
      for (var index in contentEndedListeners) {
        contentEndedListeners[index]();
      }
      if (allAdsCompleted) {
        for (var index in contentAndAdsEndedListeners) {
          contentAndAdsEndedListeners[index]();
        }
      }
      clearInterval(updateTimeIntervalHandle);
      clearInterval(seekCheckIntervalHandle);
      clearInterval(resizeCheckIntervalHandle);
      player.one('play', player.ima.setUpPlayerIntervals_);
    };

    settings = extend({}, ima_defaults, options || {});

    // Currently this isn't used but I can see it being needed in the future, so
    // to avoid implementation problems with later updates I'm requiring it.
    if (!settings['id']) {
      window.console.log('Error: must provide id of video.js div');
      return;
    }
    contentPlayer = document.getElementById(settings['id'] + '_html5_api');
    // Default showing countdown timer to true.
    showCountdown = true;
    if (settings['showCountdown'] == false) {
      showCountdown = false;
    }

    autoPlayAdBreaks = true;
    if (settings['autoPlayAdBreaks'] == false) {
      autoPlayAdBreaks = false;
    }

    player.one('play', player.ima.setUpPlayerIntervals_);

    player.on('ended', localContentEndedListener);

    var contrib_ads_defaults = {
      debug: settings.debug,
      timeout: settings.timeout,
      prerollTimeout: settings.prerollTimeout
    };

    var ads_plugin_settings =
        extend({}, contrib_ads_defaults, options['contribAdsSettings'] || {});

    player.ads(ads_plugin_settings);

    adsRenderingSettings = new google.ima.AdsRenderingSettings();
    adsRenderingSettings.restoreCustomPlaybackStateOnAdBreakComplete = true;
    if (settings['adsRenderingSettings']) {
      for (var setting in settings['adsRenderingSettings']) {
        adsRenderingSettings[setting] =
            settings['adsRenderingSettings'][setting];
      }
    }

    if (settings['locale']) {
      google.ima.settings.setLocale(settings['locale']);
    }

    player.ima.createAdContainer_();

    adsLoader = new google.ima.AdsLoader(adDisplayContainer);

    adsLoader.getSettings().setVpaidMode(
        google.ima.ImaSdkSettings.VpaidMode.ENABLED);
    if (settings.vpaidAllowed == false) {
      adsLoader.getSettings().setVpaidMode(
          google.ima.ImaSdkSettings.VpaidMode.DISABLED);
    }
    if (settings.vpaidMode) {
      adsLoader.getSettings().setVpaidMode(settings.vpaidMode);
    }

    if (settings.locale) {
      adsLoader.getSettings().setLocale(settings.locale);
    }

    if (settings.numRedirects) {
      adsLoader.getSettings().setNumRedirects(settings.numRedirects);
    }

    adsLoader.getSettings().setPlayerType('videojs-ima');
    adsLoader.getSettings().setPlayerVersion(VERSION);
    adsLoader.getSettings().setAutoPlayAdBreaks(autoPlayAdBreaks);

    adsLoader.addEventListener(
      google.ima.AdsManagerLoadedEvent.Type.ADS_MANAGER_LOADED,
      player.ima.onAdsManagerLoaded_,
      false);
    adsLoader.addEventListener(
      google.ima.AdErrorEvent.Type.AD_ERROR,
      player.ima.onAdsLoaderError_,
      false);

    if (!readyCallback) {
      readyCallback = player.ima.start;
    }
    player.on('readyforpreroll', readyCallback);
    player.ready(function() {
      player.on('fullscreenchange', player.ima.onFullscreenChange_);
      player.on('volumechange', player.ima.onVolumeChange_);
    });
  };

  videojs.plugin('ima', imaPlugin);
}(window.videojs));


/**
 * cb_ultimate.ads.js plugin for ads
 */

var Ads = function(player,settings){

	var ads = this;
	//console.log(ads);
	ads.ad_id = settings.ad_id;
	ads.ad_code = settings.ad_code;
	ads.autoplay = settings.autoplay;
	ads.player = player;
	ads.init();
}

Ads.prototype.init = function (){
	var ads = this;
	
	var startEvent = 'click';
	if (navigator.userAgent.match(/iPhone/i) ||  navigator.userAgent.match(/iPad/i) ||navigator.userAgent.match(/Android/i)) 
	{
	    console.log("iphone/Android");
	    //startEvent = 'tap';
	}

	if (!ads.autoplay){
		ads.player.one(startEvent, ads.bind(ads, ads.initialize));
	}

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
  	
  	ads.player.ima(ads.options,this.bind(this, this.adsManagerLoadedCallback));
  	if (ads.autoplay){
		ads.initialize();
	}
}

Ads.prototype.initialize = function(){
	var ads = this;
	ads.player.ima.initializeAdDisplayContainer();
    ads.player.ima.setContent(null, ads.ad_code, true);
    ads.player.ima.requestAds();
    ads.player.play();
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

Ads.prototype.update_impressions = function(ad_id){
	update_ad_imp(ad_id);
}

Ads.prototype.bind = function(thisObj, fn) {
  	return function() {
    fn.apply(thisObj, arguments);
};
}

var cb_ultimate_ads = function(settings){
	
    var ultimate_ads =  new Ads(this,settings);
}
videojs.plugin('cb_ultimate_ads',cb_ultimate_ads);

/**
 * Videojs.relatedCarousal.js plugin
*/
 /*
 *  Copyright (c) 2013 Funny or Die, Inc.
 *  http://www.funnyordie.com
 *  https://github.com/funnyordie/videojs-relatedCarousel/blob/master/LICENSE.md
 */

(function(vjs) {
  var extend = function(obj) {
      var arg, i, k;
      for (i = 1; i < arguments.length; i++) {
        arg = arguments[i];
        for (k in arg) {
          if (arg.hasOwnProperty(k)) {
            obj[k] = arg[k];
          }
        }
      }
      return obj;
    },
    defaults = [
      {
        imageSrc: '',
        title: '',
        url: ''
      }
    ];

  vjs.plugin('relatedCarousel', function(options) {
    var player = this,
      settings = extend([], defaults, options || []),
      carousel = function() {
        this.controlBarButton = document.createElement('div');

        this.holderDiv = document.createElement('div');
        this.title = document.createElement('h5');

        this.viewport = document.createElement('div');
        this.items = document.createElement('ul');

        this.leftButton = document.createElement('div');
        this.leftButtonContent = document.createElement('div');

        this.rightButton = document.createElement('div');
        this.rightButtonContent = document.createElement('div');

        this.config = null;
        this.currentPosition = 0;
        this.maxPosition = 0;
        this.currentVideoIndex = -1;
        this.isOpen = false;
        this.callbacksEnabled = true;
      };

    carousel.prototype.open = function() {
      if (!this.isOpen) {
        if (!this.holderDiv.className.match(/(?:^|\s)active(?!\S)/g)) {
          this.holderDiv.className = this.holderDiv.className + " active";
        }
      }
      this.isOpen = true;
    };

    carousel.prototype.close = function() {
      if (this.isOpen) {
        if (this.holderDiv.className.match(/(?:^|\s)active(?!\S)/g)) {
          this.holderDiv.className = this.holderDiv.className.replace(/(?:^|\s)active(?!\S)/g, '')
        }
      }
      this.isOpen = false;
    };

    carousel.prototype.toggle = function() {
      if (this.isOpen) {
        this.close();
      } else {
        this.open();
      }
    };

    carousel.prototype.initiateVideo = function(index, config, trigger) {
      if (config.callback !== undefined) {
        if (this.callbacksEnabled) {
          this.currentVideoIndex = index;
          config.callback(player, config, {
            trigger: trigger,
            newIndex: this.currentVideoIndex
          });
        }
      } else {
        this.currentVideoIndex = index;
        this.close();
        if (config.src !== undefined) {
          player.src(config.src);
          player.play();
        } else {
          window.location = config.url;
        }
      }
    };

    carousel.prototype.onItemClick = function(index, element, config) {
      var self = this;
      element.onclick = function(e) {
        e.preventDefault();
        self.initiateVideo(index, config, e);
      };
    };

    carousel.prototype.buildCarousel = function(config) {
      this.config = config;
      this.items.innerHTML = '';
      this.maxPosition = (-110) * (this.config.length - 1)

      // Initialize carousel items
      for (var i = 0; i < this.config.length; i++) {
        var item = document.createElement('li');
        item.className = 'carousel-item';

        var img = document.createElement('img');
        img.src = this.config[i].imageSrc;
        img.className = 'vjs-carousel-thumbnail';
        img.alt = this.config[i].title;
        img.style.width = '100%';

        var anchor = document.createElement('a');

        if (!this.config[i].url) {
          this.config[i].url = '#';
        }

        anchor.href = this.config[i].url;
        anchor.title = this.config[i].title;
        anchor.appendChild(img);

        this.onItemClick(i, anchor, this.config[i]);

        var title = document.createElement('div');
        title.className = 'carousel-item-title';
        title.innerHTML = this.config[i].title;
        anchor.appendChild(title);

        item.appendChild(anchor);
        this.items.appendChild(item);
      }

      this.currentVideoIndex = -1;
      this.currentPosition = 0;
      this.items.style.left = this.currentPosition + 'px';
    };

    player.carousel = new carousel();

    /* Menu Button */
    player.carousel.controlBarButton.className = 'vjs-button vjs-control vjs-related-carousel-button icon-videojs-carousel-toggle icon-related';
    player.carousel.controlBarButton.title = "More Videos";
     
    player.carousel.holderDiv.className = 'vjs-related-carousel-holder';
    player.carousel.title.innerHTML = 'More Videos';
    player.carousel.viewport.className = 'vjs-carousel-viewport';
    player.carousel.items.className = 'carousel-items';
    player.carousel.leftButton.className = 'vjs-carousel-left-button';
    player.carousel.leftButtonContent.className = 'icon-videojs-carousel-left icon-prev';
    player.carousel.rightButton.className = 'vjs-carousel-right-button';
    player.carousel.rightButtonContent.className = 'icon-videojs-carousel-right icon-next';

    // Add all items to DOM
    var controlBarChilds =  player.controlBar.el().childNodes;
    for (var i = 0; i < controlBarChilds.length; i++) {
        if (controlBarChilds[i].id == 'vjs-cb-logo'){
            cbVjsLogo = controlBarChilds[i];
        }
    }
    player.controlBar.el().insertBefore(player.carousel.controlBarButton,cbVjsLogo);
    player.carousel.holderDiv.appendChild(player.carousel.title);
    player.el().appendChild(player.carousel.holderDiv);
    player.carousel.holderDiv.appendChild(player.carousel.viewport);
    player.carousel.viewport.appendChild(player.carousel.items);
    player.carousel.leftButton.appendChild(player.carousel.leftButtonContent);
    player.carousel.holderDiv.appendChild(player.carousel.leftButton);
    player.carousel.rightButton.appendChild(player.carousel.rightButtonContent);
    player.carousel.holderDiv.appendChild(player.carousel.rightButton);

    // Add event handlers
    player.carousel.controlBarButton.onclick = function(e) {
      player.carousel.toggle();
    };
    player.carousel.leftButton.onclick = function() {
      if (player.carousel.currentPosition === 0) {
        return;
      }
      player.carousel.currentPosition = player.carousel.currentPosition + 110;
      player.carousel.items.style.left = player.carousel.currentPosition + 'px';
    };

    player.carousel.rightButton.onclick = function() {
      if (player.carousel.currentPosition <= player.carousel.maxPosition) {
        return;
      }
      player.carousel.currentPosition = player.carousel.currentPosition - 110;
      player.carousel.items.style.left = player.carousel.currentPosition + 'px';
    };

    player.carousel.buildCarousel(settings);

    // Player events
    player.on('mouseout', function() {
      if (!player.carousel.holderDiv.className.match(/(?:^|\s)vjs-fade-out(?!\S)/g)) {
        player.carousel.holderDiv.className = player.carousel.holderDiv.className + " vjs-fade-out";
      }
    });
    player.on('mouseover', function() {
      player.carousel.holderDiv.className = player.carousel.holderDiv.className.replace(/(?:^|\s)vjs-fade-out(?!\S)/g, '');
    });
    player.on('timeupdate', function() {
      /*if (player.ended()) {
        if (player.carousel.currentVideoIndex === player.carousel.config.length) {
          return;
        }

        player.carousel.initiateVideo(player.carousel.currentVideoIndex + 1, player.carousel.config[player.carousel.currentVideoIndex + 1], player);
      }*/
    });
  });
}(videojs));


/**
 * iphone-inline-video.browser.js plugin
*/

/*! npm.im/iphone-inline-video */
var makeVideoPlayableInline=function(){"use strict";/*! npm.im/intervalometer */
function e(e,r,n,i){function t(n){d=r(t,i),e(n-(a||n)),a=n}var d,a;return{start:function(){d||t(0)},stop:function(){n(d),d=null,a=0}}}function r(r){return e(r,requestAnimationFrame,cancelAnimationFrame)}function n(e,r,n,i){function t(r){Boolean(e[n])===Boolean(i)&&r.stopImmediatePropagation(),delete e[n]}return e.addEventListener(r,t,!1),t}function i(e,r,n,i){function t(){return n[r]}function d(e){n[r]=e}i&&d(e[r]),Object.defineProperty(e,r,{get:t,set:d})}function t(e,r,n){n.addEventListener(r,function(){return e.dispatchEvent(new Event(r))})}function d(e,r){Promise.resolve().then(function(){e.dispatchEvent(new Event(r))})}function a(e){var r=new Audio;return t(e,"play",r),t(e,"playing",r),t(e,"pause",r),r.crossOrigin=e.crossOrigin,r.src=e.src||e.currentSrc||"data:",r}function o(e,r,n){(m||0)+200<Date.now()&&(e[b]=!0,m=Date.now()),n||(e.currentTime=r),A[++k%3]=100*r|0}function u(e){return e.driver.currentTime>=e.video.duration}function s(e){var r=this;r.video.readyState>=r.video.HAVE_FUTURE_DATA?(r.hasAudio||(r.driver.currentTime=r.video.currentTime+e*r.video.playbackRate/1e3,r.video.loop&&u(r)&&(r.driver.currentTime=0)),o(r.video,r.driver.currentTime)):r.video.networkState!==r.video.NETWORK_IDLE||r.video.buffered.length||r.video.load(),r.video.ended&&(delete r.video[b],r.video.pause(!0))}function c(){var e=this,r=e[h];return e.webkitDisplayingFullscreen?void e[E]():("data:"!==r.driver.src&&r.driver.src!==e.src&&(o(e,0,!0),r.driver.src=e.src),void(e.paused&&(r.paused=!1,e.buffered.length||e.load(),r.driver.play(),r.updater.start(),r.hasAudio||(d(e,"play"),r.video.readyState>=r.video.HAVE_ENOUGH_DATA&&d(e,"playing")))))}function v(e){var r=this,n=r[h];n.driver.pause(),n.updater.stop(),r.webkitDisplayingFullscreen&&r[T](),n.paused&&!e||(n.paused=!0,n.hasAudio||d(r,"pause"),r.ended&&(r[b]=!0,d(r,"ended")))}function p(e,n){var i=e[h]={};i.paused=!0,i.hasAudio=n,i.video=e,i.updater=r(s.bind(i)),n?i.driver=a(e):(e.addEventListener("canplay",function(){e.paused||d(e,"playing")}),i.driver={src:e.src||e.currentSrc||"data:",muted:!0,paused:!0,pause:function(){i.driver.paused=!0},play:function(){i.driver.paused=!1,u(i)&&o(e,0)},get ended(){return u(i)}}),e.addEventListener("emptied",function(){var r=!i.driver.src||"data:"===i.driver.src;i.driver.src&&i.driver.src!==e.src&&(o(e,0,!0),i.driver.src=e.src,r?i.driver.play():i.updater.stop())},!1),e.addEventListener("webkitbeginfullscreen",function(){e.paused?n&&!i.driver.buffered.length&&i.driver.load():(e.pause(),e[E]())}),n&&(e.addEventListener("webkitendfullscreen",function(){i.driver.currentTime=e.currentTime}),e.addEventListener("seeking",function(){A.indexOf(100*e.currentTime|0)<0&&(i.driver.currentTime=e.currentTime)}))}function l(e){var r=e[h];e[E]=e.play,e[T]=e.pause,e.play=c,e.pause=v,i(e,"paused",r.driver),i(e,"muted",r.driver,!0),i(e,"playbackRate",r.driver,!0),i(e,"ended",r.driver),i(e,"loop",r.driver,!0),n(e,"seeking"),n(e,"seeked"),n(e,"timeupdate",b,!1),n(e,"ended",b,!1)}function f(e,r,n){void 0===r&&(r=!0),void 0===n&&(n=!0),n&&!g||e[h]||(p(e,r),l(e),e.classList.add("IIV"),!r&&e.autoplay&&e.play(),/iPhone|iPod|iPad/.test(navigator.platform)||console.warn("iphone-inline-video is not guaranteed to work in emulated environments"))}var m,y="undefined"==typeof Symbol?function(e){return"@"+(e||"@")+Math.random()}:Symbol,g=/iPhone|iPod/i.test(navigator.userAgent)&&!matchMedia("(-webkit-video-playable-inline)").matches,h=y(),b=y(),E=y("nativeplay"),T=y("nativepause"),A=[],k=0;return f.isWhitelisted=g,f}();


/**
 * videojs.thumbnails.js plugin for thmbnail on seekbar
*/

 (function() {
  var defaults = {
      0: {
        src: 'example-thumbnail.png'
      }
    },
    extend = function() {
      var args, target, i, object, property;
      args = Array.prototype.slice.call(arguments);
      target = args.shift() || {};
      for (i in args) {
        object = args[i];
        for (property in object) {
          if (object.hasOwnProperty(property)) {
            if (typeof object[property] === 'object') {
              target[property] = extend(target[property], object[property]);
            } else {
              target[property] = object[property];
            }
          }
        }
      }
      return target;
    },
    getComputedStyle = function(el, pseudo) {
      return function(prop) {
        if (window.getComputedStyle) {
          return window.getComputedStyle(el, pseudo)[prop];
        } else {
          return el.currentStyle[prop];
        }
      };
    },
    offsetParent = function(el) {
      if (el.nodeName !== 'HTML' && getComputedStyle(el)('position') === 'static') {
        return offsetParent(el.offsetParent);
      }
      return el;
    },
    getVisibleWidth = function(el, width) {
      var clip;

      if (width) {
        return parseFloat(width);
      }

      clip = getComputedStyle(el)('clip');
      if (clip !== 'auto' && clip !== 'inherit') {
        clip = clip.split(/(?:\(|\))/)[1].split(/(?:,| )/);
        if (clip.length === 4) {
          return (parseFloat(clip[1]) - parseFloat(clip[3]));
        }
      }
      return 0;
    },
    getScrollOffset = function() {
      if (window.pageXOffset) {
        return {
          x: window.pageXOffset,
          y: window.pageYOffset
        };
      }
      return {
        x: document.documentElement.scrollLeft,
        y: document.documentElement.scrollTop
      };
    };

  /**
   * register the thubmnails plugin
   */
  videojs.plugin('thumbnails', function(options) {
    var div, settings, img, player, progressControl, duration, moveListener, moveCancel;
    settings = extend({}, defaults, options);
    player = this;

    (function() {
      var progressControl, addFakeActive, removeFakeActive;
      // Android doesn't support :active and :hover on non-anchor and non-button elements
      // so, we need to fake the :active selector for thumbnails to show up.
      if (navigator.userAgent.toLowerCase().indexOf("android") !== -1) {
        progressControl = player.controlBar.progressControl;

        addFakeActive = function() {
          progressControl.addClass('fake-active');
        };
        removeFakeActive = function() {
          progressControl.removeClass('fake-active');
        };

        progressControl.on('touchstart', addFakeActive);
        progressControl.on('touchend', removeFakeActive);
        progressControl.on('touchcancel', removeFakeActive);
      }
    })();

    // create the thumbnail
    div = document.createElement('div');
    div.className = 'vjs-thumbnail-holder';
    img = document.createElement('img');
    div.appendChild(img);
    img.src = settings['0'].src;
    img.className = 'vjs-thumbnail';
    extend(img.style, settings['0'].style);

    // center the thumbnail over the cursor if an offset wasn't provided
    if (!img.style.left && !img.style.right) {
      img.onload = function() {
        img.style.left = -(img.naturalWidth / 2) + 'px';
      };
    }

    // keep track of the duration to calculate correct thumbnail to display
    duration = player.duration();
    
    // when the container is MP4
    player.on('durationchange', function(event) {
      duration = player.duration();
    });

    // when the container is HLS
    player.on('loadedmetadata', function(event) {
      duration = player.duration();
    });

    // add the thumbnail to the player
    progressControl = player.controlBar.progressControl;
    progressControl.el().appendChild(div);

    moveListener = function(event) {
      var mouseTime, time, active, left, setting, pageX, right, width, halfWidth, pageXOffset, clientRect;
      active = 0;
      pageXOffset = getScrollOffset().x;
      clientRect = offsetParent(progressControl.el()).getBoundingClientRect();
      right = (clientRect.width || clientRect.right) + pageXOffset;

      pageX = event.pageX;
      if (event.changedTouches) {
        pageX = event.changedTouches[0].pageX;
      }

      // find the page offset of the mouse
      left = pageX || (event.clientX + document.body.scrollLeft + document.documentElement.scrollLeft);
      // subtract the page offset of the positioned offset parent
      left -= offsetParent(progressControl.el()).getBoundingClientRect().left + pageXOffset;

      // apply updated styles to the thumbnail if necessary
      // mouseTime is the position of the mouse along the progress control bar
      // `left` applies to the mouse position relative to the player so we need
      // to remove the progress control's left offset to know the mouse position
      // relative to the progress control
      mouseTime = Math.floor((left - progressControl.el().offsetLeft) / progressControl.width() * duration);
      for (time in settings) {
        if (mouseTime > time) {
          active = Math.max(active, time);
        }
      }
      setting = settings[active];
      if (setting.src && img.src != setting.src) {
        img.src = setting.src;
      }
      if (setting.style && img.style != setting.style) {
        extend(img.style, setting.style);
      }

      width = getVisibleWidth(img, setting.width || settings[0].width);
      halfWidth = width / 2;

      // make sure that the thumbnail doesn't fall off the right side of the left side of the player
      if ( (left + halfWidth) > right ) {
        left -= (left + halfWidth) - right;
      } else if (left < halfWidth) {
        left = halfWidth;
      }

      div.style.left = left + 'px';
    };

    // update the thumbnail while hovering
    progressControl.on('mousemove', moveListener);
    progressControl.on('touchmove', moveListener);

    moveCancel = function(event) {
      div.style.left = '-1000px';
    };

    // move the placeholder out of the way when not hovering
    progressControl.on('mouseout', moveCancel);
    progressControl.on('touchcancel', moveCancel);
    progressControl.on('touchend', moveCancel);
    player.on('userinactive', moveCancel);
  });
})();


/**
 * cb_vjs_custom.js
 */

 function new_player_height (videoid) {
	var player_ratio = 1.77777;

	var native_player = $(".cb_video_js_"+videoid+"-dimensions"); 
	var embed_player = $("#cb_player_"+videoid);

	var native_player_width = native_player.width();
	var player_container_width = embed_player.width();
	


	var native_player_height  = native_player_width/player_ratio;
	var embed_player_height = player_container_width/player_ratio;

	native_player.css("height",native_player_height+"px");
	embed_player.css("height",embed_player_height+"px");
}


/**
 * cb_vjs_plugin.js plugin
 */

 //Starting CB logo custom elements class
var cbvjslogo = function(player,options){
	var cbvjslogo = this;
	
	cbvjslogo.path = options.branding_logo;
	cbvjslogo.link = options.product_link;
	cbvjslogo.show = options.show_logo;
	cbvjslogo.player = player;
	
	cbvjslogo.init();
}

cbvjslogo.prototype.init = function(){
	var cbvjslogo = this;
	var CbLogoBrand = document.createElement("div");
	CbLogoBrand.id = "vjs-cb-logo";
	CbLogoBrand.className = "vjs-cblogo-brand";
	CbLogoBrand.className += " vjs-menu-button";
	CbLogoBrand.className += " vjs-control";
	CbLogoBrand.className += " vjs-button";
	CbLogoBrand.innerHTML = '<img style="display:block !important; cursor : pointer;margin:5px 0 0 4px;" src="data:image/png;base64, '+cbvjslogo.path+'" alt="">';

	var FullScreenToggle = cbvjslogo.player.controlBar.getChild('fullscreenToggle').el_;
	cbvjslogo.player.controlBar.el_.insertBefore(CbLogoBrand, FullScreenToggle);

	cbvjslogo.el = CbLogoBrand;
	cbvjslogo.onclick(); 
}

cbvjslogo.prototype.onclick = function(){
	var cbvjslogo = this;
	cbvjslogo.el.addEventListener('click',function(){
		window.open(cbvjslogo.link, '_blank');
	});
}

//Starting Captions Menu Holder Class
var cbvjsheader = function(player,options){
	var cbvjsheader = this;
	
	cbvjsheader.title = options.videotitle;
	cbvjsheader.uploader = options.uploader;
	cbvjsheader.videoid = options.videoid;
	cbvjsheader.player = player;
	
	cbvjsheader.init();
}

cbvjsheader.prototype.init = function(){
	var cbvjsheader = this;
	var CbVjsHeader = document.createElement("div");
	CbVjsHeader.id = "vjs-cb-header";
	CbVjsHeader.className = "vjs-cb-header-caption";
	CbVjsHeader.innerHTML = "<div class='captionBlock'><div class='vidTitle col'><a target='_blank' href='"+baseurl+"/watch_video.php?v="+cbvjsheader.videoid+"'>"+cbvjsheader.title+"</a></div><div class='uploaderName col'>by "+cbvjsheader.uploader+"</div></div>";

	var BigPlayButton = cbvjsheader.player.getChild('bigPlayButton').el_;
	cbvjsheader.player.el_.insertBefore(CbVjsHeader, BigPlayButton);
}

//Starting Captions Menu Holder Class
var cbvjsvolume = function(player){
	var cbvjsvolume = this;
	cbvjsvolume.player = player;
	cbvjsvolume.init();
}

cbvjsvolume.prototype.init = function(){
	var cbvjsvolume = this;
	cbvjsvolume.Currvol = "";
	cbvjsvolume.Muted = "";
	cbvjsvolume.vol_cookie = $.cookie("cb_volume");
	if (cbvjsvolume.vol_cookie){
		if (cbvjsvolume.vol_cookie == "muted"){
			cbvjsvolume.player.muted(true);
		}else{
			cbvjsvolume.player.volume(cbvjsvolume.vol_cookie);
		}
	}else{
		console.log("Ninja : Dont Mess Around Here! ");
	}
	cbvjsvolume.player.on('volumechange',function(){
		cbvjsvolume.Currvol = cbvjsvolume.player.volume();
		cbvjsvolume.Muted = cbvjsvolume.player.muted();
		
		if (cbvjsvolume.Muted == true || cbvjsvolume.Currvol == 0 ){
			$.cookie("cb_volume","muted", { expires : 10 });
		}else{
			$.cookie("cb_volume", cbvjsvolume.Currvol , { expires : 10 });
		}

	});
}

function cb_vjs_elements(settings){

	var logo_settings = settings.logo;
	var header_settings = settings.header;

	CbVjsLogo = new cbvjslogo(this,logo_settings);
	CbVjsHeader = new cbvjsheader(this,header_settings);
	CbVjsVolume = new cbvjsvolume(this);
}

videojs.plugin('cb_vjs_elements', cb_vjs_elements);

