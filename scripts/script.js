// Initialize the MMRPG global variables
var mmrpgBody = mmrpgBody;
var gameWindow = false;
var gameEngine = false;
var gameConnect = false;
var gameCanvas = false;
var gameConsole = false;
var gameActions = false;
var gameMusic = false;
var gameSettings = {};
// Initialize browser detection variables
var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
var isFirefox = typeof InstallTrigger !== 'undefined';   // Firefox 1.0+
var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0;
var isChrome = !!window.chrome && !isOpera;              // Chrome 1+
var isIE = /*@cc_on!@*/false || document.documentMode;   // At least IE6

// Define the MMRPG global settings variables
gameSettings.cacheTime = '00000000-00'; // the timestamp of when this game was last updated
gameSettings.baseHref = 'http://localhost/'; // the base href where this game is running
gameSettings.wapFlag = false; // whether or not this game is running in mobile mode
gameSettings.wapFlagIphone = false; // whether or not this game is running in mobile iphone mode
gameSettings.wapFlagIpad = false; // whether or not this game is running in mobile iphone mode
gameSettings.baseVolume = 0.5; // default animation frame base internal
gameSettings.eventTimeout = 800; // default animation frame base internal
gameSettings.eventTimeoutThreshold = 250; // timeout theshold for when frames stop cross-fading
gameSettings.eventAutoPlay = true; // whether or not to automatically advance events
gameSettings.eventCrossFade = true; // whether or not to canvas events have crossfade animation
gameSettings.eventCameraShift = true; // whether or not to canvas events have camera shifts
gameSettings.eventHooks = []; // default to empty but may be filled at runtime and used later
gameSettings.spriteRenderMode = 'default'; // the render mode we should be using for sprites
gameSettings.idleAnimation = true; // default to allow idle animations
gameSettings.indexLoaded = false; // default to false until the index is loaded
gameSettings.currentGameState = {}; // default to empty but may be filled at runtime and used later
gameSettings.currentActionPanel = 'loading'; // default to loading until changed elsewhere
gameSettings.autoScrollTop = false; // default to true to prevent too much scrolling
gameSettings.autoResizeWidth = true; // allow auto reszing of the game window width
gameSettings.autoResizeHeight = true; // allow auto reszing of the game window height
gameSettings.currentBodyWidth = 0; // collect the current window width and update when necessary
gameSettings.currentBodyHeight = 0; // collect the current window width and update when necessary
gameSettings.allowEditing = true; // default to true to allow all editing unless otherwise stated
gameSettings.audioBaseHref = ''; // the base href where audio comes from (empty if same as baseHref)
gameSettings.customIndex = {}; // default to empty but may be filled at runtime and used later

// Define an object to hold change events for settings when/if they happen
var gameSettingsChangeEvents = {};

// Define the perfect scrollbar settings
var thisScrollbarSettings = {
    wheelSpeed: 0.3,
    useBothWheelAxes: false,
    suppressScrollX: true
    };

// Create the game engine submit timer
var gameEngineSubmitTimeout = false;
var gameEngineSubmitReturn = false;
// Create a function for when the game engine is submit
function gameEngineSubmitFunction(){
    clearTimeout(gameEngineSubmitTimeout);
    gameEngineSubmitTimeout = false;
    //console.log('...it\'s been thirty seconds since gameEngine.submit()');
    var battleStatus = $('input[name=this_battle_status]', gameEngine).val();
    if (gameEngineSubmitReturn == false && battleStatus != 'complete'){
        //console.log('...and the server still has not responded.');
        var confirmRetry = confirm('The server has not responded for some time... \nWould you like to try sending the request again?');
        if (confirmRetry){
            //console.log('Resubmitting form.');
            gameEngine.submit();
            } else {
            //console.log('Resetting timeout.');
            requestAnimationFrame(function(){
                gameEngineSubmitTimeout = setTimeout(gameEngineSubmitFunction, 120000);
                });
            }
        return false;
        } else {
        //console.log('...and everything seems to have worked out. We\'re all good.');
        return true;
        }
}

// Initialize document ready events
$(document).ready(function(){

    // Define the MMRPG global context variables
    mmrpgBody = $('#mmrpg');
    gameWindow = $('#window');
    gameEngine = $('#engine');
    gameConnect = $('#connect');
    gameCanvas = $('#canvas');
    gameConsole = $('#console');
    gameActions = $('#actions');
    gameMusic = $('#music');
    gameAnimate = $('#animate');
    gameBattle = $('#battle');
    gamePrototype = $('#prototype');

    // Check if iPhone or iPad detected
    gameSettings.wapFlagIphone = (navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i)) ? true : false;
    gameSettings.wapFlagIpad = navigator.userAgent.match(/iPad/i) ? true : false;


    /*
     * INDEX EVENTS
     */

    if (mmrpgBody.length){

        // Update the dimensions
        gameSettings.currentBodyWidth = $(document).width(); //mmrpgBody.outerWidth();
        gameSettings.currentBodyHeight = $(document).height(); //mmrpgBody.outerHeight();

        // Tooltip only Text
        //console.log('assigning event for '+document.URL+';\n gameSettings.currentBodyWidth = '+gameSettings.currentBodyWidth+';\n gameSettings.currentBodyHeight = '+gameSettings.currentBodyHeight+'; ');

        // Only attach hover tooltips if NOT in mobile mode
        //if (!gameSettings.wapFlag && !gameSettings.wapFlagIphone && !gameSettings.wapFlagIpad){
        // Attempt to attach tooltips regardless of device
        if (true){

            var tooltipDelay = 1200; //600;
            var tooltipTimeout = false;
            var tooltipShowing = false;
            var tooltipInitiator = false;

            // Define the function for showing the tooltip
            var showTooltipFunction = function(e){
                var thisElement = $(this);
                $('.tooltip', mmrpgBody).empty();
                var thisDate = new Date();
                var thisTime = thisDate.getTime();
                //console.log('starting the tooltip at '+thisTime);
                var thisClassList = thisElement.attr('class') != undefined ? thisElement.attr('class').split(/\s+/) : '';
                var thisTitle = thisElement.attr('data-backup-title') != undefined ? thisElement.attr('data-backup-title') : (thisElement.attr('title') != undefined ? thisElement.attr('title') : '');
                var thisTooltip = thisElement.attr('data-tooltip') != undefined ? thisElement.attr('data-tooltip') : '';
                if (!thisTooltip.length && thisElement.attr('data-click-tooltip') != undefined){ thisTooltip = thisElement.attr('data-click-tooltip'); }
                if (!thisTitle.length && !thisTooltip.length){ return false; }
                else if (thisTitle.length && !thisTooltip.length){ thisTooltip = thisTitle; }
                thisTooltip = thisTooltip.replace(/\n/g, '<br />').replace(/\|\|/g, '<br />').replace(/\|/g, '<span class="pipe">|</span>').replace(/\s?\/\/\s?/g, '<br />').replace(/\[\[([^\[\]]+)\]\]/ig, '<span class="subtext">$1</span>');
                var thisTooltipAlign = thisElement.attr('data-tooltip-align') != undefined ? thisElement.attr('data-tooltip-align') : 'left';
                var thisTooltipType = thisElement.attr('data-tooltip-type') != undefined ? thisElement.attr('data-tooltip-type') : '';
                if (!thisTooltipType.length){
                    for (i in thisClassList){
                        var tempClass = thisClassList[i] != undefined ? thisClassList[i].toString() : '';
                        //console.log('tempClass = '+tempClass);
                        if (tempClass.match(/^(field_|player_|robot_|ability_|item_)?type$/) || tempClass.match(/^(field_|player_|robot_|ability_|item_)?type_/) || tempClass.match(/^(energy|weapons|attack|defense|speed|light|cossack|wily|experience|level|damage|recovery|none|cutter|impact|freeze|explode|flame|electric|time|earth|wind|water|swift|nature|missile|crystal|shadow|space|shield|laser|copy)(_|$)/)){
                            //console.log('tempClass match!');
                            thisTooltipType += tempClass+' ';
                            }
                        //console.log('thisTooltipType = '+thisTooltipType);
                        }
                    }
                if (!thisTooltipType.length){
                    thisTooltipType = 'type none';
                    }
                //console.log('thisTitle : '+thisTitle);
                //console.log('append and trigger animation at '+thisTime);
                thisElement.attr('data-backup-title', thisTitle).removeAttr('title');
                if (!$('.tooltip', mmrpgBody).length){ $('<p class="tooltip '+thisTooltipType+'"></p>').html('<span class="message" style="text-align:'+thisTooltipAlign+';">'+thisTooltip+'</span>').appendTo(mmrpgBody).fadeIn('fast'); }
                else { $('.tooltip', mmrpgBody).removeClass().addClass('tooltip').addClass(thisTooltipType).html('<span class="message" style="text-align:'+thisTooltipAlign+';">'+thisTooltip+'</span>').fadeIn('fast'); }
                //$('.tooltip', mmrpgBody).css({width:''});
                //var toolwidth = $('.tooltip', mmrpgBody).outerWidth();
                //$('.tooltip', mmrpgBody).css({width:toolwidth+'px'});
                alignTooltipFunction.call(this, e);
                tooltipShowing = true;
                };

            // Define the function for positioning the tooltip
            var alignTooltipFunction = function(e){

                var mouseX = e.pageX;
                var mouseY = e.pageY;

                $('.tooltip', mmrpgBody).css({left:0,top:0,right:'auto',bottom:'auto'});
                var toolWidth = $('.tooltip', mmrpgBody).outerWidth() + 20;
                var toolHeight = $('.tooltip', mmrpgBody).outerHeight() + 10;

                var invertX = mouseX >= (gameSettings.currentBodyWidth / 2) ? true : false;
                var invertY = mouseY >= (gameSettings.currentBodyHeight / 2) ? true : false;

                var newPosX = mouseX + (invertX ? ((toolWidth + 5) * -1) : 5);
                var newPosY = mouseY + (invertY ? ((toolHeight + 5) * -1) : 5);

                $('.tooltip', mmrpgBody).css({left:newPosX,top:newPosY});

                };

            // Define the live MOUSEENTER events for any elements with a title tag (which should be many)
            var tooltipSelector = '*[title],*[data-backup-title]:not([data-click-tooltip]),*[data-tooltip]';
            $(tooltipSelector, mmrpgBody).live('mouseenter', function(e){
                e.preventDefault();
                if (tooltipTimeout == false){
                    var thisObject = this;
                    tooltipInitiator = thisObject;
                    requestAnimationFrame(function(){
                        tooltipTimeout = setTimeout(function(){
                            tooltipShowing = true;
                            showTooltipFunction.call(thisObject, e);
                            }, tooltipDelay);
                        });
                    var thisElement = $(this);
                    if (thisElement.attr('title')){
                        thisElement.attr('data-backup-title', thisElement.attr('title'));
                        thisElement.removeAttr('title');
                        }
                    }
                });

            // Define the live CLICK events for any elements with a click-title tag (which should be a few)
            var tooltipSelector = '*[data-click-tooltip]';
            $(tooltipSelector, mmrpgBody).live('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                if (tooltipShowing){
                    $('.tooltip', mmrpgBody).empty();
                    clearTimeout(tooltipTimeout);
                    tooltipTimeout = false;
                    tooltipShowing = false;
                    } else {
                    if (tooltipTimeout == false){
                        var thisObject = this;
                        tooltipInitiator = thisObject;
                        requestAnimationFrame(function(){
                            tooltipShowing = true;
                            showTooltipFunction.call(thisObject, e);
                            });
                        }
                    }
                });

            // Define the live MOUSEMOVE events for any elements with a title tag (which should be many)
            $(tooltipSelector, mmrpgBody).live('mousemove', function(e){
                if (!tooltipShowing){ return false; }
                alignTooltipFunction.call(this, e);
                });

            // Define the live MOUSELEAVE events for any elements with a title tag (which should be many)
            $(tooltipSelector, mmrpgBody).live('mouseleave', function(e){
                e.preventDefault();
                $('.tooltip', mmrpgBody).empty();
                clearTimeout(tooltipTimeout);
                tooltipTimeout = false;
                tooltipShowing = false;
                });

            // If the user clicks somewhere in the body, immediately remove the tooltip
            $('*', mmrpgBody).click(function(e){
                if (e.target === tooltipInitiator){ return; }
                $('.tooltip', mmrpgBody).empty();
                clearTimeout(tooltipTimeout);
                tooltipTimeout = false;
                tooltipShowing = false;
                });

            }

    }

    // Ensure this is the battle document
    if (gameWindow.length){

        // Add click-events to the debug panel links
        $('a.battle', gamePrototype).live('click', function(e){
            var windowFrame = $('iframe', gameWindow);
            var thisLink = $(this).attr('href');
            if (windowFrame.attr('src') != 'about:blank'){
                e.preventDefault();
                var thisConfirm = 'Are you sure you want to switch battles?  Progress will be lost and all robots will be reset.';
                if (confirm(thisConfirm)){
                //if (true){
                    windowFrame.attr('src', thisLink);
                    return true;
                    }
                } else {
                windowFrame.attr('src', thisLink);
                return false;
                }
            });

        // Set up the game music options
        if (gameMusic.length){

            // Add the click-events to the music toggle button
            $('a.toggle', gameMusic).bind('click touch', function(e){
                e.preventDefault();
                if (gameSettings.indexLoaded){
                    if ($('iframe', gameWindow).hasClass('loading')){ $('iframe', gameWindow).css({opacity:0}).removeClass('loading').animate({opacity:1}, 1000, 'swing'); } // DEBUG
                    if (gameMusic.hasClass('onload')){
                        gameMusic.removeClass('onload');
                        gameMusic.find('.start').remove();
                        mmrpg_music_toggle();
                        } else {
                        mmrpg_music_toggle();
                        }
                    return true;
                    } else {
                    return false;
                    }
                });
            // Automatically load the title screen music
            mmrpg_music_load('misc/player-select', true, false);

            }

    }


    /*
     * BATTLE EVENTS
     */

    // Ensure this is the battle document
    if (gameEngine.length){

        // Define a list of valid render modes we can use
        //var allowedRenderModes = ['default', 'auto', 'smooth', 'pixelated', 'high-quality', 'crisp-edges'];
        var allowedRenderModes = ['default', 'pixelated', 'crisp-edges'];

        // Update the body to use the requested sprite rendering mode
        //console.log('setting data-render-mode to ', gameSettings['spriteRenderMode']);
        mmrpgBody.attr('data-render-mode', gameSettings['spriteRenderMode']);

        // If a localStorage value has been set, load that instead
        if (typeof window.localStorage !== 'undefined'){
            var spriteRenderMode = window.localStorage.getItem('spriteRenderMode');
            if (typeof spriteRenderMode !== 'undefined' && allowedRenderModes.indexOf(spriteRenderMode) !== -1){
                gameSettings['spriteRenderMode'] = spriteRenderMode;
            }
        }

        // Define a change event for whenever this game setting is altered
        gameSettingsChangeEvents['spriteRenderMode'] = function(newValue){
            //console.log('setting data-render-mode to ', newValue);
            mmrpgBody.attr('data-render-mode', newValue);
            if (typeof window.localStorage !== 'undefined'){
                window.localStorage.setItem('spriteRenderMode', newValue);
                }
            var $actionButton = $('.button.action_option[data-panel="settings_spriteRenderMode"]', gameActions);
            if ($actionButton.length){
                var newValueTitle = newValue.replace('/\-/g', ' ').replace(/\b\w/g, function(l){ return l.toUpperCase() });
                if (typeof gameSettings.customIndex.renderModes !== 'undefined'){
                    var renderModesIndex = gameSettings.customIndex.renderModes;
                    newValueTitle = renderModesIndex[newValue]['name'];
                    }
                $actionButton.find('.value').html(newValueTitle);
                }
            };
        gameSettingsChangeEvents['spriteRenderMode'](gameSettings.spriteRenderMode);

        // Define a change event for whenever this game setting is altered
        gameSettingsChangeEvents['eventTimeout'] = function(newValue){
            //console.log('setting eventTimeout to ', newValue, typeof newValue);
            updateCameraShiftTransitionDuration();
            var $actionButton = $('.button.action_option[data-panel="settings_eventTimeout"]', gameActions);
            if ($actionButton.length){
                var newValueTitle = '(1f/'+newValue+'ms)';
                if (typeof gameSettings.customIndex.gameSpeeds !== 'undefined'){
                    var gameSpeedIndex = gameSettings.customIndex.gameSpeeds;
                    newValueTitle = gameSpeedIndex[newValue]['name'];
                    }
                $actionButton.find('.value').html(newValueTitle);
                }
            };
        gameSettingsChangeEvents['eventTimeout'](gameSettings.eventTimeout);

        // Define a change event for whenever this game setting is altered
        gameSettingsChangeEvents['eventCrossFade'] = function(newValue){
            //console.log('setting eventCrossFade to ', newValue, ' w/ timeout at ', gameSettings.eventTimeout);
            updateCameraShiftTransitionDuration();
            };
        gameSettingsChangeEvents['eventCrossFade'](gameSettings.eventCrossFade);

        // Define a change event for whenever this game setting is altered
        gameSettingsChangeEvents['eventCameraShift'] = function(newValue){
            //console.log('setting eventCameraShift to ', newValue);
            updateCameraShiftTransitionDuration(0);
            if (newValue === false){
                mmrpg_canvas_camera_shift();
                }
            else if (newValue === true
                && gameSettings.currentActionPanel !== 'loading'
                && !mmrpgEvents.length){
                canvasAnimationCameraShift = {
                    shift: 'left',
                    focus: 'active',
                    depth: 0,
                    depthInc: 1,
                    offset: 0,
                    };
                }
            updateCameraShiftTransitionDuration();
            };
        gameSettingsChangeEvents['eventCameraShift'](gameSettings.eventCameraShift);

        // Auto-highlight settings buttons that are "active"
        var settingsWithActiveStates = ['eventTimeout', 'eventCrossFade', 'spriteRenderMode'];
        for (var i = 0; i < settingsWithActiveStates.length; i++){
            var settingsKey = settingsWithActiveStates[i];
            var settingsValue = gameSettings[settingsKey];
            if (typeof settingsValue === 'undefined'){ continue; }
            if (typeof settingsValue === 'boolean'){ settingsValue = settingsValue ? 'true' : 'false'; }
            var settingsButtonWrapper = $('.wrapper.actions_settings_'+settingsKey, gameActions);
            var activeSettingsButton = settingsButtonWrapper.find('a[data-action="settings_'+settingsKey+'_'+settingsValue+'"]');
            settingsButtonWrapper.find('a[data-action]').removeClass('active');
            activeSettingsButton.addClass('active');
            }

        // Attach a submit event for tracking timestaps
        gameEngine.submit(function(){
            //console.log('gameEngine.submit() triggered, setting timeout');
            clearTimeout(gameEngineSubmitTimeout);
            gameEngineSubmitTimeout = false;
            canvasAnimationCameraTimer = 0;
            requestAnimationFrame(function(){
                gameEngineSubmitTimeout = setTimeout(gameEngineSubmitFunction, 120000);
                });
            });

        // Add click-events to the hidden resend command
        $('#actions .actions_resend', mmrpgBody).live('click', function(e){
            e.preventDefault();
            //console.log('actions_resend clicked');
            var loadingDisplay = $('#actions_loading', mmrpgBody).css('display');
            if (loadingDisplay == 'none'){ return false; }
            var confirmText = 'Would you like to resubmit your last action?\nThis can have unpredicable results on your battle...\nResend anyway?';
            if (confirm(confirmText)){
                // Switch to the loading screen
                //console.log('switch to loading panel');
                mmrpg_action_panel('loading');
                //console.log('Resubmitting form.');
                gameEngine.submit();
                }
            });

        // Set up rge game animate options
        if (true){

            // Add the click-events to the animate toggle button
            $('a.toggle', gameAnimate).bind('click touch', function(e){
                e.preventDefault();
                mmrpg_toggle_animation();
                return true;
                });
            // Automatically start the animation sequences
            //mmrpg_start_animation();

            }

        // Add a click event to the gameActions panel buttons
        $('a[data-panel]', gameActions).live('click', function(e){
            var thisPanel = $(this).attr('data-panel');
            mmrpg_action_panel(thisPanel);
            });

        // Add a click event to the gameActions action buttons
        $('a[data-action]', gameActions).live('click', function(e){
            // Collect the action and preload, if set
            var thisAction = $(this).attr('data-action');
            var thisPreload = $(this).attr('data-preload') !== undefined ? $(this).attr('data-preload') : false;
            var thisTarget = $(this).attr('data-target') !== undefined ? $(this).attr('data-target') : false;
            //var thisPanel = $(this).parent().parent().attr('id');
            var thisPanel = $(this).closest('.wrapper').attr('id');
            thisPanel = thisPanel.replace(/^actions_/i, '');
            //alert(thisPanel);
            // Trigger the requested action and return the result
            return mmrpg_action_trigger(thisAction, thisPreload, thisTarget, thisPanel);
            });

        // Add a hover event to all the gameAction sprites
        $('.sprite[data-action]', gameCanvas)
            .live('mouseenter', function(){
                if ($('#actions_scan', gameActions).is(':visible')){
                    $(this).css({cursor:'pointer'});
                    if ($(this).hasClass('sprite_40x40')){ var thisSize = 40; }
                    else if ($(this).hasClass('sprite_80x80')){ var thisSize = 80; }
                    else if ($(this).hasClass('sprite_160x160')){ var thisSize = 160; }
                    $(this).addClass('sprite_'+thisSize+'x'+thisSize+'_focus');
                    var thisOffset = parseInt($(this).css('z-index'));
                    $('.event', gameCanvas).append('<div class="scan_overlay" style="z-index: '+(thisOffset-1)+';">&nbsp;</div>');
                    } else {
                    $(this).css({cursor:'default'});
                    return false;
                    }
                })
            .live('mouseleave', function(){
                if ($(this).hasClass('sprite_40x40')){ var thisSize = 40; }
                else if ($(this).hasClass('sprite_80x80')){ var thisSize = 80; }
                else if ($(this).hasClass('sprite_160x160')){ var thisSize = 160; }
                $(this).removeClass('sprite_'+thisSize+'x'+thisSize+'_focus');
                $('.scan_overlay', gameCanvas).remove();
                });

        // Add scan functionality to all on-screen robot sprites
        $('.sprite[data-action]', gameCanvas).live('click', function(){
            if ($('#actions_scan', gameActions).is(':visible')){
                $(this).css({cursor:'pointer'});
                // Collect the action and preload, if set
                var thisAction = $(this).attr('data-action');
                var thisPreload = $(this).attr('data-preload')  !== undefined ? $(this).attr('data-preload') : false;
                var thisTarget = $(this).attr('data-target')  !== undefined ? $(this).attr('data-target') : false;
                // Remove the focus class now clicked
                if ($(this).hasClass('sprite_40x40')){ var thisSize = 40; }
                else if ($(this).hasClass('sprite_80x80')){ var thisSize = 80; }
                else if ($(this).hasClass('sprite_160x160')){ var thisSize = 160; }
                $(this).removeClass('sprite_'+thisSize+'x'+thisSize+'_focus');
                // Trigger the requested action and return the result
                return mmrpg_action_trigger(thisAction, thisPreload, thisTarget);
                } else {
                $(this).css({cursor:'default'});
                return false;
                }
            });

        // Add a specialized click event for the gameActions continue button
        $('a[data-action=continue]', gameActions).live('click', function(e){
            mmrpg_events();
            });

        // Start animating the canvas randomly
        //mmrpg_canvas_animate();

        // Trigger the panel switch to the "next" action
        var nextAction = $('input[name=next_action]', gameEngine).val();
        if (nextAction.length){ mmrpg_action_panel(nextAction); }

    }

    /*
     * WINDOW RESIZE EVENTS
     */

    // Remove the hard-coded heights for the main iframe
    $('iframe', gameWindow).removeAttr('width').removeAttr('height');

    // Trigger the windowResizeUpdate function automatically
    windowResizeUpdate('startup');
    window.onresize = function(){ return windowResizeUpdate('onresize'); }


    /*
     * MOBILE EVENTS
     */

    // Check if we're running the game in mobile mode
    if (gameSettings.wapFlag){

        //alert('wapFlag');

        // Remove the hard-coded heights for the main iframe
        $('iframe', gameWindow).removeAttr('width').removeAttr('height');

        // Let the user know about the full-screen option for mobile browsers
        if (('standalone' in window.navigator) && !window.navigator.standalone){
            //alert('Please use "Add to Home Screen" option for best view! :)');
            } else if (('standalone' in window.navigator) && window.navigator.standalone){
            //alert('launched from full-screen ready browser, and in full screen!');
            $('body').addClass('mobileFlag_fullScreen');
            } else {
            //alert('launched from a regular old browser...');
            }

        // Prevent scrolling as long as the exclusion class isn't present
        if (false){
            /*
            // Prevent window scrolling, or scrolling of any kind, for mobile views
            $(document).bind('touchmove', function(e){
                //alert('touchmove');
                //alert('touchmove etarget : '.$(e.target).attr('class'));
                e.preventDefault();
                });
            $('*').live('touchstart', function(e) {
                //alert('touchstart etarget : '.$(e.target).attr('class'));
                this.onclick = this.onclick || function () { };
                });
            */
        }



        // Change the body's orientation flag classes
        orientationModeUpdate('startup');
        window.onorientationchange = function(){ return orientationModeUpdate('onorientationchange'); }
        window.onresize = function(){ return orientationModeUpdate('onresize'); }
        if (gameSettings.autoScrollTop === true){ window.onscroll = function(){ return orientationModeUpdate('onscroll'); } }

    }

    // Create the variable functions for Pausing/Unpausing the game
    var mmrpgPauseFunction = function(){
        //gameSettings.eventAutoPlay = false;
        //clearTimeout(canvasAnimationTimeout);
        clearInterval(canvasAnimationTimeout);
        //mmrpg_music_stop();
        };
    var mmrpgPlayFunction = function(){
        //gameSettings.eventAutoPlay = true;
        if (gameSettings.eventAutoPlay && !mmrpgEvents.length){ mmrpg_canvas_animate(); }
        //mmrpg_music_play();
        };

    // Attach blur/focus events to the window to automatically pause/play
    if (gameBattle.length){
        //console.log('gameBattle.length = '+gameBattle.length+';');
        $(window).blur(function(){
            //console.log('mmrpgPauseFunction();');
            mmrpgPauseFunction();
            }).focus(function(){
            //console.log('mmrpgPlayFunction();');
            mmrpgPlayFunction();
            });
    }


});

// Define a function for updating the window sizes
var lastOrientation = false;
var defaultViewportSettings = false;
var defaultViewportUpdated = false;
var windowResizeUpdateTimeout = false;
function windowResizeUpdate(updateType){
    //console.log('windowResizeUpdate('+updateType+')');

    /*
    // Collect the actual screen sizes for reference
    var screenWidth = window.innerWidth
        || document.documentElement.clientWidth
        || document.body.clientWidth;
    var screenHeight = window.innerHeight
        || document.documentElement.clientHeight
        || document.body.clientHeight;
    //console.log('screenWidth =', screenWidth, '| screenHeight =', screenHeight);
    */

    // Collect viewport settings in case we have to adjust
    if (!defaultViewportSettings){
        var $metaViewport = $('head meta[name="viewport"]');
        var viewportContent = $metaViewport.attr('content');
        if (typeof viewportContent !== 'undefined'
            && viewportContent.length){
            viewportContent = viewportContent.replace(/\s+/g, '').split(',');
            //console.log('viewportContent =', viewportContent);
            defaultViewportSettings = {};
            for (var i = 0; i < viewportContent.length; i++){
                var setting = viewportContent[i].split('=');
                var settingName = setting[0];
                var settingValue = setting[1];
                if (settingValue.match(/^[0-9]+$/)){ settingValue = parseInt(settingValue); }
                defaultViewportSettings[settingName] = settingValue;
                }
            //console.log('defaultViewportSettings =', defaultViewportSettings);
            }
        }

    // Re-generate the viewport settings, skipping any unsupported settings
    var windowWidth = $(window).width();
    var windowHeight = $(window).height();
    if (windowWidth >= windowHeight){ var newOrientation = 'landscape'; }
    else { var newOrientation = 'portrait'; }
    if (window === window.top && (updateType === 'startup' || lastOrientation !== newOrientation)){
        var newViewportSettings = $.extend(true,{},defaultViewportSettings);
        if (typeof newViewportSettings['min-width'] !== 'undefined'){
            var minWidth = newViewportSettings['min-width'];
            delete newViewportSettings['min-width'];
            if (windowWidth < minWidth){
                newViewportSettings['width'] = minWidth;
                newViewportSettings['initial-scale'] = windowWidth / minWidth;
                //if (typeof newViewportSettings['initial-scale'] !== 'undefined'){
                //    delete newViewportSettings['initial-scale'];
                //    }
                }
            }
        var newViewportContent = [];
        var newViewportSettingsKeys = Object.keys(newViewportSettings);
        for (var i = 0; i < newViewportSettingsKeys.length; i++){
            var settingName = newViewportSettingsKeys[i];
            var settingValue = newViewportSettings[settingName];
            newViewportContent.push(settingName+'='+settingValue);
            }
        //console.log('newViewportContent =', newViewportContent.join(', '));
        $('head meta[name="viewport"]').remove();
        var $metaViewport = $('<meta name="viewport" />');
        $metaViewport.attr('content', newViewportContent.join(', '));
        $metaViewport.appendTo('head');
        lastOrientation = newOrientation;
        }

    // Define the base values to resize from
    var canvasHeight = 267;
    var consoleHeight = 256;
    var consoleMessageHeight = 64;
    var actionsHeight = 225;
    //console.log('windowResizeUpdate('+updateType+');\n', {canvasHeight:canvasHeight,consoleHeight:consoleHeight,consoleMessageHeight:consoleMessageHeight,actionsHeight:actionsHeight});

    // Check if this is the main window or if it's a child
    if (window === window.top){
        // Collect this window's width and height
        var windowType = 'top';
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();
        var gameWidth = gameWindow.width();
        var gameHeight = gameWindow.height();
        } else {
        // Collect the parent window's width and height
        var windowType = 'child';
        var windowWidth = $(parent.window).width();
        var windowHeight = $(parent.window).height();
        var gameWidth = parent.gameWindow.width();
        var gameHeight = parent.gameWindow.height();
        }

    var bodyInnerHeight = mmrpgBody.innerHeight();
    //console.log('windowType = '+windowType+' \nwindowWidth = '+windowWidth+' \nwindowHeight = '+windowHeight+' \nbodyInnerHeight = '+bodyInnerHeight);
    if (bodyInnerHeight < windowHeight){ windowHeight = bodyInnerHeight; }

    /*
    if (window === window.top && windowHeight <= 924){
        alert('preventOverflow');
        $('html,body').css({height:windowHeight+'px',overflow:'hidden'});
    } else {
        alert('resetOverflow');
        $('html,body').css({height:'',overflow:''});
    }
    */

    // Update the dimensions
    gameSettings.currentBodyWidth = windowWidth; //$(document).width(); //mmrpgBody.outerWidth();
    gameSettings.currentBodyHeight = windowHeight; //$(document).height(); //mmrpgBody.outerHeight();

    //console.log({windowWidth:windowWidth,windowHeight:windowHeight,gameWidth:gameWidth,gameHeight:gameHeight,gameSettings:gameSettings});

    var windowModWidth = localStorage.getItem('mmrpg-window-width') || 'flex';
    var windowModHeight = localStorage.getItem('mmrpg-window-height') || 'flex';

    // Check if the window is in landscape mode and update the session
    var thisRequestType = 'session';
    var thisRequestData = 'index_settings,windowFlag,';
    //if (windowWidth >= (1024 + 12)){ $('body').addClass('windowFlag_landscapeMode'); thisRequestData += 'landscapeMode'; }
    if (Math.ceil(windowModWidth === 'flex' ? windowWidth : gameWidth) >= 1024){ $('body').addClass('windowFlag_landscapeMode'); thisRequestData += 'landscapeMode'; }
    else { $('body').removeClass('windowFlag_landscapeMode'); thisRequestData += 'portraitMode'; }
    if (windowResizeUpdateTimeout !== false){ clearTimeout(windowResizeUpdateTimeout); }
    windowResizeUpdateTimeout = setTimeout(function(){
        $.post('scripts/script.php',{requestType:thisRequestType,requestData:thisRequestData});
        //console.log('scripts/script.php',{requestType:thisRequestType,requestData:thisRequestData});
        }, 1000);

    // Calculate the new game and console height values
    var newGameHeight = windowHeight - 25; //15;
    if (gameSettings.wapFlagIphone && newGameHeight > 924){ newGameHeight = 924; }
    var newConsoleHeight = newGameHeight - (canvasHeight + actionsHeight);
    //console.log({windowHeight:windowHeight,newGameHeight:newGameHeight,newConsoleHeight:newConsoleHeight});

    if ((newConsoleHeight - 3) < (consoleMessageHeight * 2)){
        var thisMinimum = consoleMessageHeight * 2;
        newGameHeight = newGameHeight + (thisMinimum - newConsoleHeight);
        newConsoleHeight = thisMinimum + 3;
        //console.log({thisMinimum:thisMinimum,newGameHeight:newGameHeight,newConsoleHeight:newConsoleHeight});
        } else if ((newConsoleHeight - 3) %  consoleMessageHeight != 0){
        var thisRemainer = (newConsoleHeight - 3) %  consoleMessageHeight;
        newGameHeight = newGameHeight - thisRemainer;
        newConsoleHeight = newConsoleHeight - thisRemainer;
        //console.log({thisRemainer:thisRemainer,newGameHeight:newGameHeight,newConsoleHeight:newConsoleHeight});
        }

    // If the console exists, resize it
    if (gameConsole.length && !gameConsole.hasClass('noresize')){
        //console.log('gameConsole.length && !gameConsole.hasClass(\'noresize\');\ngameConsole.height('+newConsoleHeight+' - 3); ');
        gameConsole.height(newConsoleHeight - 3);
        var gameConsoleWrapper = gameConsole.find('.wrapper');
        //gameConsoleWrapper.css({overflow:'scroll',width:(gameConsole.width() + 18)+'px',height:(gameConsole.height() + 18)+'px'});
        gameConsoleWrapper.css({width:(gameConsole.width() + 18)+'px',height:(gameConsole.height() + 0)+'px'});
        gameConsoleWrapper.perfectScrollbar(thisScrollbarSettings);
        }

    // If height reszing is allowed, update the window height
    if (gameSettings.autoResizeHeight != false){
        //console.log('gameSettings.autoResizeHeight != false;\ngameWindow.height('+newGameHeight+');');
        gameWindow.height(newGameHeight);
        $('iframe', gameWindow).height(newGameHeight - 6);
        }

    // Reset the window scroll to center elements properly
    if (gameSettings.autoScrollTop === true && updateType != 'onscroll'){
        //console.log('gameSettings.autoScrollTop == true;\nwindow.scrollTo(0, 1);');
        window.scrollTo(0, 1);
        if (window !== window.top){ parent.window.scrollTo(0, 1); }
        }


    // Tooltip only Text
    //console.log('resizing event for '+document.URL+';\n gameSettings.currentBodyWidth = '+gameSettings.currentBodyWidth+';\n gameSettings.currentBodyHeight = '+gameSettings.currentBodyHeight+'; ');

    // Return true on success
    return true;
}




// Define a function for updating the orientation Mode
function orientationModeUpdate(updateType){
    // Check if this is the main window or if it's a child
    if (window === window.top){
        // If this is the main window, collect it's orientation variable
        if (!isNaN(window.orientation)){ var orientationMode = (window.orientation == 0 || window.orientation == 180) ? 'portrait' : 'landscape'; }
        else { var orientationMode = ($(window).width() < 980) ? 'portrait' : 'landscape'; }
        } else {
        // Otherwise, check the parent window's orientation variable
        window.top.testValue = true;
        if (!isNaN(window.top.orientation)){ var orientationMode = (window.top.orientation == 0 || window.top.orientation == 180) ? 'portrait' : 'landscape'; }
        else { var orientationMode = ($(window.top).width() < 980) ? 'portrait' : 'landscape'; }
        }
    // Determine if this user is running is non-fullscreen mode
    var notFullscreenMode = ('standalone' in window.navigator) && !window.navigator.standalone ? true : false;
    // Update the orientation variables on this window's body elements
    if (orientationMode == 'portrait'){
        $('body').removeClass('mobileFlag_landscapeMode').addClass('mobileFlag_portraitMode');
        if (notFullscreenMode){ $('body').removeClass('mobileFlag_landscapeMode_notFullscreen').addClass('mobileFlag_portraitMode_notFullscreen');  }
        } else {
        $('body').removeClass('mobileFlag_portraitMode').addClass('mobileFlag_landscapeMode');
        if (notFullscreenMode){ $('body').removeClass('mobileFlag_portraitMode_notFullscreen').addClass('mobileFlag_landscapeMode_notFullscreen');   }
        }

    // Reset the window scroll to center elements properly
    if (gameSettings.autoScrollTop === true && updateType != 'onscroll'){
        //console.log('gameSettings.autoScrollTop == true;\nwindow.scrollTo(0, 1);');
        window.scrollTo(0, 1);
        if (window !== window.top){ parent.window.scrollTo(0, 1); }
        }

    //$('body').css('border', '10px solid red').animate({borderWidth : '0'}, 1000, 'swing');
    // DEBUG
    //alert('<body class="'+$('body').attr('class')+'">\n'+updateType+'\n</body>');
    // Check if this is a child frame and this is not a startup call
    if (window !== window.top){
        // Alert the user of the orientation change (used to fix a bug with iframe not updating)
        parent.window.location.hash = '#'+orientationMode;
        //alert('Screen orientation changed...\nGame display updated!');
        }
    // Return the final orientation mode
    //console.log({orientationMode:orientationMode,notFullscreenMode:notFullscreenMode,bodyClass:$('body').attr('class')});
    return orientationMode;
}

function localFunction(myMessage){
    alert(myMessage);
}

// Define a function for randomly animating canvas robots (idle animation, background animation, more)
var backgroundDirection = 'left';
var canvasAnimationTimeout = false;
var canvasAnimationCameraShift = false;
var canvasAnimationCameraLastShift = false;
var canvasAnimationCameraTimer = 0;
var canvasAnimationCameraDelay = 5;
function mmrpg_canvas_animate(){
    //console.log('mmrpg_canvas_animate();');
    //console.log('gameSettings.idleAnimation:', gameSettings.idleAnimation);
    //console.log('canvasAnimationCameraTimer:', canvasAnimationCameraTimer, 'canvasAnimationCameraDelay:', canvasAnimationCameraDelay);
    //clearTimeout(canvasAnimationTimeout);
    clearInterval(canvasAnimationTimeout);
    if (!gameSettings.idleAnimation){  return false; }

    // Collect the current battle status and result
    var battleStatus = $('input[name=this_battle_status]', gameEngine).val();
    var battleResult = $('input[name=this_battle_result]', gameEngine).val();


    // Check to see if we should skip camera animations for any reason
    var skipCameraShift = false;
    var currentGameState = gameSettings.currentGameState;
    var currentAction = currentGameState['this_action'];
    var currentActionPanel = gameSettings.currentActionPanel;
    //console.log({eventCameraShift:gameSettings.eventCameraShift,currentActionPanel:gameSettings.currentActionPanel,mmrpgEventsLength:mmrpgEvents.length});
    //console.log('currentGameState:', currentGameState);
    //console.log('currentAction:', currentAction);
    //console.log('currentActionPanel:', currentActionPanel);
    if (currentActionPanel === 'loading' && currentAction === 'start'){ skipCameraShift = true; }
    //console.log('skipCameraShift:', skipCameraShift);

    // If the camera is not yet shifted, checked to see if we randomly should
    if (!skipCameraShift
        && gameSettings.eventCameraShift
        && !mmrpgEvents.length){

        // Increment the camera shift timer and, when ready, trigger some motion
        //console.log('canvasAnimationCameraTimer = ', canvasAnimationCameraTimer);
        var canvasAnimationCameraTimerMax = canvasAnimationCameraDelay;
        if (battleStatus == 'complete'){ canvasAnimationCameraTimerMax = Math.ceil(canvasAnimationCameraTimerMax / 2); }
        if (!canvasAnimationCameraShift
            && canvasAnimationCameraTimer >= canvasAnimationCameraTimerMax){
            var shiftRandom = Math.floor(Math.random() * 100);
            if (shiftRandom <= 33){
                var lastShift = canvasAnimationCameraLastShift;
                if (typeof lastShift.shift !== 'undefined'){ var shiftDirection = lastShift.shift !== 'left' ? 'left' : 'right'; }
                else { var shiftDirection = (shiftRandom % 2 === 0 ? 'left' : 'right'); }
                var focusRandom = Math.floor(Math.random() * 100);
                var depthRandom = Math.floor(Math.random() * 100);
                canvasAnimationCameraShift = {
                    shift: shiftDirection,
                    focus: (focusRandom % 3 === 0 ? 'bench' : 'active'),
                    depth: (depthRandom % 2 === 0 ? 0 : 8),
                    depthInc: (depthRandom % 2 === 0 ? 1 : -1),
                    offset: 0,
                    };
                if (battleStatus == 'complete'){
                    if (battleResult == 'victory'){ canvasAnimationCameraShift.shift = 'left'; }
                    else if (battleResult == 'defeat'){ canvasAnimationCameraShift.shift = 'right'; }
                    }
                canvasAnimationCameraTimer = 0;
                canvasAnimationCameraLastShift = canvasAnimationCameraShift;
                //console.log('canvasAnimationCameraShift:', canvasAnimationCameraShift);
                }
            }

        // If the camera is currently being shifted, we need to animate that
        if (canvasAnimationCameraShift){
            canvasAnimationCameraShift.depth += canvasAnimationCameraShift.depthInc;
            if (canvasAnimationCameraShift.depth > 8
                || canvasAnimationCameraShift.depth < 1){
                canvasAnimationCameraShift = false;
                updateCameraShiftTransitionTiming();
                updateCameraShiftTransitionDuration();
                mmrpg_canvas_camera_shift();
                } else {
                mmrpg_canvas_camera_shift(
                    canvasAnimationCameraShift.shift,
                    canvasAnimationCameraShift.focus,
                    canvasAnimationCameraShift.depth,
                    canvasAnimationCameraShift.offset
                    );
                updateCameraShiftTransitionTiming('linear');
                updateCameraShiftTransitionDuration(1);
                }
            } else {
            canvasAnimationCameraTimer++;
            }

        } else {

        // We should not be animating now
        canvasAnimationCameraShift = false;

        }

    // Loop through all field layers on the canvas
    $('.background[data-animate],.foreground[data-animate]', gameCanvas).each(function(){
        // Trigger an animation frame change for this field
        var thisField = $(this);
        mmrpg_canvas_field_frame(thisField, '');
        });

    // Loop through all field sprites on the canvas
    $('.sprite[data-animate]', gameCanvas).each(function(){
        // Trigger an animation frame change for this field
        var thisSprite = $(this);
        var thisType = thisSprite.attr('data-type');
        // Call the animation function based on sprite type
        if (thisType == 'attachment'){
            if (thisSprite.attr('data-status') != 'disabled' || thisSprite.attr('data-direction') == 'right'){
                mmrpg_canvas_attachment_frame(thisSprite, '');
            } else {
            //alert('sprite is disabled');
            // Fade this sprite off-screen
            //thisSprite.animate({opacity:0},1000,'linear',function(){ $(this).remove(); });
            var spriteKind = thisSprite.attr('data-type');
            var spriteID = thisSprite.attr('data-'+spriteKind+'id');
            //alert('sprite kind is '+spriteKind+' and its ID is '+spriteID);
            var shadowSprite = $('.sprite[data-shadowid='+spriteID+']', gameCanvas);
            //var detailsSprite = $('.sprite[data-detailsid='+spriteID+']', gameCanvas);
            //var mugshotSprite = $('.sprite[data-mugshotid='+spriteID+']', gameCanvas);
            //alert('Shadowsprite '+(shadowSprite.length ? 'exists' : 'does not exist')+'!');
            if (gameSettings.eventTimeout > gameSettings.eventTimeoutThreshold){
                //console.log('normal animation');
                // We're at a normal speed, so we can animate normally
                thisSprite.stop(true, true).animate({opacity:0},Math.ceil(gameSettings.eventTimeout / 2),'linear',function(){
                    $(this).remove();
                    if (shadowSprite.length){ shadowSprite.stop(true, true).animate({opacity:0},Math.ceil(gameSettings.eventTimeout / 2),'linear',function(){ $(this).remove(); }); }
                    });
                } else {
                //console.log('speedy animation');
                // We're at a super-fast speed, so we should NOT cross-fade
                thisSprite.stop(true, true).remove();
                if (shadowSprite.length){ shadowSprite.stop(true, true).remove(); }
                }
            }
        }

        });


    // Loop through all players on the field
    $('.sprite[data-type="player"]', gameCanvas).each(function(){

        // Collect a reference to the current player
        var thisPlayer = $(this);
        // Generate a random number
        var thisRandom = Math.floor(Math.random() * 100);
        // Default the new frame to base
        var newFrame = 'base';
        var extraStyles = {};
        // Define the relative battle result
        var relativeResult = 'pending';
        if (battleStatus == 'complete'){
            relativeResult = thisPlayer.attr('data-direction') == 'right' ? (battleResult) : (battleResult == 'victory' ? 'defeat' : 'victory');
            }
        // If the there are no more events to display
        if (!mmrpgEvents.length){
            // If the player has been defeated, only show one frame, otherwise randomize
            if (relativeResult == 'defeat'){
                // Defeault to the defeat frame
                newFrame = 'defeat';
                } else {
                // Higher animation freqency if not active
                if (thisPlayer.attr('data-position') != 'active'){
                    if (battleStatus == 'complete' && thisRandom >= 50){
                        newFrame = relativeResult;
                        } else if (thisRandom >= 80){
                        newFrame = 'taunt';
                        } else if (thisRandom >= 60){
                        newFrame = 'base2';
                        }
                    } else {
                    if (battleStatus == 'complete' && thisRandom >= 50){
                        newFrame = relativeResult;
                        } else if (thisRandom >= 80){
                        newFrame = 'taunt';
                        } else if (thisRandom >= 60){
                        newFrame = 'base2';
                        }
                    }
                }
            // Check to see if we should be applying any extra styles
            if (newFrame !== 'base' && thisRandom % 11 === 0){ extraStyles = {transform: 'scaleX(-1) translateX(-10%)'}; }
            else if (thisRandom % 3 === 0){ extraStyles = {transform: ''}; }
            }

        // Trigger the player frame advancement
        //console.log('thisRandom:', thisRandom, 'newFrame:', newFrame, 'extraStyles:', extraStyles);
        mmrpg_canvas_player_frame(thisPlayer, newFrame, extraStyles);

        });


    // Loop through all robots on the field
    $('.sprite[data-type="robot"]', gameCanvas).each(function(){

        // Collect a reference to the current robot
        var thisRobot = $(this);
        // Ensure the robot has not been disabled
        if (thisRobot.attr('data-status') != 'disabled'){
            // Generate a random number
            var thisRandom = Math.floor(Math.random() * 100);
            // Default the new frame to base
            var newFrame = 'base';
            var currentFrame = thisRobot.attr('data-frame');
            // Define the relative battle result
            var relativeResult = 'pending';
            if (battleStatus == 'complete'){
                relativeResult = thisRobot.attr('data-direction') == 'right' ? (battleResult) : (battleResult == 'victory' ? 'defeat' : 'victory');
                }
            // If the there are no more events to display
            if (!mmrpgEvents.length){
                // If the player has been defeated, only show one frame, otherwise randomize
                if (relativeResult == 'defeat'){
                    // Defeault to the defeat frame
                    newFrame = 'defeat';
                    } else {
                    // Only change to an action frame if currently base
                    if (currentFrame == 'base'){
                        // Animation freqency based on position
                        if (thisRobot.attr('data-position') != 'active'){
                            // Higher animation freqency if not active (BENCH)
                            if (battleStatus == 'complete' && thisRandom >= 50){
                                newFrame = relativeResult;
                                } else if (thisRandom >= 80){
                                newFrame = 'base2';
                                } else if (thisRandom >= 50){
                                newFrame = 'taunt';
                                } else if (thisRandom >= 40){
                                newFrame = 'defend';
                                }
                            } else {
                            // Lower animation freqency if active (ACTIVE)
                            if (battleStatus == 'complete' && thisRandom >= 50){
                                newFrame = relativeResult;
                                } else if (thisRandom >= 90){
                                newFrame = 'base2';
                                } else if (thisRandom >= 30){
                                newFrame = 'defend';
                                } else if (thisRandom >= 20){
                                newFrame = 'taunt';
                                }
                            }
                        }
                    }
                }
            // Trigger the robot frame advancement
            mmrpg_canvas_robot_frame(thisRobot, newFrame);
            var spriteKind = thisRobot.attr('data-type');
            var spriteID = thisRobot.attr('data-'+spriteKind+'id');
            var shadowSprite = $('.sprite[data-shadowid='+spriteID+']', gameCanvas);
            if (shadowSprite.length){ mmrpg_canvas_robot_frame(shadowSprite, newFrame);  }

            }
            else if (thisRobot.attr('data-status') == 'disabled' && thisRobot.attr('data-direction') == 'right'){

            // Default the new frame to base
            //var newFrame = 'base';
            // Trigger the robot frame advancement
            //mmrpg_canvas_robot_frame(thisRobot, newFrame);

            }
            else {

            //alert('robot is disabled');
            // Fade this robot off-screen
            var spriteKind = thisRobot.attr('data-type');
            var spriteID = thisRobot.attr('data-'+spriteKind+'id');
            //alert('sprite kind is '+spriteKind+' and its ID is '+spriteID);
            var shadowSprite = $('.sprite[data-shadowid='+spriteID+']', gameCanvas);
            var detailsSprite = $('.sprite[data-detailsid='+spriteID+']', gameCanvas);
            var mugshotSprite = $('.sprite[data-mugshotid='+spriteID+']', gameCanvas);
            //alert('Shadowsprite '+(shadowSprite.length ? 'exists' : 'does not exist')+'!');
            thisRobot.stop(true, true).animate({opacity:0},1000,'linear',function(){
                $(this).remove();
                if (shadowSprite.length){ shadowSprite.stop(true, true).animate({opacity:0},1000,'linear',function(){ $(this).remove(); }); }
                if (detailsSprite.length){ detailsSprite.stop(true, true).animate({opacity:0},1000,'linear',function(){ $(this).remove(); }); }
                if (mugshotSprite.length){ mugshotSprite.stop(true, true).animate({opacity:0},1000,'linear',function(){ $(this).remove(); }); }
                });

            }

        });

    // Reset the timeout event for another animation round
    if (canvasAnimationTimeout != false){ window.clearTimeout(canvasAnimationTimeout); }
    if (!canvasAnimationTimeout.length){
        requestAnimationFrame(function(){
            canvasAnimationTimeout = window.setTimeout(function(){
                //console.log('mmrpg_canvas_animate');
                mmrpg_canvas_animate(); // DEBUG PAUSE
                }, gameSettings.eventTimeout);
            });
        }
    // Return true for good measure
    return true;
}

// Define a function for updating a fields's frame with animation
function mmrpg_canvas_field_frame(thisField, newFrame){
    // Generate a new frame if one was not provided
    if (newFrame == ''){
        // Collect a reference to the current field data
        var thisFieldFrame = thisField.attr('data-frame');
        var thisAnimateFrame = thisField.attr('data-animate').split(',');
        var thisAnimateFrameCount = thisAnimateFrame.length;
        // Default the new frame to base
        if (thisAnimateFrameCount > 1){
            var thisIndex = thisAnimateFrame.indexOf(thisFieldFrame);
            if ((thisIndex + 1) < thisAnimateFrameCount){
                var newFrame = thisAnimateFrame[thisIndex + 1];
                } else {
                var newFrame = thisAnimateFrame[0];
                }
        } else {
            var newFrame = thisAnimateFrame[0];
        }
    }
    // Collect this field's data fields (hehe)
    var thisFrame = thisField.attr('data-frame');
    // If the new frame is the same as the current, return
    if (thisFrame == newFrame || thisField.is(':animated')){ return false; }
    // Define the current class (based on data) and the new class
    var fieldLayer = thisField.hasClass('background') ? 'background' : 'foreground';
    var currentClass = fieldLayer+'_'+thisFrame;
    var newClass = fieldLayer+'_'+newFrame;
    // Check to make sure event crossfade is enabled
    if (gameSettings.eventCrossFade == true){
        // Create a clone object with the new class and crossfade it into view
        var cloneField = thisField.clone().css('z-index', '10').appendTo(thisField.parent());
        thisField.stop(true, true).css({opacity:0}).attr('data-frame', newFrame).removeClass(currentClass).addClass(newClass);
        thisField.stop(true, true).animate({opacity:1}, {duration:Math.ceil(gameSettings.eventTimeout * 0.5),easing:'swing',queue:false});
        cloneField.stop(true, true).animate({opacity:1}, {duration:Math.ceil(gameSettings.eventTimeout * 0.5),easing:'swing',queue:false,complete:function(){ $(this).remove(); }});
        } else {
        // Update the existing sprite's frame without crossfade by swapping the classsa
        thisField.attr('data-frame', newFrame).stop(true, true).removeClass(currentClass).addClass(newClass);
        }
    // Return true on success
    return true;
}

// Define the sprite frame index
var spriteFrameIndex = {};

// Define a function for updating a robot's frame with animation
spriteFrameIndex.robots = ['base','taunt','victory','defeat','shoot','throw','summon','slide','defend','damage','base2'];
function mmrpg_canvas_robot_frame(thisRobot, newFrame){
    // Collect this robot's data fields
    var thisSize = thisRobot.attr('data-size');
    var thisPosition = thisRobot.attr('data-position');
    var thisDirection = thisRobot.attr('data-direction');
    var thisStatus = thisRobot.attr('data-status');
    var thisFrame = thisRobot.attr('data-frame');
    var isShadow = thisRobot.attr('data-shadowid') != undefined ? true : false;
    var newFramePosition = spriteFrameIndex.robots.indexOf(newFrame) || 0;
    // If the new frame is the same as the current, return
    if (thisFrame == newFrame){ return false; }
    // If this robot is disabled, do not animate
    if (thisStatus == 'disabled'){
        //thisRobot.animate({opacity:0},1000,'swing',function(){ $(this).remove(); });
        return false;
        }
    // Define the current class (based on data) and the new class
    var currentClass = 'sprite_'+thisSize+'x'+thisSize+'_'+thisFrame;
    var newClass = 'sprite_'+thisSize+'x'+thisSize+'_'+newFrame;
    // Define the new background offset for the frame
    var backgroundOffset = -1 * Math.ceil(newFramePosition * thisSize);
    //alert('backgroundOffset = '+backgroundOffset);
    // Stop this robot from animating further
    thisRobot.stop(true, true);
    // Check to make sure event crossfade is enabled
    if (gameSettings.eventCrossFade == true){
        // Create a clone object with the new class and crossfade it into view
        var cloneRobot = thisRobot.clone().css('z-index', '-=1').appendTo(thisRobot.parent());
        thisRobot.stop(true, true).css({opacity:0,backgroundPosition:backgroundOffset+'px 0'}).attr('data-frame', newFrame).removeClass(currentClass).addClass(newClass);
        thisRobot.stop(true, true).animate({opacity:1}, {duration:400,easing:'swing',queue:false});
        cloneRobot.stop(true, true).animate({opacity:0}, {duration:400,easing:'swing',queue:false,complete:function(){ $(this).remove(); }});
        } else {
        // Update the existing sprite's frame without crossfade by swapping the classsa
        thisRobot.stop(true, true).css({backgroundPosition:backgroundOffset+'px 0'}).attr('data-frame', newFrame).removeClass(currentClass).addClass(newClass);
        }
    // Return true on success
    return true;
}
// DEBUG
//function mmrpg_canvas_robot_frame_

// Define a function for updating a player's frame with animation
spriteFrameIndex.players = ['base','taunt','victory','defeat','command','damage','base2'];
function mmrpg_canvas_player_frame(thisPlayer, newFrame, extraStyles){
    // Collect this player's data fields
    var thisSize = thisPlayer.attr('data-size');
    var thisPosition = thisPlayer.attr('data-position');
    var thisDirection = thisPlayer.attr('data-direction');
    var thisStatus = thisPlayer.attr('data-status');
    var thisFrame = thisPlayer.attr('data-frame');
    var newFramePosition = spriteFrameIndex.players.indexOf(newFrame) || 0;
    if (typeof extraStyles !== 'object' || !extraStyles){ extraStyles = false; }
    //if (true){ alert(newFrame+' : '+newFramePosition); }
    // If the new frame is the same as the current, return
    if (thisFrame == newFrame){ return false; }
    // If this player is disabled, do not animate
    if (thisStatus == 'disabled'){ return false; }
    // Define the current class (based on data) and the new class
    var currentClass = 'sprite_'+thisSize+'x'+thisSize+'_'+thisFrame;
    var newClass = 'sprite_'+thisSize+'x'+thisSize+'_'+newFrame;
    // Define the new background offset for the frame
    var backgroundOffset = -1 * Math.ceil(newFramePosition * thisSize);
    //if (backgroundOffset > 0){ alert('newFrame : '+newFrame+', newFramePosition : '+newFramePosition+', backgroundOffset : '+backgroundOffset+''); }
    // Check to make sure event crossfade is enabled
    if (gameSettings.eventCrossFade == true){
        // Create a clone object with the new class and crossfade it into view
        var clonePlayer = thisPlayer.clone().css('z-index', '-=1').appendTo(thisPlayer.parent());
        thisPlayer.stop(true, true).css({opacity:0,backgroundPosition:backgroundOffset+'px 0'}).attr('data-frame', newFrame).removeClass(currentClass).addClass(newClass);
        if (extraStyles){ thisPlayer.css(extraStyles); }
        thisPlayer.stop(true, true).animate({opacity:1}, {duration:400,easing:'swing',queue:false});
        clonePlayer.stop(true, true).animate({opacity:0}, {duration:400,easing:'swing',queue:false,complete:function(){ $(this).remove(); }});
        } else {
        // Update the existing sprite's frame without crossfade by swapping the classsa
        thisPlayer.stop(true, true).css({backgroundPosition:backgroundOffset+'px 0'}).attr('data-frame', newFrame).removeClass(currentClass).addClass(newClass);
        if (extraStyles){ thisPlayer.css(extraStyles); }
        }
    // Return true on success
    return true;
}

// Define a function for updating an attachment's frame with animation
spriteFrameIndex.attachments = ['00','01','02','03','04','05','06','07','08','09', '10'];
function mmrpg_canvas_attachment_frame(thisAttachment, newFrame){
    // If the newFrame or newIndex are empty
    if (newFrame === ''){
        // Collect a reference to the current attachment properties
        var thisAttachmentFloat = thisAttachment.attr('data-direction') == 'left' ? 'right' : 'left';
        var thisAttachmentFrame = thisAttachment.attr('data-frame');
        var thisAnimateFrame = thisAttachment.attr('data-animate').split(',');
        var thisAnimateFrameShift = thisAttachment.attr('data-animate-shift') != undefined ? thisAttachment.attr('data-animate-shift').split('|') : false;
        var thisAnimateFrameIndex = thisAttachment.attr('data-animate-index') != undefined ? parseInt(thisAttachment.attr('data-animate-index')) : 0;
        var thisAnimateFrameCount = thisAnimateFrame.length;
        // Default the new frame to base
        var newIndex = 0;
        var newFrame = thisAnimateFrame[newIndex];
        if (thisAnimateFrameCount > 1 && (thisAnimateFrameIndex + 1) < thisAnimateFrameCount){
            newIndex = thisAnimateFrameIndex + 1;
            newFrame = thisAnimateFrame[newIndex];
            }
        var newFrameShift = thisAnimateFrameShift.length ? thisAnimateFrameShift[newIndex] : thisAttachment.css(thisAttachmentFloat)+','+thisAttachment.css('bottom');
        newFrameShift = newFrameShift.split(',');
        var newFrameShiftX = newFrameShift[0]+'px';
        var newFrameShiftY = newFrameShift[1]+'px';
        }

    // DEBUG
    // If there was a frame shift defined
    if (false && thisAnimateFrameShift){
        // DEBUG
        //console.log('ID = '+thisAttachment.attr('data-id'));
        //console.log('newIndex = '+newIndex);
        //console.log('newFrame = '+newFrame);
        //console.log('newFrameShiftX = '+newFrameShiftX);
        //console.log('newFrameShiftY = '+newFrameShiftY);
        }


    // Collect this robot's data fields
    var thisSize = thisAttachment.attr('data-size');
    //var thisPosition = thisAttachment.attr('data-position');
    var thisDirection = thisAttachment.attr('data-direction');
    var thisFloat = thisDirection == 'left' ? 'right' : 'left';
    var thisFrame = thisAttachment.attr('data-frame');
    var thisPosition = thisAttachment.attr('data-position');
    var thisIndex = thisAttachment.attr('data-animate-index');
    //console.log('checkpoint1');
    // If the new frame is the same as the current, return
    if (thisFrame == newFrame && thisIndex == newIndex && !thisAnimateFrameShift){ return false; }
    // Define the new frame position in the index
    var newFramePosition = spriteFrameIndex.attachments.indexOf(newFrame) || 0;
    // Define the new background offset for the frame
    var backgroundOffset = -1 * Math.ceil(newFramePosition * thisSize);
    // Define the current class (based on data) and the new class
    var currentClass = 'sprite_'+thisSize+'x'+thisSize+'_'+thisFrame;
    var newClass = 'sprite_'+thisSize+'x'+thisSize+'_'+newFrame;
    //console.log('checkpoint2');
    // If the frame has changed, animate to the next image, otherwise just update properties
    if (thisFrame != newFrame || thisAnimateFrameShift){
        //console.log('checkpoint3');
        // Check to make sure event crossfade is enabled
        if ((thisPosition != 'background' && thisPosition != 'foreground') && gameSettings.eventCrossFade == true){
            // Create a clone object with the new class and crossfade it into view
            var cloneAttachment = thisAttachment.clone().css('z-index', '-=1').appendTo(thisAttachment.parent());
            thisAttachment.stop(true, true).css({opacity:0,backgroundPosition:backgroundOffset+'px 0'}).attr('data-frame', newFrame).attr('data-animate-index', newIndex).removeClass(currentClass).addClass(newClass);
            // If the frame's offsets have changed, update the css offsets
            if (thisAnimateFrameShift){ thisAttachment.stop(true, true).css(thisFloat, newFrameShiftX).css('bottom', newFrameShiftY); }
            // Fade this attachment back into view and fade the cloned attachment in the old frame out
            if (gameSettings.eventTimeout > gameSettings.eventTimeoutThreshold){
                // We're at a normal speed, so we can animate normally
                thisAttachment.stop(true, true).animate({opacity:1}, {duration:Math.ceil(gameSettings.eventTimeout / 2),easing:'swing',queue:false});
                cloneAttachment.stop(true, true).animate({opacity:0}, {duration:Math.ceil(gameSettings.eventTimeout / 2),easing:'swing',queue:false,complete:function(){ $(this).remove(); }});
                } else {
                // We're at a super-fast speed, so we should NOT cross-fade
                thisAttachment.stop(true, true).css({opacity:1});
                cloneAttachment.stop(true, true).remove();
                }
            } else {
            // If the frame's offsets have changed, update the css offsets
            if (thisAnimateFrameShift){ thisAttachment.stop(true, true).css(thisFloat, newFrameShiftX).css('bottom', newFrameShiftY); }
            // Update the existing sprite's frame without crossfade by swapping the classsa
            thisAttachment.stop(true, true).css({backgroundPosition:backgroundOffset+'px 0'}).attr('data-frame', newFrame).attr('data-animate-index', newIndex).removeClass(currentClass).addClass(newClass);
            }
    }  else {
        // If the frame's offsets have changed, update the css offsets
        if (thisAnimateFrameShift){ thisAttachment.stop(true, true).css(thisFloat, newFrameShiftX).css('bottom', newFrameShiftY); }
        // Simply update the parameters on this sprite frame
        thisAttachment.attr('data-animate-index', newIndex);
    }
    // Return true on success
    return true;
}

// Define a function for triggering an action submit
function mmrpg_action_trigger(thisAction, thisPreload, thisTarget, thisPanel){
    //console.log('thisAction : '+thisAction);
    // Return false if this is a continue click
    if (thisAction == 'continue'){ return false; }
    if (thisTarget == undefined){ thisTarget = 'auto'; }
    if (thisPanel == undefined){ thisPanel = 'battle'; }
    // Set the submitEngine flag to true by default
    var submitEngine = true;
    var nextPanel = false;

    // Switch to the loading screen
    mmrpg_action_panel('loading');

    // If the target was set to auto, pull the data from the engine
    if (thisTarget == 'auto'){
        //var autoTargetID = $('target_robot_id', gameEngine).val();
        //var autoTargetToken = $('target_robot_token', gameEngine).val();
        //thisTarget = autoTargetID+'_'+autoTargetToken;
        }

    // Parse any actions with subtokens in their string
    if (thisAction.match(/^ability_([-a-z0-9_]+)$/i)){

        // Parse the ability token and clean the main action token
        var thisAbility = thisAction.replace(/^ability_([-a-z0-9_]+)$/i, '$1');
        // If this ability's target is not set to auto
        if (thisTarget == 'select_this'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_this';
            } else if (thisTarget == 'select_this_disabled'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_this_disabled';
            } else if (thisTarget == 'select_this_ally'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_this_ally';
            } else if (thisTarget == 'select_target'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_target';
            }
        mmrpg_engine_update({this_action_token:thisAbility});
        thisAction = 'ability';

        }
    else if (thisAction.match(/^item_([-a-z0-9_]+)$/i)){

        // Parse the item token and clean the main action token
        var thisItem = thisAction.replace(/^item_([-a-z0-9_]+)$/i, '$1');
        // If this item's target is not set to auto
        if (thisTarget == 'select_this'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_this';
            } else if (thisTarget == 'select_this_disabled'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_this_disabled';
            } else if (thisTarget == 'select_this_ally'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_this_ally';
            } else if (thisTarget == 'select_target'){
            // Make sure the engine is not submit yet
            submitEngine = false;
            // Make sure the next panel is the target
            nextPanel = 'target_target';
            }
        mmrpg_engine_update({this_action_token:thisItem});
        thisAction = 'item';

        }
    else if (thisAction.match(/^switch_([-a-z0-9_]+)$/i)){

        // Parse the switch token and clean the main action token
        var thisSwitch = thisAction.replace(/^switch_([-a-z0-9_]+)$/i, '$1');
        mmrpg_engine_update({this_action_token:thisSwitch});
        thisAction = 'switch';

        }
    else if (thisAction.match(/^scan_([-a-z0-9_]+)$/i)){

        // Parse the scan token and clean the main action token
        var thisScan = thisAction.replace(/^scan_([-a-z0-9_]+)$/i, '$1');
        mmrpg_engine_update({this_action_token:thisScan});
        thisAction = 'scan';

        }
    else if (thisAction.match(/^target_([-a-z0-9_]+)$/i)){

        // Parse the target token and clean the main action token
        var thisTarget = thisAction.replace(/^target_([-a-z0-9_]+)$/i, '$1');
        //alert('thisTarget '+thisTarget);
        thisTarget = thisTarget.split('_');
        mmrpg_engine_update({target_robot_id:thisTarget[0]});
        mmrpg_engine_update({target_robot_token:thisTarget[1]});
        thisAction = '';

        }
    else if (thisAction.match(/^settings_([-a-z0-9]+)_([-a-z0-9_]+)$/i)){

        // Parse the settings token and value, then clean the action token
        var thisSettingToken = thisAction.replace(/^settings_([-a-z0-9]+)_([-a-z0-9_]+)$/i, '$1');
        var thisSettingValue = thisAction.replace(/^settings_([-a-z0-9]+)_([-a-z0-9_]+)$/i, '$2');
        if (thisSettingValue === 'true'){ thisSettingValue = true; }
        else if (thisSettingValue === 'false'){ thisSettingValue = false; }
        gameSettings[thisSettingToken] = thisSettingValue;
        var thisRequestType = 'session';
        var thisRequestData = 'battle_settings,'+thisSettingToken+','+thisSettingValue;
        $.post('scripts/script.php',{requestType: 'session',requestData: 'battle_settings,'+thisSettingToken+','+thisSettingValue});
        if (typeof gameSettingsChangeEvents[thisSettingToken] === 'function'){ gameSettingsChangeEvents[thisSettingToken](thisSettingValue); }

        // Make sure this setting button has the "active" class, remove any others
        var thisActionButton = $('a[data-action="'+thisAction+'"]', gameActions);
        var thisActionButtonWrapper = thisActionButton.closest('.main_actions');
        thisActionButtonWrapper.find('a[data-action]').removeClass('active');
        thisActionButton.addClass('active');

        thisAction = 'settings';
        nextAction = 'settings_'+thisSettingToken;
        if (nextAction.length){ mmrpg_action_panel(nextAction); }

        //var nextAction = $('input[name=next_action]', gameEngine).val();
        //if (nextAction.length){ mmrpg_action_panel(nextAction, thisPanel); }

        return true;

        }

    // Check if image preloading was requested
    if (thisPreload.length){
        // Preload the requested image
        var thisPreloadImage = $(document.createElement('img'))
            .attr('src', thisPreload)
            .load(function(){
                // Update the engine and trigger a submit event
                if (thisAction.length){ mmrpg_engine_update({this_action:thisAction}); }
                if (submitEngine == true){ gameEngine.submit(); }
                if (nextPanel != false){ mmrpg_action_panel(nextPanel, thisPanel); }
                return true;
                });
        } else {
            // Update the engine and trigger a submit event
            if (thisAction.length){ mmrpg_engine_update({this_action:thisAction}); }
            if (submitEngine == true){ gameEngine.submit(); }
            if (nextPanel != false){ mmrpg_action_panel(nextPanel, thisPanel); }
            return true;
            }
}

// Define a function for preloading assets
var asset_sprite_cache = [];
var asset_sprite_images = [
    'images/assets/battle-scene_robot-details.gif',
    'images/assets/battle-scene_robot-results.gif',
    'images/abilities/_effects/stat-arrows/sprite_left_80x80.png',
    'images/abilities/_effects/stat-arrows/sprite_right_80x80.png'
    ];
function mmrpg_preload_assets(){
    // Loop through each of the asset images
    for (key in asset_sprite_images){
        // Define the sprite path value
        var sprite_path = asset_sprite_images[key];
        // Cache this image in the appropriate array
        var cacheImage = document.createElement('img');
        cacheImage.src = sprite_path;
        asset_sprite_cache.push(cacheImage);
    }
}

// Define a function for preloading field sprites
var field_sprite_cache = {};
var field_sprite_frames = ['base'];
var field_sprite_kinds = ['background', 'foreground'];
var field_sprite_type = 'png';
function mmrpg_preload_field_sprites(fieldKind, fieldToken){
    // If this sprite has not already been cached
    if (!field_sprite_cache[fieldToken]){
        //alert('creating sprite cache for '+fieldToken);
        // Define the container for this robot's cache
        field_sprite_cache[fieldToken] = [];
        // Define the sprite path and counter values
        var sprite_path = 'images/fields/'+fieldToken+'/';
        var num_frames = field_sprite_frames.length;
        var num_kinds = field_sprite_kinds.length;
        // Loop through all the sizes and frames
        for (var i = 0; i < num_frames; i++){
            // Collect the current frame, size, and filename
            var this_frame = field_sprite_frames[i];
            var this_kind = fieldKind;
            var file_name = 'battle-field_'+this_kind+'_'+this_frame+'.'+field_sprite_type;
            // Cache this image in the apporiate array
            var cacheImage = document.createElement('img');
            cacheImage.src = sprite_path+file_name;
            field_sprite_cache[fieldToken].push(cacheImage);
            //alert(field_path+file_name);
        }
    }
    //alert('sprite cache '+field_sprite_cache[fieldToken].length);
}

// Define a function for preloading robot sprites
var robotSpriteCache = {};
var robotSpriteTypes = ['mug', 'sprite'];
var robotSpriteExtension = 'png';
function mmrpg_preload_robot_sprites(thisRobotToken, thisRobotDirection, thisRobotSize){
    // If this sprite has not already been cached
    if (thisRobotToken == false || thisRobotToken == 0 || thisRobotToken == ''){ return false; }
    var thisCacheToken = thisRobotToken+'_'+thisRobotDirection+'_'+thisRobotSize;
    if (!robotSpriteCache[thisCacheToken]){
        //console.log('creating sprite cache for '+thisRobotToken);
        // Define the container for this robot's cache
        robotSpriteCache[thisCacheToken] = [];
        // Define the sprite path and counter values
        var robotSpritePath = 'images/robots/'+thisRobotToken+'/';
        var numRobotTypes = robotSpriteTypes.length;
        // Loop through all the sizes and frames
        for (var i = 0; i < numRobotTypes; i++){
            // Collect the current frame, size, and filename
            var thisSpriteType = robotSpriteTypes[i];
            var thisSpriteToken = thisSpriteType+'_'+thisRobotDirection+'_'+thisRobotSize+'x'+thisRobotSize;
            var thisSpriteFilename = thisSpriteToken+'.'+robotSpriteExtension;
            // Cache this image in the apporiate array
            var thisCacheImage = document.createElement('img');
            thisCacheImage.src = robotSpritePath+thisSpriteFilename;
            robotSpriteCache[thisCacheToken].push(thisCacheImage);
            //alert('thisCacheImage.src = '+robotSpritePath+thisSpriteFilename+';');
            //alert(robotSpritePath+thisSpriteFilename);
        }
    }
    //alert('sprite cache '+sprite_cache[thisRobotToken].length);
}

// Define a function for updating the engine form
function mmrpg_engine_update(newValues){
    if (gameEngine.length){
        // Loop through the game engine values and update them
        for (var thisName in newValues){
            var thisValue = newValues[thisName];
            // Update the value in the global settings object
            gameSettings.currentGameState[thisName] = thisValue;
            // And then also update it in the DOM for form submission
            if ($('input[name='+thisName+']', gameEngine).length){
                $('input[name='+thisName+']', gameEngine).val(thisValue);
                } else {
                gameEngine.append('<input type="hidden" class="hidden" name="'+thisName+'" value="'+thisValue+'" />');
                }
            }
        }
}

// Define a function for switching to a different action panel
function mmrpg_action_panel(thisPanel, currentPanel){

    // Update the current panel in the game settings for reference
    gameSettings.currentActionPanel = thisPanel;

    // Switch to the event actions panel
    $('.wrapper', gameActions).css({display:'none'});
    var newWrapper = $('#actions_'+thisPanel, gameActions);
    if (currentPanel != undefined){
        newWrapper.find('.action_back').attr('data-panel', currentPanel);
        var newWrapperTitle = newWrapper.find('.main_actions_title');
        if (newWrapperTitle.length){ newWrapperTitle.html(newWrapperTitle.html().replace('{thisPanel}', currentPanel)); }
        //alert('thisPanel = '+thisPanel+'; currentPanel = '+currentPanel);
        }

    // Unhide the new wrapper
    newWrapper.css({display:''});

    // If the new action panel has numbered links in the title
    var mainActionsTitle = $('.main_actions_title', newWrapper);
    var floatLinkContainer = $('.float_links', mainActionsTitle);
    if (floatLinkContainer.length){

        // Collect the parent wraper ID and generate the option class name
        var parentActionsWrapper = floatLinkContainer.closest('#actions > .wrapper');
        var actionWrapperID = parentActionsWrapper.attr('id');
        var optionClass = actionWrapperID.replace(/^actions_/, 'action_');

        // Assign events to any of the page links here
        $('.num[href]', floatLinkContainer).click(function(e){
            e.preventDefault();

            // Collect references to this link and number
            var thisLink = $(this);
            var thisNum = parseInt(thisLink.attr('href').replace(/^#/, ''));

            // If this this panel is disabled, prevent clicking but only the first link
            //if (thisNum > 1 && mainActionsTitle.hasClass('main_actions_title_disabled')){ return false; }
            //console.log('num link '+thisNum+' clicked!');

            // Remove the active class from other links and add to this one
            $('.num', floatLinkContainer).removeClass('active');
            thisLink.addClass('active');

            // Define the key of the first and last element to be shown
            var lastElementKey = thisNum * 8;
            var firstElementKey = lastElementKey - 8;
            //console.log('first key should be '+firstElementKey+' and last should be '+lastElementKey+'!');

            // Hide all item buttons in the current view and then show only relevant
            $('.'+optionClass, newWrapper).css({display:'none'});
            var activeButtons = $('.'+optionClass, newWrapper).slice(firstElementKey, lastElementKey);
            //console.log('we have selected a total of '+activeButtons.length+' elements');
            activeButtons.css({display:'block'});

            // Loop through the active buttons and update their order values
            var tempOrder = 1;
            activeButtons.each(function(){ $(this).attr('data-order', tempOrder); tempOrder++; });
            $('.action_back', newWrapper).attr('data-order', tempOrder);

            // Update the session with the last page click
            //var thisRequestType = 'session';
            //var thisRequestData = 'battle_settings,'+optionClass+'_page_num,'+thisNum;
            //$.post('scripts/script.php',{requestType:thisRequestType,requestData:thisRequestData});
            //(disabled for now)

            // Return true on success
            return true;

            });

        var activeLink = $('.active', floatLinkContainer);
        var firstLink = $('.num', floatLinkContainer).first();
        if (activeLink.length){ activeLink.trigger('click'); }
        else if (firstLink.length){ firstLink.trigger('click'); }

        }

    // If there are buttons in the new wrapper
    var hoverButton = $('.button_hover', newWrapper);
    var currentButtons = $('.button:not(.button_disabled)', newWrapper);
    var currentButtonCount = currentButtons.length;
    if (currentButtonCount > 0 && !hoverButton.length){
        var firstButton = currentButtons.first();
        var firstButtonOrder = firstButton.attr('data-order') != undefined ? parseInt(firstButton.attr('data-order')) : 0;
        if (firstButton.length){ firstButton.addClass('button_hover'); }
    }

}

// Define an extension of the string prototype to handle replace all
String.prototype.replaceAll = function(search, replace) {
        if (replace === undefined) { return this.toString(); }
        return this.replace(new RegExp(search, 'g'), replace);
        //return this.split(search).join(replace);
}

// Define a function for updating an action panel's markup
var actionPanelCache = [];
function mmrpg_action_panel_update(thisPanel, thisMarkup){
    // Update the requested panel with the supplied markup
    //console.log('mmrpg_action_panel_update('+thisPanel+', [thisMarkup])');
    var thisActionPanel = $('#actions_'+thisPanel, gameActions);
    thisActionPanel.empty().html(thisMarkup);
    // Search for any sprites in this panel's markup
    $('.sprite', thisActionPanel).each(function(){
        var thisBackground = $(this).css('background-image').replace(/^url\("?(.*?)"?\)$/i, '$1');
        if (thisBackground != 'none'){
            var cacheImage = document.createElement('img');
            cacheImage.src = thisBackground;
            actionPanelCache.push(cacheImage)
            }
        });
}

// Define a global variable for holding events
var mmrpgEvents = [];
// Define a function for queueing up an event
function mmrpg_event(flagsMarkup, dataMarkup, canvasMarkup, consoleMarkup){
    if (flagsMarkup.length){ flagsMarkup = $.parseJSON(flagsMarkup); }
    else { flagsMarkup = {}; }
    if (dataMarkup.length){ dataMarkup = $.parseJSON(dataMarkup); }
    else { dataMarkup = {}; }
    mmrpgEvents.push({
        'event_functions' : function(eventFlags){
            if (dataMarkup.length){
                //dataMarkup = $.parseJSON(dataMarkup);
                /*
                mmrpg_canvas_update(
                    dataMarkup.this_battle,
                    dataMarkup.this_field,
                    dataMarkup.this_player,
                    dataMarkup.this_robot,
                    dataMarkup.target_player,
                    dataMarkup.target_robot
                    );
                */
                }
            if (canvasMarkup.length){
                mmrpg_canvas_event(canvasMarkup, eventFlags); //, flagsMarkup
                }
            if (consoleMarkup.length){
                mmrpg_console_event(consoleMarkup, eventFlags);  //, flagsMarkup
                }
            },
        'event_flags' : flagsMarkup //$.parseJSON(flagsMarkup)
            });
    //console.log('mmrpgEvents.push() w/ new size', mmrpgEvents.length);
}
// Define a function for playing the events
var eventAlreadyQueued = false;
function mmrpg_events(){

    if (eventAlreadyQueued){ return; }

    //console.log('mmrpg_events()');
    //clearTimeout(canvasAnimationTimeout);
    clearInterval(canvasAnimationTimeout);
    canvasAnimationCameraTimer = 0;
    updateCameraShiftTransitionTiming();
    updateCameraShiftTransitionDuration();

    var thisEvent = false;
    if (mmrpgEvents.length){
        //console.log('mmrpgEvents.length =', mmrpgEvents.length);
        // Switch to the events panel
        mmrpg_action_panel('event');
        // Collect the topmost event and execute it
        thisEvent = mmrpgEvents.shift();
        thisEvent.event_functions(thisEvent.event_flags);
        // Loop through eventhooks functions if there are any in gameSettings.eventHooks and process them with this event
        if (gameSettings.eventHooks.length){
            $.each(gameSettings.eventHooks, function(){
                var thisEventHook = this;
                if (typeof thisEventHook == 'function'){ thisEventHook(thisEvent.event_flags); }
                });
            }
        }

    if (mmrpgEvents.length < 1){
        // Switch to the specified "next" action
        var nextAction = $('input[name=next_action]', gameEngine).val();
        if (nextAction.length){ mmrpg_action_panel(nextAction); }
        // Add the idle class to the robot details on-screen
        //console.log('adding robot details class....1');
        //$('.robot_details', gameCanvas).css('opacity', 0.9).addClass('robot_details_idle');
        // Start animating the canvas randomly
        mmrpg_canvas_animate();
        } else if (mmrpgEvents.length >= 1){
            var autoClickTimer = false;
            if (gameSettings.eventAutoPlay && thisEvent.event_flags.autoplay != false){
                //console.log('queue next event');
                eventAlreadyQueued = true;
                clearTimeout(autoClickTimer);
                autoClickTimer = setTimeout(function(){
                    requestAnimationFrame(function(){
                        //console.log('fire next event');
                        eventAlreadyQueued = false;
                        mmrpg_events();
                        });
                    }, parseInt(gameSettings.eventTimeout));
                $('a[data-action="continue"]').addClass('button_disabled');
                } else {
                $('a[data-action="continue"]').removeClass('button_disabled');
                }
            $('a[data-action="continue"]').click(function(){
                if (autoClickTimer !== false){
                    clearTimeout(autoClickTimer);
                    autoClickTimer = false;
                    }
                });
        }

    // Collect the current battle status and result
    var battleStatus = $('input[name=this_battle_status]', gameEngine).val();
    var battleResult = $('input[name=this_battle_result]', gameEngine).val();

    // Check for specific value triggers and execute events
    if (battleStatus == 'complete'){
        //console.log('checkpoint | battleStatus='+battleStatus+' battleResult='+battleResult);

        // Define the post-results music sequence that's common for both results
        var onCompleteCooldown = function(){
            //console.log('onCompleteCooldown()');
            /* ... */
            };

        // Based on the battle result, play the victory or defeat music
        if (battleResult == 'victory' && thisEvent.event_flags.victory != undefined && thisEvent.event_flags.victory != false){
            // Play the victory music
            //console.log('mmrpg_events() / Play the victory music');
            parent.mmrpg_music_load('misc/leader-board', true, false);
            setTimeout(function(){ parent.mmrpg_music_volume(0); }, 600);
            setTimeout(function(){ parent.mmrpg_fanfare_load('misc/battle-victory', true, true, true, onCompleteCooldown); }, 900);
            if (mmrpgEvents.length < canvasAnimationCameraDelay){ canvasAnimationCameraTimer = canvasAnimationCameraDelay - mmrpgEvents.length; }
            } else if (battleResult == 'defeat' && thisEvent.event_flags.defeat != undefined && thisEvent.event_flags.defeat != false){
            // Play the failure music
            //console.log('mmrpg_events() / Play the failure music');
            parent.mmrpg_music_load('misc/leader-board', true, false);
            setTimeout(function(){ parent.mmrpg_music_volume(0); }, 600);
            setTimeout(function(){ parent.mmrpg_fanfare_load('misc/battle-defeat', true, true, true, onCompleteCooldown); }, 900);
            if (mmrpgEvents.length < canvasAnimationCameraDelay){ canvasAnimationCameraTimer = canvasAnimationCameraDelay - mmrpgEvents.length; }
            }

        }


}

// Define a function for creating a new layer on the canvas
function mmrpg_canvas_event(thisMarkup, eventFlags){ //, flagsMarkup
    var thisContext = $('.wrapper', gameCanvas);
    if (thisContext.length){
        //console.log('mmrpg_canvas_event(thisMarkup, eventFlags) | eventFlags =', eventFlags);
        //console.log('gameSettings.eventTimeout =', gameSettings.eventTimeout, 'gameSettings.eventTimeoutThreshold =', gameSettings.eventTimeoutThreshold);
        // Drop all the z-indexes to a single amount
        $('.event:not(.sticky)', thisContext).css({zIndex:500});
        // Calculate the top offset based on previous event height
        var eventTop = $('.event:not(.sticky):first-child', thisContext).outerHeight();
        // Prepend the event to the current stack but bring it to the front
        var thisEvent = $('<div class="event event_frame clearback">'+thisMarkup+'</div>');
        thisEvent.css({opacity:0.0,zIndex:600});
        thisContext.prepend(thisEvent);

        // Wait for all the event's assets to finish loading
        thisEvent.waitForImages(function(){

            // Find all the details in this event markup and move them to the sticky
            $(this).find('.details').addClass('hidden').css({opacity:0}).appendTo('.event_details', gameCanvas);

            // If camera shift settings are enabled, we can process them
            if (gameSettings.eventCameraShift){
                // If this event has any camera action going on, make sure we update the canvas
                var currentShift = thisContext.attr('data-camera-shift') || '';
                var currentFocus = thisContext.attr('data-camera-focus') || '';
                var currentDepth = thisContext.attr('data-camera-depth') || '';
                var currentOffset = thisContext.attr('data-camera-offset') || '';
                var newCameraShift = '';
                var newCameraFocus = '';
                var newCameraDepth = 0;
                var newCameraOffset = 0;
                // Check to see if camera shift settings were provided in the frame
                if (gameSettings.eventCameraShift
                    && typeof eventFlags.camera !== 'undefined'
                    && eventFlags.camera !== false){
                    //console.log('we have camera action!', eventFlags.camera);
                    newCameraShift = eventFlags.camera.side;
                    newCameraFocus = eventFlags.camera.focus;
                    newCameraDepth = eventFlags.camera.depth;
                    newCameraOffset = eventFlags.camera.offset;
                }
                // If any of the shift values have changed, we need to update everything
                if (currentShift !== newCameraShift
                    || currentFocus !== newCameraFocus
                    || currentDepth !== newCameraDepth
                    || newCameraOffset !== newCameraOffset){
                    mmrpg_canvas_camera_shift(newCameraShift, newCameraFocus, newCameraDepth, newCameraOffset);
                }
            }

            // If we're allowed to cross-fade transition the normal way, otherwise straight-up replace the event
            if (gameSettings.eventCrossFade === true){

                // Animate a fade out of the other events
                if (gameSettings.eventTimeout > gameSettings.eventTimeoutThreshold){
                    // We're at a normal speed, so we can animate normally
                    $('.event:not(.sticky):gt(0)', thisContext).animate({opacity:0},{
                        duration: Math.ceil(gameSettings.eventTimeout / 2),
                        easing: 'linear',
                        queue: false
                        });
                    } else {
                    // We're at a super-fast speed, so we should NOT cross-fade
                    $('.event:not(.sticky):gt(0)', thisContext).css({opacity:0});
                    }

                // Animate a fade in, and the remove the old images
                if (gameSettings.eventTimeout > gameSettings.eventTimeoutThreshold){
                    // We're at a normal speed, so we can animate normally
                    $(this).animate({opacity:1.0}, {
                        duration: Math.ceil(gameSettings.eventTimeout / 2),
                        easing: 'linear',
                        complete: function(){
                            $('.details:not(.hidden)', thisContext).remove();
                            $('.details', thisContext).css({opacity:1}).removeClass('hidden');
                            $('.event:not(.sticky):gt(0)', thisContext).remove();
                            $(this).css({zIndex:500});
                            },
                        queue: false
                        });
                    } else {
                    // We're at a super-fast speed, so we should NOT cross-fade
                    $(this).css({opacity:1.0});
                    $('.details:not(.hidden)', thisContext).remove();
                    $('.details', thisContext).css({opacity:1}).removeClass('hidden');
                    $('.event:not(.sticky):gt(0)', thisContext).remove();
                    $(this).css({zIndex:500});
                    }

            }
            else {

                    // Make sure the new event is visible then remove the old ones
                    $(this).css({opacity:1.0,zIndex:500});
                    $('.event:not(.sticky):gt(0)', thisContext).css({opacity:0});
                    $('.details:not(.hidden)', thisContext).remove();
                    $('.details', thisContext).css({opacity:1}).removeClass('hidden');
                    $('.event:not(.sticky):gt(0)', thisContext).remove();

            }

            // Loop through all field layers on the canvas and trigger animations
            $('.background[data-animate],.foreground[data-animate]', gameCanvas).each(function(){
                // Trigger an animation frame change for this field
                var thisField = $(this);
                mmrpg_canvas_field_frame(thisField, '');
                });

            // Loop through all field layers on the canvas and trigger animations
            $('.sprite[data-type=attachment][data-animate]', gameCanvas).each(function(){
                // Trigger an animation frame change for this field
                var thisAttachment = $(this);
                var thisPosition = thisAttachment.attr('data-position');
                if (thisPosition == 'background' || thisPosition == 'foreground'){
                    //console.log('mmrpg_canvas_attachment_frame('+thisAttachment.attr('data-id')+')');
                    mmrpg_canvas_attachment_frame(thisAttachment, '');
                    }
                });

            });
        }
}


// Define a function for updating the graphics on the canvas
function mmrpg_canvas_update(thisBattle, thisPlayer, thisRobot, targetPlayer, targetRobot){
    // Preload all this robot's sprite image files if not already
    if (thisPlayer.player_side && thisRobot.robot_token){
        var thisRobotToken = thisRobot.robot_token;
        var thisRobotSide = thisPlayer.player_side == 'right' ? 'left' : 'right';
        mmrpg_preload_robot_sprites(thisRobotToken, thisRobotSide);
        }
    // Preload all the target robot's sprite image files if not already
    if (targetPlayer.player_side && targetRobot.robot_token){
        var targetRobotToken = targetRobot.robot_token;
        var targetRobotSide = targetPlayer.player_side == 'right' ? 'left' : 'right';
        mmrpg_preload_robot_sprites(targetRobotToken, targetRobotSide);
        }
}


// Define a change event for whenever this game setting is altered
gameSettings.currentCameraShift = {shift:'',focus:'',depth:'',offset:''};
function mmrpg_canvas_camera_shift(newCameraShift, newCameraFocus, newCameraDepth, newCameraOffset){
    //console.log('mmrpg_canvas_camera_shift() w/ ', newCameraShift, newCameraFocus, newCameraDepth, newCameraOffset);

    if (typeof newCameraShift === 'undefined' || !newCameraShift){ newCameraShift = ''; }
    if (typeof newCameraFocus === 'undefined' || !newCameraFocus){ newCameraFocus = ''; }
    if (typeof newCameraDepth === 'undefined' || !newCameraDepth){ newCameraDepth = 0; }
    if (typeof newCameraOffset === 'undefined' || !newCameraOffset){ newCameraOffset = 0; }
    //console.log('mmrpg_canvas_camera_shift() w/ ', newCameraShift, newCameraFocus, newCameraDepth, newCameraOffset);

    // Collect the canvas context and immediately return false if not exists
    var thisContext = $('.wrapper', gameCanvas);
    if (!thisContext.length){ return false; }

    // Collect current shift values for reference and updating
    var currentCameraShift = gameSettings.currentCameraShift;
    //console.log('currentCameraShift:', currentCameraShift);

    // If the values haven't changed at all, we should just return
    if (currentCameraShift.shift === newCameraShift
        && currentCameraShift.focus === newCameraFocus
        && currentCameraShift.depth === newCameraDepth
        && currentCameraShift.offset === newCameraOffset){
        return;
    }

    // Update the data attributes on the canvas wrapper
    currentCameraShift.shift = newCameraShift;
    currentCameraShift.focus = newCameraFocus;
    currentCameraShift.depth = newCameraDepth;
    currentCameraShift.offset = newCameraOffset;
    thisContext.attr('data-camera-shift', newCameraShift);
    thisContext.attr('data-camera-focus', newCameraFocus);
    thisContext.attr('data-camera-depth', newCameraDepth);
    thisContext.attr('data-camera-offset', newCameraOffset);
    var offsetCameraDepth = newCameraDepth + newCameraOffset;
    if (offsetCameraDepth < -8){ offsetCameraDepth - -8; }
    else if (offsetCameraDepth > 8){ offsetCameraDepth = 8; }

    // This first value is used for camera shifts on the bench
    if (offsetCameraDepth !== 0){
        var diffValue = ((Math.abs(offsetCameraDepth) - 1) * 0.1);
        var depthModValue = 1 - diffValue;
        if (offsetCameraDepth < 0){ depthModValue = depthModValue * -1; }
        updateCameraShiftVariable('depth-mod', depthModValue);
    } else {
        updateCameraShiftVariable('depth-mod', 1);
    }

    // This second value is used for camera shifts in the foreground
    if (offsetCameraDepth !== 0){
        var diffValue = ((Math.abs(offsetCameraDepth) - 1) * 0.1);
        var depthMod2Value = 1.8 - diffValue;
        if (offsetCameraDepth < 0){ depthMod2Value = depthMod2Value * -1; }
        updateCameraShiftVariable('depth-mod2', depthMod2Value);
    } else {
        updateCameraShiftVariable('depth-mod2', 1.8);
    }

}

// Define a function for easily updating camera-related CSS variables on the canvas
function updateCameraShiftVariable(varName, varValue){
    //console.log('updateCameraShiftVariable((varName:', varName, ', varValue:', varValue, ')');
    var cssVarName = '--camera-shift-'+varName;
    var cssVarValue = varValue;
    //console.log('setting '+cssVarName+' to:', cssVarValue);
    document.documentElement.style.setProperty(cssVarName, cssVarValue);
}

// Define a quick function for updating the camera shift transition timing variable
function updateCameraShiftTransitionTiming(newValue){
    //console.log('updateCameraShiftTransitionTiming(', newValue, ')');
    if (typeof newValue !== 'string' || !newValue){ newValue = 'ease'; }
    var transitionTimingValue = newValue;
    updateCameraShiftVariable('transition-timing', transitionTimingValue);
};

// Define a quick function for updating the camera shift transition duration variable
function updateCameraShiftTransitionDuration(newValue){
    //console.log('updateCameraShiftTransitionDuration(', typeof newValue, newValue, ')');
    if (typeof newValue !== 'number'){ newValue = 0.5; }
    var transitionDurationValue = (function(modValue){
        //console.log('transitionDurationValue(', modValue, ')');
        if (typeof modValue !== 'number'){ modValue = 1; }
        var duration = Math.ceil(gameSettings.eventTimeout * modValue);
        if (!gameSettings.eventCrossFade){ duration = 0; }
        else if (!gameSettings.eventCameraShift){ duration = 0; }
        else if (gameSettings.eventTimeout <= gameSettings.eventTimeoutThreshold){ duration = 0; }
        var cssValue = duration > 0 ? (duration / 1000)+'s' : 'none';
        //console.log('duration:', duration, 'cssValue:', cssValue);
        return cssValue;
        })(newValue);
    updateCameraShiftVariable('transition-duration', transitionDurationValue);
};

// Define a function for appending a event to the console window
function mmrpg_console_event(thisMarkup, eventFlags){ //, flagsMarkup
    var thisContext = $('.wrapper', gameConsole);
    if (thisContext.length){
        //console.log('mmrpg_console_event(thisMarkup, eventFlags) | eventFlags =', eventFlags);
        // Append the event to the current stack
        //thisContext.prepend('<div class="event" style="top: -100px;">'+thisMarkup+'</div>');
        thisContext.prepend(thisMarkup);
        gameConsole.find('.wrapper').scrollTop(0);
        $('.event:first-child', thisContext).css({top:-100});
            if (gameSettings.eventTimeout > gameSettings.eventTimeoutThreshold){
                // We're at a normal speed, so we can animate normally
                $('.event:first-child', thisContext).animate({top:0}, 400, 'swing');
                $('.event:eq(1)', thisContext).animate({opacity:0.90}, 100, 'swing');
                $('.event:eq(2)', thisContext).animate({opacity:0.80}, 100, 'swing');
                $('.event:eq(3)', thisContext).animate({opacity:0.70}, 100, 'swing');
                $('.event:eq(4)', thisContext).animate({opacity:0.65}, 100, 'swing');
                $('.event:eq(5)', thisContext).animate({opacity:0.60}, 100, 'swing');
                $('.event:eq(6)', thisContext).animate({opacity:0.55}, 100, 'swing');
                $('.event:eq(7)', thisContext).animate({opacity:0.50}, 100, 'swing');
                $('.event:eq(8)', thisContext).animate({opacity:0.45}, 100, 'swing');
                $('.event:gt(9)', thisContext).animate({opacity:0.40}, 100, 'swing');
                } else {
                // We're at a super-fast speed, so we should NOT cross-fade
                $('.event:first-child', thisContext).css({top:0});
                $('.event:eq(1)', thisContext).css({opacity:0.90});
                $('.event:eq(2)', thisContext).css({opacity:0.80});
                $('.event:eq(3)', thisContext).css({opacity:0.70});
                $('.event:eq(4)', thisContext).css({opacity:0.65});
                $('.event:eq(5)', thisContext).css({opacity:0.60});
                $('.event:eq(6)', thisContext).css({opacity:0.55});
                $('.event:eq(7)', thisContext).css({opacity:0.50});
                $('.event:eq(8)', thisContext).css({opacity:0.45});
                $('.event:gt(9)', thisContext).css({opacity:0.40});
                }
        // Hide any leftover boxes from previous events over the limit
        $('.event:gt(50)', thisContext).appendTo('#event_console_backup');
        // Remove any leftover boxes from previous events
        //$('.event:gt(10)', thisContext).remove();
        }
}



// Define a function for toggling the canvas animation
function mmrpg_toggle_animation(){
    if (gameSettings.idleAnimation != false){ return mmrpg_stop_animation(); }
    else { return mmrpg_start_animation(); }
}

// Define a function for starting the canvas animation
function mmrpg_start_animation(){
    var animateToggle = $('a.toggle', gameAnimate);
    animateToggle.removeClass('paused').addClass('playing');
    gameSettings.idleAnimation = true;
    gameSettings.eventAutoPlay = true;
    mmrpg_canvas_animate();
    if (mmrpgEvents.length){ mmrpg_events(); }
    return gameSettings.idleAnimation;
}

// Define a function for stopping the canvas animation
function mmrpg_stop_animation(){
    var animateToggle = $('a.toggle', gameAnimate);
    animateToggle.removeClass('playing').addClass('paused');
    gameSettings.idleAnimation = false;
    gameSettings.eventAutoPlay = false;
    mmrpg_canvas_animate();
    return gameSettings.idleAnimation;
}

// Define a Howl object to handle audio playback and set up some defaults
var mmrpgMusicSound = false;
var mmrpgFanfareSound = false;
var mmrpgMusicEndedDefault = function(){ /* ... */ };
var mmrpgFanfareEndedDefault = function(){ /* ... */ };
var mmrpgMusicEnded = mmrpgMusicEndedDefault;
var mmrpgFanfareEnded = mmrpgFanfareEndedDefault;
var mmrpgMusicInit = false;

// Define a function for toggling the music player
function mmrpg_music_toggle(){
    //console.log('mmrpg_music_toggle()');
    var musicToggle = $('a.toggle', gameMusic);
    if (!mmrpgMusicSound.playing()){
        mmrpg_music_volume(1);
        mmrpgMusicSound.play();
        musicToggle.html('&#9658;');
        musicToggle.removeClass('paused').addClass('playing');
    } else {
        mmrpg_music_volume(0);
        mmrpgMusicSound.pause();
        musicToggle.html('&#8226;');
        musicToggle.removeClass('playing').addClass('paused');
    }
    if (!mmrpgMusicInit){
        mmrpgMusicSound.on('end', mmrpgMusicEnded);
        mmrpgMusicInit = true;
    }
}

// Define a function for playing the current music
function mmrpg_music_play(){
    var musicToggle = $('a.toggle', gameMusic);
    var musicStream = $('.audio-stream.music', gameMusic);
    var musicStreamSource = $('source', musicStream).attr('src');
    if (!mmrpgMusicSound.playing()){
        mmrpg_music_volume(1);
        mmrpgMusicSound.play();
        musicToggle.html('&#9658;');
        musicToggle.removeClass('paused').addClass('playing');
    }
}

// Define a function for stopping the current music
function mmrpg_music_stop(){
    //console.log('mmrpg_music_stop()');
    //console.log('gameSettings.indexLoaded =', gameSettings.indexLoaded);
    //console.log('mmrpgMusicSound =', typeof mmrpgMusicSound, mmrpgMusicSound);
    var musicToggle = $('a.toggle', gameMusic);
    if (mmrpgMusicSound && mmrpgMusicSound.playing()){
        //console.log('updating the sound and toggle');
        mmrpg_music_volume(0);
        mmrpgMusicSound.stop();
        musicToggle.find('span').html('PLAY');
        musicToggle.removeClass('playing').addClass('paused');
    }
}
// Define a function for stopping the current music
function mmrpg_music_onend(onendFunction){
    var musicToggle = $('a.toggle', gameMusic);
    var musicStream = $('.audio-stream.music', gameMusic);
    if (mmrpgMusicSound && mmrpgMusicSound.playing()){
        return onendFunction(musicToggle, musicStream);
    }
}
// Define a function for playing the current music
function mmrpg_music_load(newTrack, resartTrack, playOnce, onendFunction){
    //console.log('mmrpg_music_load()', newTrack, resartTrack, playOnce);
    var musicStream = $('.audio-stream.music', gameMusic);
    var musicToggle = $('a.toggle', gameMusic);
    var thisTrack = musicStream.attr('data-track');
    var isPaused = !mmrpgMusicSound || !mmrpgMusicSound.playing();
    var isRestart = typeof resartTrack === 'boolean' ? resartTrack : true;
    var isPlayOnce = typeof playOnce === 'boolean' ? playOnce : false;
    var onplayFunction = function(){ musicToggle.removeClass('paused').addClass('playing'); };
    var onendFunction = typeof onendFunction === 'function' ? onendFunction : mmrpgFanfareEndedDefault;
    if (newTrack == 'last-track'){
        var lastTrack = musicStream.attr('data-last-track');
        if (lastTrack.length){ newTrack = lastTrack; }
        }
    if (isRestart == false && newTrack == thisTrack){
        return false;
        }
    var waitTime = mmrpgMusicSound && mmrpgMusicSound.playing() ? 500 : 0;
    mmrpg_music_volume(0);
    var loadTimeout = setTimeout(function(){
        mmrpg_music_stop();
        musicStream.attr('data-track', newTrack);
        musicStream.attr('data-last-track', thisTrack);
        // Create a new Howl object and load the new track
        mmrpgMusicSound = new Howl({
            src: [gameSettings.audioBaseHref+'sounds/'+newTrack+'/audio.mp3?'+gameSettings.cacheTime,
                  gameSettings.audioBaseHref+'sounds/'+newTrack+'/audio.ogg?'+gameSettings.cacheTime],
            autoplay: !isPaused,
            volume: gameSettings.baseVolume,
            loop: isPlayOnce ? false : true,
            onplay: onplayFunction,
            onend: onendFunction
            });
        }, waitTime);
}
// Define a function for adjusting the currently playing music's volume
function mmrpg_music_volume(newVolume, isRelative, fadeDuration){
    if (!mmrpgMusicSound){ return false; }
    //console.log('mmrpg_music_volume(newVolume:', newVolume, 'isRelative:', isRelative, ')');
    if (typeof isRelative !== 'boolean'){ isRelative = true; }
    if (typeof fadeDuration !== 'number'){ fadeDuration = 500; }
    var currentVolume = mmrpgMusicSound !== false ? mmrpgMusicSound.volume() : gameSettings.baseVolume;
    var newVolume = isRelative ? (gameSettings.baseVolume * newVolume) : newVolume;
    //console.log('currentVolume =', currentVolume);
    //console.log('newVolume =', newVolume);
    if (fadeDuration > 0){ mmrpgMusicSound.fade(currentVolume, newVolume, fadeDuration);  }
    else { mmrpgMusicSound.volume(newVolume); }
}

// Define a function for playing a specific fanfare track
function mmrpg_fanfare_load(newTrack, resartTrack, playOnce, fadeMusic, onendFunction){
    //console.log('mmrpg_fanfare_load(', newTrack, resartTrack, playOnce, ')');
    var fanfareStream = $('.audio-stream.fanfare', gameMusic);
    //console.log('fanfareStream =', fanfareStream.length, fanfareStream);
    var thisTrack = fanfareStream.attr('data-track');
    var isRestart = typeof resartTrack === 'boolean' ? resartTrack : true;
    var isPlayOnce = typeof playOnce === 'boolean' ? playOnce : true;
    var fadeMusic = typeof fadeMusic === 'boolean' ? fadeMusic : true;
    var onendFunction = typeof onendFunction === 'function' ? onendFunction : mmrpgFanfareEndedDefault;
    if (newTrack == 'last-track'){
        var lastTrack = fanfareStream.attr('data-last-track');
        if (lastTrack.length){ newTrack = lastTrack; }
        }
    if (isRestart == false && newTrack == thisTrack){
        return false;
        }
    if (mmrpgFanfareSound !== false
        && mmrpgFanfareSound.playing()){
        mmrpgFanfareSound.stop();
        }
    fanfareStream.attr('data-track', newTrack);
    fanfareStream.attr('data-last-track', thisTrack);
    // Create a new Howl object and load the new track
    if (fadeMusic){ mmrpg_music_volume(0.1); }
    mmrpgFanfareSound = new Howl({
        src: [gameSettings.audioBaseHref+'sounds/'+newTrack+'/audio.mp3?'+gameSettings.cacheTime,
              gameSettings.audioBaseHref+'sounds/'+newTrack+'/audio.ogg?'+gameSettings.cacheTime],
        autoplay: true,
        volume: gameSettings.baseVolume,
        loop: isPlayOnce ? false : true,
        onend: function(){
            onendFunction();
            if (fadeMusic){ mmrpg_music_volume(1); }
            }
    });
}

// Define a function for preloading music files
var musicCache = [];
var cacheList = [];
function mmrpg_music_preload(newTrack){
    // Ensure the new track is not alrady in the list
    if (cacheList.indexOf(newTrack) === -1){
        // Define the two audio objects based on the track
        var newAudioMP3 = '<audio src="'+gameSettings.audioBaseHref+'sounds/'+newTrack+'/audio.mp3?'+gameSettings.cacheTime+'" preload></audio>';
        var newAudioOGG = '<audio src="'+gameSettings.audioBaseHref+'sounds/'+newTrack+'/audio.ogg?'+gameSettings.cacheTime+'" preload></audio>';
        cacheList.push(newTrack);
        if (isIE || isOpera || isSafari){ musicCache.push($(newAudioMP3));  }
        else if (isChrome || isFirefox){ musicCache.push($(newAudioOGG)); }
        else { musicCache.push($(newAudioMP3)); }
        return true;
        } else {
        // Does not need to be preloaded
        return false;
        }
}

// Define a function for displaying event messages to the player
gameSettings.canvasMarkupArray = [];
gameSettings.messagesMarkupArray = [];
function windowEventCreate(canvasMarkupArray, messagesMarkupArray){
    //console.log('windowEventCreate('+canvasMarkupArray+', '+messagesMarkupArray+')');
    gameSettings.canvasMarkupArray = canvasMarkupArray;
    gameSettings.messagesMarkupArray = messagesMarkupArray;
    windowEventDisplay();
}

// Define a function for displaying event messages to the player
function windowEventDisplay(){
    //console.log('windowEventDisplay()');

    // Check if the event container exists and, if not, create it
    var $eventContainer = $('#events');
    if (!$eventContainer.length){

        // Define the markup for the event window dynamically
        $eventContainer = $(
            '<div id="events" class="hidden">'+
                '<div class="event_wrapper">'+
                    '<div class="event_container">'+
                        '<div id="canvas" class="event_canvas"></div>'+
                        '<div id="messages" class="event_messages"></div>'+
                        '<div id="buttons" class="event_buttons"><a class="event_continue">Continue</a></div>'+
                    '</div>'+
                '</div>'+
            '</div>'
            );

        // Detect which parent window is available and then append the window to it
        var $eventContainerParent = false;
        if ($('#window').length){ $eventContainerParent = $('#window').first(); }
        else if ($('#prototype').length){ $eventContainerParent = $('#prototype').first(); }
        else if ($('#battle').length){ $eventContainerParent = $('#battle').first(); }
        $eventContainerParent.append($eventContainer);

        // Define a click event for the event window continue button
        var eventContinue = $('#buttons .event_continue', $eventContainer);
        eventContinue.click(function(e){
            e.preventDefault();
            //alert('clicked');
            windowEventDestroy();
            if (gameSettings.canvasMarkupArray.length || gameSettings.messagesMarkupArray.length){
                windowEventDisplay();
                }
            });

        }

    // Collect the canvas and message markup to be added to the event container
    var canvasMarkup = gameSettings.canvasMarkupArray.length ? gameSettings.canvasMarkupArray.shift() : '';
    var messagesMarkup = gameSettings.messagesMarkupArray.length ? gameSettings.messagesMarkupArray.shift() : '';
    //console.log('canvasMarkup:', canvasMarkup, 'messagesMarkup:', messagesMarkup);

    // Empty the canvas and messages of any leftover, fill them with new markup, then show 'em
    $('#canvas', $eventContainer).empty().html(canvasMarkup);
    $('#messages', $eventContainer).empty().html(messagesMarkup);
    $eventContainer.css({opacity:0}).removeClass('hidden').animate({opacity:1},300,'swing');
    $('#messages', $eventContainer).perfectScrollbar(thisScrollbarSettings);
    $(window).focus();

}

// Define a function for displaying event messages to the player
function windowEventDestroy(){
    var $eventContainer = $('#events');
    //console.log('windowEventDestroy()');
    $('#canvas', $eventContainer).empty();
    $('#messages', $eventContainer).empty();
    $eventContainer.addClass('hidden');
    //alert(eventMarkup);
}

// Define a function for updating the loaded status of the main index page
function mmrpg_toggle_index_loaded(toggleValue){
    //console.log('game loaded!');
    if (toggleValue == true && gameSettings.indexLoaded != true){
        //console.log('unfade the splash loader');
        // Fade out the splash loader text, change it to PLAY, then flade it in
        $('a.toggle span', gameMusic).css({opacity:1}).animate({opacity:0}, 1000, 'swing', function(){
            $('a.toggle span', gameMusic).html('<div class="start"><div class="title">START</div><div class="subtitle">MEGA MAN RPG PROTOTYPE</div><div class="info">(Toggle music with &nbsp;&nbsp;)<div class="icon">&nbsp;</div></div></div>').animate({opacity:1}, 1000, 'swing', function(){
                // Remove the loading class from the iframe and fade it into view
                //$('iframe', gameWindow).css({opacity:0}).removeClass('loading').animate({opacity:1}, 1000, 'swing'); // DEBUG
                // Set the toggle loader flag to true
                gameSettings.indexLoaded = true;
                });
            });
        }
}

// Define a function for updating the loaded status of the main index page
function mmrpg_toggle_debug_mode(element){
    // Collect the object references to the button and internal label
    var thisButton = $(element);
    var thisLabel = $('.multi', thisButton);
    // Pull the current value and use it to calculate new ones
    var thisValue = parseInt(thisButton.attr('data-value'));
    var newValue = thisValue != 1 ? 1 : 0;
    var newValueText = newValue == 1 ? 'ON' : 'OFF';
    var newValueClass = 'value type type_';
    newValueClass += newValue == 1 ? 'nature' : 'flame';
    //console.log('Toggle the debug mode!', {thisValue:thisValue,newValue:newValue,newValueText:newValueText,newValueClass:newValueClass});
    // Update the button value and label text/colour
    thisButton.attr('data-value', newValue);
    thisLabel.find('.value').html(newValueText).removeClass().addClass(newValueClass);
    // Send the new value to the server to update the session
    var thisRequestType = 'session';
    var thisRequestData = 'debug_mode,'+newValue;
    $.post('scripts/script.php',{requestType:thisRequestType,requestData:thisRequestData});
    return true;
}

// Define a function for updating the loaded status of the main index page
function mmrpg_toggle_settings_option(element){
    //console.log('mmrpg_toggle_settings_option()');

    // Collect the object references to the button and internal label
    var thisButton = $(element);
    var thisLabel = $('.multi', thisButton);

    // Parse the settings token and value, then clean the action token
    var thisSettingToken = thisButton.attr('data-setting-token');
    var thisSettingValue = parseInt(thisButton.attr('data-setting-value'));
    if (thisSettingValue === 1){ thisSettingValue = true; }
    else if (thisSettingValue === 0){ thisSettingValue = false; }
    //console.log('thisSettingToken =', thisSettingToken);
    //console.log('thisSettingValue =', thisSettingValue);

    // Pull the current value and use it to calculate new ones
    var newSettingValue = !thisSettingValue ? true : false;
    var newSettingValueText = newSettingValue ? 'ON' : 'OFF';
    var newSettingValueClass = 'value type type_';
    newSettingValueClass += (newSettingValue ? 'nature' : 'flame');
    //console.log('newSettingValue =', newSettingValue);

    // Update the local setting in case we need to work with it again
    gameSettings[thisSettingToken] = newSettingValue;
    //console.log('gameSettings[' + thisSettingToken + '] = ' + newSettingValue + ';');

    // Update the button value and label text/colour
    thisButton.attr('data-setting-value', (newSettingValue ? 1 : 0));
    thisLabel.find('.value').html(newSettingValueText).removeClass().addClass(newSettingValueClass);
    var thisRequestType = 'session';
    var thisRequestData = 'battle_settings,'+thisSettingToken+','+(newSettingValue ? 'true' : 'false');
    //console.log('thisRequestData =', thisRequestData);
    $.post('scripts/script.php',{requestType: thisRequestType, requestData: thisRequestData});
    if (typeof gameSettingsChangeEvents[thisSettingToken] === 'function'){ gameSettingsChangeEvents[thisSettingToken](newSettingValue); }

    return true;
}

// Define a function for updating the loaded status of the main index page
var windowTogglePerspectiveTimeout = false;
function mmrpg_toggle_perspective_mode(element){
    // Collect the object references to the button and internal label
    var thisButton = $(element);
    var thisLabel = $('.multi', thisButton);
    // Pull the current value and use it to calculate new ones
    var thisValue = parseInt(thisButton.attr('data-value'));
    var newValue = thisValue != 1 ? 1 : 0;
    var newValueText = newValue == 1 ? 'ON' : 'OFF';
    var newValueClass = 'value type type_';
    newValueClass += newValue == 1 ? 'nature' : 'flame';
    //console.log('Toggle the perspective mode!', {thisValue:thisValue,newValue:newValue,newValueText:newValueText,newValueClass:newValueClass});
    // Update the button value and label text/colour
    thisButton.attr('data-value', newValue);
    thisLabel.find('.value').html(newValueText).removeClass().addClass(newValueClass);
    // Send the new value to the server to update the session
    var thisRequestType = 'session';
    var thisRequestData = 'perspective_mode,'+newValue;
    if (windowTogglePerspectiveTimeout !== false){ clearTimeout(windowTogglePerspectiveTimeout); }
    windowTogglePerspectiveTimeout = setTimeout(function(){
        $.post('scripts/script.php',{requestType:thisRequestType,requestData:thisRequestData});
        }, 1000);
    return true;
}


// Define a function for keeping the session alive and auto-redirecting when it's over
function mmrpg_keep_session_alive(sessionUserID){
    //console.log('mmrpg_keep_session_alive()');

    var keepSessionAlive = true;
    var thisSessionUserID = sessionUserID;
    var sessionPingFrequency = 1000 * 60 * 5; // every 5 mins
    var sessionPingURL = gameSettings.baseHref + 'scripts/ping.php';
    var loginPageURL = gameSettings.baseHref + 'file/load/';

    // Define a function that "pings" the server to keep login status alive
    var extendGameSession = function(){
        //console.log('extendGameSession()');
        if (!keepSessionAlive){ return false; }
        $.post(sessionPingURL, function(data){
            //console.log('data =', data);
            if (typeof data.status !== 'undefined'
                && data.status === 'success'
                && data.user_id === thisSessionUserID){
                keepSessionAlive = true;
                } else {
                keepSessionAlive = false;
                redirectToLogin();
                }
            //console.log('keepSessionAlive =', keepSessionAlive);
            });
        };

    // Define a function that redirects to the login frame when logged out
    var redirectToLogin = function(){
        //console.log('redirectToLogin()');
        var confirmRedirect = confirm('MMRPG SESSION ERROR! \n'
            + 'Your session has expired or you logged out in another frame! \n'
            + 'The game cannot function in this state and must be restarted. \n'
            + 'You will now be redirected to the login page... '
            );
        if (!confirmRedirect){ return; }
        if (window.self !== window.parent){
            window.parent.location.href = loginPageURL;
            } else {
            window.location.href = loginPageURL;
            }
        };

    // Start the extend session interval to keep pinging every X minutes
    var extendSessionInterval = setInterval(function(){
        if (keepSessionAlive){ extendGameSession(); }
        else { clearInterval(extendSessionInterval); }
        }, sessionPingFrequency);

}


/**
 * Function : dump()
 * Arguments: The data - array,hash(associative array),object
 *    The level - OPTIONAL
 * Returns  : The textual representation of the array.
 * This function was inspired by the print_r function of PHP.
 * This will accept some data as the argument and return a
 * text that will be a more readable version of the
 * array/hash/object that is given.
 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
 */
function dump(arr,level) {
    var dumped_text = "";
    if(!level) level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for(var j=0;j<level+1;j++) level_padding += "    ";

    if(typeof(arr) == 'object') { //Array/Hashes/Objects
        for(var item in arr) {
            var value = arr[item];

            if(typeof(value) == 'object') { //If it is an array,
                dumped_text += level_padding + "'" + item + "' ...\n";
                dumped_text += dump(value,level+1);
            } else {
                dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
            }
        }
    } else { //Stings/Chars/Numbers etc.
        dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
    }
    return dumped_text;
}

// Define a jQuery function for preloading images
(function($){
    var cache = [];
    // Arguments are image paths relative to the current page.
    $.preLoadImages = function(){
        var args_len = arguments.length;
        for (var i = args_len; i--;){
            var cacheImage = document.createElement('img');
            cacheImage.src = arguments[i];
            cache.push(cacheImage);
        }
    }
})(jQuery)

// Define a jQuery function for waiting for images
;(function($) {

        // Namespace all events.
        var eventNamespace = 'waitForImages';

        // CSS properties which contain references to images.
        $.waitForImages = {
            hasImageProperties: [
            'backgroundImage',
            'listStyleImage',
            'borderImage',
            'borderCornerImage'
            ]
        };

        // Custom selector to find `img` elements that have a valid `src` attribute and have not already loaded.
        $.expr[':'].uncached = function(obj) {
            // Ensure we are dealing with an `img` element with a valid `src` attribute.
            if ( ! $(obj).is('img[src!=""]')) {
                return false;
            }
            // Firefox's `complete` property will always be`true` even if the image has not been downloaded.
            // Doing it this way works in Firefox.
            var img = document.createElement('img');
            img.src = obj.src;
            return ! img.complete;
        };

        $.fn.waitForImages = function(finishedCallback, eachCallback, waitForAll) {
            // Handle options object.
            if ($.isPlainObject(arguments[0])) {
                eachCallback = finishedCallback.each;
                waitForAll = finishedCallback.waitForAll;
                finishedCallback = finishedCallback.finished;
            }
            // Handle missing callbacks.
            finishedCallback = finishedCallback || $.noop;
            eachCallback = eachCallback || $.noop;
            // Convert waitForAll to Boolean
            waitForAll = !! waitForAll;
            // Ensure callbacks are functions.
            if (!$.isFunction(finishedCallback) || !$.isFunction(eachCallback)) {
                throw new TypeError('An invalid callback was supplied.');
            };
            return this.each(function() {
                // Build a list of all imgs, dependent on what images will be considered.
                var obj = $(this);
                var allImgs = [];
                var processedImages = new Set();
                if (waitForAll){
                    // CSS properties which may contain an image.
                    var hasImgProperties = $.waitForImages.hasImageProperties || [];
                    var matchUrl = /url\((['"]?)(.*?)\1\)/g;
                    // Get all elements, as any one of them could have a background image.
                    obj.find('*').each(function(){
                        var element = $(this);
                        // If an `img` element, add it. But keep iterating in case it has a background image too.
                        if (element.is('img:uncached')
                            && !processedImages.has(element.attr('src'))){
                            allImgs.push({
                                src: element.attr('src'),
                                element: element[0]
                                });
                            processedImages.add(element.attr('src'));
                        }
                        $.each(hasImgProperties, function(i, property){
                            var propertyValue = element.css(property);
                            // If it doesn't contain this property, skip.
                            if (!propertyValue){
                                return true;
                                }
                            // Get all url() of this element.
                            var match;
                            while (match = matchUrl.exec(propertyValue)){
                                if (!processedImages.has(match[2])){
                                    allImgs.push({
                                        src: match[2],
                                        element: element[0]
                                        });
                                    processedImages.add(match[2]);
                                    }
                                };
                            });
                        });
                } else {
                    // For images only, the task is simpler.
                    obj.find('img:uncached').each(function(){
                            allImgs.push({
                                src: this.src,
                                element: this
                                });
                            });
                };
                var allImgsLength = allImgs.length;
                var allImgsLoaded = 0;
                // If no images found, don't bother.
                if (allImgsLength == 0){
                    finishedCallback.call(obj[0]);
                    };
                //console.log('allImgs =', allImgs, allImgs.length);
                $.each(allImgs, function(i, img) {
                    var image = new Image;
                    var loadedOrErrored = false;  // Add this line
                    // Update the callback
                    $(image).bind('load.' + eventNamespace + ' error.' + eventNamespace, function(event) {
                        // Only increment if this is the first event for this image
                        if (!loadedOrErrored) {
                            loadedOrErrored = true;
                            allImgsLoaded++;
                            // If an error occurred with loading the image, set the third argument accordingly.
                            eachCallback.call(img.element, allImgsLoaded, allImgsLength, event.type == 'load');
                            if (allImgsLoaded == allImgsLength) {
                                finishedCallback.call(obj[0]);
                                return false;
                            };
                        }
                    });
                    image.src = img.src;
                });
            });
        };

})(jQuery);

// Fix the indexOf issue for IE8 and lower
if (!Array.prototype.indexOf) {
        Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
                "use strict";
                if (this === void 0 || this === null) {
                        throw new TypeError();
                }
                var t = Object(this);
                var len = t.length >>> 0;
                if (len === 0) {
                        return -1;
                }
                var n = 0;
                if (arguments.length > 0) {
                        n = Number(arguments[1]);
                        if (n !== n) { // shortcut for verifying if it's NaN
                                n = 0;
                        } else if (n !== 0 && n !== Infinity && n !== -Infinity) {
                                n = (n > 0 || -1) * Math.floor(Math.abs(n));
                        }
                }
                if (n >= len) {
                        return -1;
                }
                var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
                for (; k < len; k++) {
                        if (k in t && t[k] === searchElement) {
                                return k;
                        }
                }
                return -1;
        }
}

// Polyfill for requestAnimationFrame if not exists
window.requestAnimationFrame = window.requestAnimationFrame
|| window.mozRequestAnimationFrame
|| window.webkitRequestAnimationFrame
|| window.msRequestAnimationFrame
|| function(f){return setTimeout(f, 1000/60)};
window.cancelAnimationFrame = window.cancelAnimationFrame
    || window.mozCancelAnimationFrame
    || function(requestID){clearTimeout(requestID)};
