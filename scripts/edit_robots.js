// Generate the document ready events for this page
var thisBody = false;
var thisPrototype = false;
var thisWindow = false;
var thisRobotCanvas = false;
var thisPlayerCanvas = false;
var thisAbilityCanvas = false;
var thisItemCanvas = false;
var thisEditor = false;
var thisEditorData = {playerTotal:0,robotTotal:0};
var thisScrollbarSettings = {
    wheelSpeed:0.3,
    useBothWheelAxes: false,
    suppressScrollX: true
    };
var resizePlayerWrapper = function(){};
var loadConsoleRobotMarkup = function(thisSprite, index, complete){};
var loadCanvasAbilitiesMarkup = function(){};
var loadCanvasItemsMarkup = function(){};
var showRobotInReadyRoom = function(){};
var loadedConsoleRobotTokens = [];
gameSettings.shareProgramUnlocked = false;
gameSettings.transferProgramUnlocked = false;
gameSettings.searchProgramUnlocked = false;
$(document).ready(function(){

    // Update global reference variables
    thisBody = $('#mmrpg');
    thisPrototype = $('#prototype', thisBody);
    thisWindow = $(window);
    thisEditor = $('#edit', thisBody);
    thisConsole = $('#console', thisEditor);
    thisCanvas = $('#canvas', thisEditor);
    thisRobotCanvas = $('div[data-canvas=robots]', gameCanvas);
    thisPlayerCanvas = $('div[data-canvas=players]', gameCanvas);
    thisAbilityCanvas = $('div[data-canvas=abilities]', gameCanvas);
    thisItemCanvas = $('div[data-canvas=items]', gameCanvas);

    // Automatically hide the console and canvas areas until we're ready
    thisConsole.css({height:0,minHeight:0});

    //console.log( thisEditorData);

    // -- SOUND EFFECT FUNCTIONALITY -- //

    // Define some interaction sound effects for the items menu
    var thisContext = $('#edit');
    var playSoundEffect = function(){};
    if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){

        // Define a quick local function for routing sound effect plays to the parent
        playSoundEffect = function(soundName, options){
            if (this instanceof jQuery || this instanceof Element){
                if ($(this).data('silentClick')){ return; }
                if ($(this).is('.disabled')){ return; }
                if ($(this).is('.button_disabled')){ return; }
                }
            top.mmrpg_play_sound_effect(soundName, options);
            };

        // ROBOT AVATAR LINKS

        // Add hover and click sounds to the robot avatar in the selection drawer
        $('#canvas .wrapper .sprite_robot', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#canvas .wrapper .sprite_robot', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // ROBOT PANEL BUTTONS

        // Add hover and click sounds to the robot favourite (pin) button in the editor panel
        $('#console .event .robot_favourite', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event .robot_favourite', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // Add hover and click sounds to the robot alt image sprite in the editor panel
        $('#console .event .event_robot_images a.robot_image_alts', thisContext).live('mouseenter', function(){
            if ($(this).is('[data-alt-index="base"]')){ return; }
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event .event_robot_images a.robot_image_alts', thisContext).live('click', function(){
            if ($(this).is('[data-alt-index="base"]')){ return; }
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            setTimeout(function(){ playSoundEffect.call(this, 'link-click-action', {volume: 1.0}); }, 800);
            });

        //#console .event .event_robot_images .sprite_wrapper .sprite

        // EQUIP ABILITY LINKS & SUBLINKS

        // Add hover and click sounds to the ability buttons in the editor
        $('#console .event a.ability_name', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event a.ability_name', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // Add hover and click sounds to the ability buttons in the selection drawer
        $('#canvas .ability_canvas div.links a', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#canvas .ability_canvas div.links a', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // EQUIP ITEM LINKS

        // Add hover and click sounds to the item buttons in the editor
        $('#console .event a.item_name', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event a.item_name', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // Add hover and click sounds to the item buttons in the selection drawer
        $('#canvas .item_canvas div.links a', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#canvas .item_canvas div.links a', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // CHANGE PLAYER LINKS

        // Add hover and click sounds to the player buttons in the editor
        $('#console .event a.player_name', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event a.player_name', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        // Add hover and click sounds to the player buttons in the selection drawer
        $('#canvas .player_canvas div.links a', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#canvas .player_canvas div.links a', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click-mini', {volume: 1.0});
            });

        }

    // -- RESIZE WRAPPER FUNCTIONS -- //

    // Define a function for resizing player wrappers based on robot count
    resizePlayerWrapper = function(){
        $('.robot_canvas .wrapper[data-player]', thisCanvas).each(function(){
            var tempPlayerWrapper = $(this);
            var tempPlayerCell = tempPlayerWrapper.parent();
            var tempPlayerToken = tempPlayerWrapper.attr('data-player');
            var tempRobotCount = $('.sprite[data-robot]', tempPlayerWrapper).length;
            var tempRobotPercent = parseFloat(Math.round(((tempRobotCount / thisEditorData.robotTotal) * 100) * 100) / 100).toFixed(2);
            //tempPlayerCell.css({width:tempRobotPercent+'%'});
            tempPlayerCell.css({width:''});
            if (tempRobotCount < 2){ tempPlayerWrapper.find('.sort_wrapper').css({display:'none'}); }
            else { tempPlayerWrapper.find('.sort_wrapper').css({display:''}); }
            //console.log( thisEditorData);
            //console.log( tempPlayerToken+' has '+tempRobotCount+' robots which is '+tempRobotPercent+'% of the total');
            tempPlayerWrapper.find('.wrapper_header .count').html(tempRobotCount);
            });
        };


    // -- MARKUP LOADING FUNCTIONS -- //

    // Define the batch function for loading robot data in a loop
    loadConsoleRobotMarkup = function(thisSprite, index, complete){
        //console.log('loadConsoleRobotMarkup() for '+thisSprite.attr('data-player')+' '+thisSprite.attr('data-robot'));

        thisBody.addClass('loading');

        var thisPlayerToken = thisSprite.attr('data-player');
        var thisRobotToken = thisSprite.attr('data-robot');
        if (index == undefined){ index = 0; }
        if (complete == undefined){ complete = function(){}; }
        //console.log('sending request for console data of '+thisPlayerToken+' '+thisRobotToken);

        $.post('frames/edit_robots.php', {
            action: 'console_markup',
            wap: gameSettings.wapFlag,
            edit: gameSettings.allowEditing ? 'true' : 'false',
            user_id: gameSettings.userNumber,
            player: thisPlayerToken,
            robot: thisRobotToken
            }, function(data, status){
            //console.log('console data received, appending '+thisPlayerToken+' '+thisRobotToken+'...');

            var firstLoad = false;
            var $placeHolderBlock = gameConsole.parent().find('.placeholder');
            if ($placeHolderBlock.length){
                var marginTop = (-1 * $placeHolderBlock.outerHeight()) + 'px';
                $placeHolderBlock.animate({opacity:0,marginTop:marginTop},600,'swing',function(){ $(this).remove(); });
                firstLoad = true;
                }

            if (index == 0){
                thisConsole.animate({height:'230px',opacity:1},300,'swing',function(){
                    //console.log('console animation complete');
                    $(this).css({height:'auto'});
                    });
                }

            $('#console #robots').append(data);

            var fadeDuration = firstLoad ? 600 : 300;
            thisSprite.animate({opacity:0.3},{duration:fadeDuration,queue:false,easing:'swing',complete:function(){
                //console.log( thisPlayerToken+' '+thisRobotToken+' sprite animation complete');
                $(this).removeClass('notloaded').css({opacity:''});
                $(this).find('i.new').remove();
                thisBody.removeClass('loading');
                complete();
                }});

            countRobotsLoaded++;
            loadedConsoleRobotTokens.push(thisRobotToken);

            });

        };

    // Define a function for showing/highlighting a robot in the above ready room when possible
    showRobotInReadyRoom = function(playerToken, robotToken){
        //console.log('showRobotInReadyRoom(playerToken:', playerToken, 'robotToken:', robotToken, ')');
        // We should add the new robot to the parent ready room
        if (typeof window.parent.mmrpgReadyRoom !== 'undefined'
            && typeof window.parent.mmrpgReadyRoom.updateRobot !== 'undefined'){
            // If the extra data in dataExtra was not empty and is JSON, parse it into robotInfo
            var readyRoom = window.parent.mmrpgReadyRoom;
            var spriteBounces = readyRoom.config.spriteBounds;
            readyRoom.updatePlayer('all', {frame: 'base', position: [null, (spriteBounces.maxY - 2)]});
            readyRoom.updateRobot('all', {frame: 'base', position: [null, '>=20']});
            readyRoom.updatePlayer(playerToken, {frame: 'victory', direction: 'right', position: [44, (spriteBounces.minY - 2)]});
            readyRoom.updateRobot(robotToken, {frame: 'victory', direction: 'left', position: [56, (spriteBounces.minY - 2)]});
            }
        };

    // Define the batch function for loading all unlocked player data
    loadCanvasPlayersMarkup = function(onComplete){

        //console.log('calling the loadCanvasPlayersMarkup() function');

        if (onComplete == undefined){
            onComplete = function(){
                var thisContainer = $(this);
                thisContainer.attr('data-player', '');
                thisContainer.attr('data-key', '');
                };
            }

        // If the links have not been loaded, do so now
        if ($('#canvas .player_canvas .links').is(':empty')){
            $.post('frames/edit_robots.php', {
                action: 'canvas_players_markup',
                wap: gameSettings.wapFlag,
                edit: gameSettings.allowEditing ? 'true' : 'false',
                user_id: gameSettings.userNumber
                }, function(data, status){
                //console.log('console player data received, appending...');
                //console.log( data);
                // Append the link markup to the canvas
                $('#canvas .player_canvas .links').empty().append(data);
                // Trigger scrollbars on any overflow containers
                $('#canvas .player_canvas .wrapper_overflow', thisEditor).perfectScrollbar(thisScrollbarSettings);
                // Trigger the on complete function
                onComplete.call($('#canvas .player_canvas'));
                });
            } else {
                // Fix scrollbars on any overflow containers
                $('#canvas .player_canvas .wrapper_overflow', thisEditor).scrollTop(0).perfectScrollbar('update');
                // Trigger the on complete function
                onComplete.call($('#canvas .player_canvas'));
            }

        };

    // Define the batch function for loading all unlocked ability data
    loadCanvasAbilitiesMarkup = function(onComplete){

        //console.log('calling the loadCanvasAbilitiesMarkup() function');

        if (onComplete == undefined){
            onComplete = function(){
                var thisContainer = $(this);
                thisContainer.attr('data-player', '');
                thisContainer.attr('data-robot', '');
                thisContainer.attr('data-key', '');
                };
            }

        // If the links have not been loaded, do so now
        if ($('#canvas .ability_canvas .links').is(':empty')){
            $.post('frames/edit_robots.php', {
                action: 'canvas_abilities_markup',
                wap: gameSettings.wapFlag,
                edit: gameSettings.allowEditing ? 'true' : 'false',
                user_id: gameSettings.userNumber
                }, function(data, status){
                //console.log('console ability data received, appending...');
                //console.log( data);
                // Append the link markup to the canvas
                $('#canvas .ability_canvas .links').empty().append(data);
                // Trigger scrollbars on any overflow containers
                $('#canvas .ability_canvas .wrapper_overflow', thisEditor).perfectScrollbar(thisScrollbarSettings);
                // Trigger the on complete function
                onComplete.call($('#canvas .ability_canvas'));
                });
            } else {
                // Fix scrollbars on any overflow containers
                $('#canvas .ability_canvas .wrapper_overflow', thisEditor).scrollTop(0).perfectScrollbar('update');
                // Trigger the on complete function
                onComplete.call($('#canvas .ability_canvas'));
            }

        };

    // Define the batch function for loading all unlocked item data
    loadCanvasItemsMarkup = function(onComplete){

        //console.log('calling the loadCanvasItemsMarkup() function');

        if (onComplete == undefined){
            onComplete = function(){
                var thisContainer = $(this);
                thisContainer.attr('data-player', '');
                thisContainer.attr('data-robot', '');
                thisContainer.attr('data-key', '');
                };
            }

        // If the links have not been loaded, do so now
        if ($('#canvas .item_canvas .links').is(':empty')){
            $.post('frames/edit_robots.php', {
                action: 'canvas_items_markup',
                wap: gameSettings.wapFlag,
                edit: gameSettings.allowEditing ? 'true' : 'false',
                user_id: gameSettings.userNumber
                }, function(data, status){
                //console.log('console item data received, appending...');
                //console.log( data);
                // Append the link markup to the canvas
                $('#canvas .item_canvas .links').empty().append(data);
                // Trigger scrollbars on any overflow containers
                $('#canvas .item_canvas .wrapper_overflow', thisEditor).perfectScrollbar(thisScrollbarSettings);
                // Trigger the on complete function
                onComplete.call($('#canvas .item_canvas'));
                });
            } else {
                // Fix scrollbars on any overflow containers
                $('#canvas .item_canvas .wrapper_overflow', thisEditor).scrollTop(0).perfectScrollbar('update');
                // Trigger the on complete function
                onComplete.call($('#canvas .item_canvas'));
            }

        };


    // -- ROBOT CANVAS EVENTS -- //

    // Create the click event for canvas sprites
    $('.robot_canvas .sprite[data-token]', gameCanvas).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }

        var dataSprite = $(this);
        var dataSpriteIndex = dataSprite.index();
        var dataParent = dataSprite.closest('.wrapper')
        var dataSelect = dataParent.attr('data-select');
        var dataToken = dataSprite.attr('data-token');
        var dataRobot = dataSprite.attr('data-robot');
        var dataPlayer = dataSprite.attr('data-player');
        if (dataSprite.hasClass('loading')){ return false; }
        dataSprite.addClass('loading');

        $('.robot_canvas .sprite[data-token]', gameCanvas).css({opacity:''});

        // Define the show function for this sprite
        var showFunction = function(){
            var dataSelectorCurrent = '#'+dataSelect+' .event_visible';
            var dataSelectorNext = '#'+dataSelect+' .event[data-token='+dataToken+']';
            $('.sprite[data-token]', gameCanvas).removeClass('sprite_robot_current').removeClass('sprite_robot_dr-light_current sprite_robot_dr-wily_current sprite_robot_dr-cossack_current');
            dataSprite.addClass('sprite_robot_current').addClass('sprite_robot_current').addClass('sprite_robot_'+dataPlayer+'_current');
            dataParent.css({display:'block'});
            if ($(dataSelectorCurrent, gameConsole).length){
                $(dataSelectorCurrent, gameConsole).stop().animate({opacity:0},250,'swing',function(){
                    $(this).removeClass('event_visible').addClass('event_hidden').css({opacity:1});
                    $(dataSelectorNext, gameConsole).css({opacity:0}).removeClass('event_hidden').addClass('event_visible').animate({opacity:1.0},250,'swing');
                    });
                } else {
                    $(dataSelectorNext, gameConsole).css({opacity:0}).removeClass('event_hidden').addClass('event_visible').animate({opacity:1.0},250,'swing');
                }
            if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                setTimeout(function(){ playSoundEffect('link-click', {volume: 1.0}); }, 250);
                }
            dataSprite.removeClass('loading');

            // We should add the new robot to the parent ready room
            showRobotInReadyRoom(dataPlayer, dataRobot);

            }

        // Load the robot data if not already loaded
        if (loadedConsoleRobotTokens.indexOf(dataRobot) == -1){
            //console.log('calling loadConsoleRobotMarkup from checkpoint A');
            loadConsoleRobotMarkup(dataSprite, dataSpriteIndex, showFunction);
            } else {
            showFunction();
            }

        });

    // Create the click event for robot canvas sort button
    $('div[data-canvas="robots"] .sort_wrapper:not(.fake) .sort', gameCanvas).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }

        var thisSortButton = $(this);
        var thisSortToken = thisSortButton.attr('data-sort');
        var thisSortOrder = thisSortButton.attr('data-order');
        var thisSortPlayer = thisSortButton.attr('data-player');
        var thisPlayerContainer = $('.wrapper[data-player='+thisSortPlayer+']', gameCanvas);
        var thisPlayerRobots = $('.sprite[data-token]', thisPlayerContainer);
        var thisPlayerRobotsTokens = [];
        thisPlayerRobots.each(function(){ thisPlayerRobotsTokens.push($(this).attr('data-robot')); });
        thisPlayerRobotsTokens.join(',');
        //console.log('clicked sort; robot tokens = '+thisPlayerRobotsTokens);
        // Define the post options for the ajax call
        var postData = {action:'sort',token:thisSortToken,order:thisSortOrder,player:thisSortPlayer};
        //console.log('postData', postData);

        // Reverse the sort direction for next click
        if (thisSortOrder == 'asc'){ thisSortButton.attr('data-order', 'desc'); }
        else if (thisSortOrder == 'desc'){ thisSortButton.attr('data-order', 'asc'); }

        // Post the sort request to the server
        thisBody.addClass('loading');
        $.ajax({
            type: 'POST',
            url: 'frames/edit_robots.php',
            data: postData,
            success: function(data, status){

                // DEBUG
                //alert(data);

                // Break apart the response into parts
                var data = data.split('|');
                var dataStatus = data[0] != undefined ? data[0] : false;
                var dataMessage = data[1] != undefined ? data[1] : false;
                var dataContent = data[2] != undefined ? data[2] : false;

                // If there was an error, reset select, otherwise, refresh the page
                if (dataStatus == 'error'){
                    //console.log('error');
                    //console.log( data);
                    thisBody.removeClass('loading');
                    return false;
                    } else if (dataStatus == 'success'){
                    //console.log('success');
                    //console.log( data);
                    // check if the array is the same
                    //if (dataContent == thisPlayerRobotsTokens){ //console.log('dataContent == thisPlayerRobotsTokens'); }
                    //else { //console.log('dataContent != thisPlayerRobotsTokens'); }
                    // split the new order
                    var myArrayOrder = dataContent.split(',');
                    //console.log( myArrayOrder);
                    // get array of elements
                    var myArray = thisPlayerRobots;
                    // sort based on timestamp attribute
                    myArray.sort(function (a, b){
                        // convert to integers from strings
                        var robotToken1 = $(a).attr('data-robot');
                        var robotToken2 = $(b).attr('data-robot');
                        var robotToken1Position = myArrayOrder.indexOf(robotToken1);
                        var robotToken2Position = myArrayOrder.indexOf(robotToken2);
                        //console.log('robotToken1('+robotToken1+') = robotToken1Position('+robotToken1Position+');\n');
                        //console.log('robotToken2('+robotToken2+') = robotToken2Position('+robotToken2Position+');\n ');
                        // compare
                        if (robotToken1Position > robotToken2Position) { return 1; }
                        else if (robotToken1Position < robotToken2Position) { return -1; }
                        else { return 0; }
                        });
                    // put sorted results back on page
                    thisPlayerContainer.find('.sprite[data-token]').remove();
                    thisPlayerContainer.find('.wrapper_overflow').append(myArray);
                    thisBody.removeClass('loading');
                    if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                        playSoundEffect('link-click-action', {volume: 1.0});
                        }
                    return true;
                    } else {
                    //console.log('ummmm');
                    //console.log( data);
                    thisBody.removeClass('loading');
                    return false;
                    }

                // DEBUG
                //alert('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');

                }
            });

        });

    // Create the click event for ability canvas sort button
    $('div[data-canvas="abilities"] .sort', gameCanvas).live('click', function(e){
        e.preventDefault();
        //if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }

        var thisSortButton = $(this);
        var thisSortToken = thisSortButton.attr('data-sort');
        var thisSortOrder = thisSortButton.attr('data-order');
        var thisAbilityWrapper = $('.ability_canvas', gameCanvas);
        var thisAbilities = $('.ability_name[data-id!="0"]', thisAbilityWrapper);
        var thisAbilitiesTokens = [];
        thisAbilities.each(function(){ thisAbilitiesTokens.push($(this).attr('data-ability')); });
        thisAbilitiesTokens.join(',');
        //console.log('clicked sort; ability tokens = '+thisAbilitiesTokens);

        // Remove the active class from other sorts and apply it to this one
        $('.sort', thisAbilityWrapper).removeClass('active');
        thisSortButton.addClass('active');

        // Reverse the sort direction for next click
        if (thisSortOrder == 'asc'){ thisSortButton.attr('data-order', 'desc'); }
        else if (thisSortOrder == 'desc'){ thisSortButton.attr('data-order', 'asc'); }

        // Post the sort request to the server
        thisBody.addClass('loading');

        // get array of ability elements
        var myArray = thisAbilities;

        // sort based on requested attribute
        myArray.sort(function (a, b){

            // predefine keys as false
            var robotKey1 = 0;
            var robotKey2 = 0;
            var robotKey1B = 0;
            var robotKey2B = 0;

            // collect sort keys based on parameter
            if (thisSortToken == 'number'){

                // sort abilities by KEY if number clicked
                robotKey1 = parseInt($(a).attr('data-key'));
                robotKey2 = parseInt($(b).attr('data-key'));

                } else if (thisSortToken == 'cost'){

                // sort abilities by COST if cost clicked
                robotKey1 = parseInt($(a).attr('data-cost'));
                robotKey2 = parseInt($(b).attr('data-cost'));
                robotKey1B = parseInt($(a).attr('data-key'));
                robotKey2B = parseInt($(b).attr('data-key'));

                } else if (thisSortToken == 'power'){

                // sort abilities by POWER if power clicked
                robotKey1 = parseInt($(a).attr('data-power'));
                robotKey2 = parseInt($(b).attr('data-power'));
                robotKey1B = parseInt($(a).attr('data-key'));
                robotKey2B = parseInt($(b).attr('data-key'));

                } else if (thisSortToken == 'type'){

                // sort abilities by TYPE if type clicked
                var indexTypesForSort = typeof gameSettings.mmrpgIndexTypesForSort !== 'undefined' ? gameSettings.mmrpgIndexTypesForSort : gameSettings.mmrpgIndexTypes;
                robotKey1 = indexTypesForSort.indexOf($(a).attr('data-type'));
                robotKey2 = indexTypesForSort.indexOf($(b).attr('data-type'));
                robotKey1B = indexTypesForSort.indexOf($(a).attr('data-type2'));
                robotKey2B = indexTypesForSort.indexOf($(b).attr('data-type2'));

                }

            // compare key values and return
            if (thisSortOrder == 'asc'){
                if (robotKey1 > robotKey2) { return 1; }
                else if (robotKey1 < robotKey2) { return -1; }
                else {
                    if (robotKey1B > robotKey2B) { return 1; }
                    else if (robotKey1B < robotKey2B) { return -1; }
                    else { return 0; }
                    }
                } else if (thisSortOrder == 'desc'){
                if (robotKey1 > robotKey2) { return -1; }
                else if (robotKey1 < robotKey2) { return 1; }
                else {
                    if (robotKey1B > robotKey2B) { return -1; }
                    else if (robotKey1B < robotKey2B) { return 1; }
                    else { return 0; }
                    }
                }

            });

        // put sorted results back on page
        thisAbilityWrapper.find('.ability_name[data-id!="0"]').remove();
        thisAbilityWrapper.find('.wrapper_overflow').append(myArray);
        thisBody.removeClass('loading');
        if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
            playSoundEffect('icon-click-mini', {volume: 1.0});
            }
        return true;

        });

    // Create the click event for item canvas sort button
    $('div[data-canvas="items"] .sort', gameCanvas).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }

        var thisSortButton = $(this);
        var thisSortToken = thisSortButton.attr('data-sort');
        var thisSortOrder = thisSortButton.attr('data-order');
        var thisItemWrapper = $('.item_canvas', gameCanvas);
        var thisAbilities = $('.item_name[data-id!="0"]', thisItemWrapper);
        var thisAbilitiesTokens = [];
        thisAbilities.each(function(){ thisAbilitiesTokens.push($(this).attr('data-item')); });
        thisAbilitiesTokens.join(',');
        //console.log('clicked sort; item tokens = '+thisAbilitiesTokens);

        // Remove the active class from other sorts and apply it to this one
        $('.sort', thisItemWrapper).removeClass('active');
        thisSortButton.addClass('active');

        // Reverse the sort direction for next click
        if (thisSortOrder == 'asc'){ thisSortButton.attr('data-order', 'desc'); }
        else if (thisSortOrder == 'desc'){ thisSortButton.attr('data-order', 'asc'); }

        // Post the sort request to the server
        thisBody.addClass('loading');

        // get array of item elements
        var myArray = thisAbilities;

        // sort based on requested attribute
        myArray.sort(function (a, b){

            // predefine keys as false
            var robotKey1 = 0;
            var robotKey2 = 0;
            var robotKey1B = 0;
            var robotKey2B = 0;

            // collect sort keys based on parameter
            if (thisSortToken == 'number'){

                // sort items by KEY if number clicked
                robotKey1 = $(a).attr('data-key');
                robotKey2 = $(b).attr('data-key');

                } else if (thisSortToken == 'amount'){

                // sort items by COUNT if amount clicked
                robotKey1 = $(a).attr('data-count');
                robotKey2 = $(b).attr('data-count');

                } else if (thisSortToken == 'type'){

                // sort items by TYPE if type clicked
                var indexTypesForSort = typeof gameSettings.mmrpgIndexTypesForSort !== 'undefined' ? gameSettings.mmrpgIndexTypesForSort : gameSettings.mmrpgIndexTypes;
                robotKey1 = indexTypesForSort.indexOf($(a).attr('data-type'));
                robotKey2 = indexTypesForSort.indexOf($(b).attr('data-type'));
                robotKey1B = indexTypesForSort.indexOf($(a).attr('data-type2'));
                robotKey2B = indexTypesForSort.indexOf($(b).attr('data-type2'));

                }

            // compare key values and return
            if (thisSortOrder == 'asc'){
                if (robotKey1 > robotKey2) { return 1; }
                else if (robotKey1 < robotKey2) { return -1; }
                else {
                    if (robotKey1B > robotKey2B) { return 1; }
                    else if (robotKey1B < robotKey2B) { return -1; }
                    else { return 0; }
                    }
                } else if (thisSortOrder == 'desc'){
                if (robotKey1 > robotKey2) { return -1; }
                else if (robotKey1 < robotKey2) { return 1; }
                else {
                    if (robotKey1B > robotKey2B) { return -1; }
                    else if (robotKey1B < robotKey2B) { return 1; }
                    else { return 0; }
                    }
                }

            });

        // put sorted results back on page
        thisItemWrapper.find('.item_name[data-id!="0"]').remove();
        thisItemWrapper.find('.wrapper_overflow').append(myArray);
        thisBody.removeClass('loading');
        if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
            playSoundEffect('icon-click-mini', {volume: 1.0});
            }
        return true;

        });


    // -- ABILITY EQUIPPING EVENTS -- //

    // Create the click event for ability buttons in the console (click the ability slot itself)
    $('a.ability_name', gameConsole).live('click', function(e){
        e.preventDefault();

        var abilityToken = $(this).attr('data-ability');
        //console.log('a.ability_name[data-ability='+abilityToken+'] was clicked');

        if (thisBody.hasClass('loading')){
            //console.log('a.ability_name : sorry but we are loading');
            return false;
            }
        if (!gameSettings.allowEditing){
            //console.log('a.ability_name : sorry but editing is disabled');
            return false;
            }

        var thisLink = $(this);
        var thisContainer = thisLink.parent();
        var thisContainerStatus = thisContainer.attr('data-status') != undefined ? thisContainer.attr('data-status') : 'enabled';
        var thisLinkStatus = thisLink.attr('data-status') != undefined ? thisLink.attr('data-status') : 'enabled';
        var thisRobotEvent = thisLink.parents('.event[data-player][data-robot]');
        var thisRobotAbilitiesEquipped = $('.ability_name[data-ability!=""]', thisRobotEvent).length;
        //console.log('thisContainerStatus = '+thisContainerStatus+', thisLinkStatus = '+thisLinkStatus+', thisRobotAbilitiesEquipped = '+thisRobotAbilitiesEquipped);

        if (thisContainerStatus == 'disabled'){

            //console.log('container is disabled');

            return false;

            } else if (thisLinkStatus == 'pending'){

            //console.log('ability already selected, revert to normal menu');

            return resetEditorMenus(thisRobotEvent);

            } else {

            var thisPlayerToken = thisRobotEvent.attr('data-player');
            var thisRobotToken = thisRobotEvent.attr('data-robot');
            var thisRobotName = thisRobotEvent.find('.header .title').html();
            var thisAbilityKey = parseInt(thisLink.attr('data-key'));
            var thisAbilityID = parseInt(thisLink.attr('data-id'));

            var thisAbilityCompatible = thisRobotEvent.find('.ability_container').attr('data-compatible');
            thisAbilityCompatible = thisAbilityCompatible.split(',');
            var thisAbilityEquipped = [];
            $('.ability_container .ability_name[data-id]', thisRobotEvent).each(function(index,element){
                var thisID = $(this).attr('data-id');
                thisAbilityEquipped.push(thisID);
                });

            var thisRobotTypes = thisRobotEvent.attr('data-types');

            thisLink.css({opacity:''});
            thisLink.attr('data-status', 'pending');
            $('a.ability_name', thisRobotEvent).not(thisLink).css({opacity:0.3}).attr('data-status', 'disabled');
            $('a.player_name', thisRobotEvent).css({opacity:0.3}).attr('data-status', 'disabled');
            $('a.item_name', thisRobotEvent).css({opacity:0.3}).attr('data-status', 'disabled');

            loadCanvasAbilitiesMarkup(function(){

                //console.log('custom complete function!');

                thisAbilityCanvas.attr('data-player', thisPlayerToken);
                thisAbilityCanvas.attr('data-robot', thisRobotToken);
                thisAbilityCanvas.attr('data-key', thisAbilityKey);

                thisItemCanvas.addClass('hidden');
                thisRobotCanvas.addClass('hidden');
                thisAbilityCanvas.find('.wrapper_header').html('Select Ability for '+thisRobotName).attr('class', 'wrapper_header ability_type type_'+thisRobotTypes);
                thisAbilityCanvas.removeClass('hidden');

                $('.wrapper_overflow', thisAbilityCanvas).scrollTop(0).perfectScrollbar('update');

                $('.ability_name[data-id]', thisAbilityCanvas).each(function(index, element){
                    var thisID = $(this).attr('data-id');
                    //console.log('thisID('+thisID+') vs thisAbilityID('+thisAbilityID+')');
                    if (thisID > 0 && thisAbilityCompatible.indexOf(thisID) == -1){
                        //console.log('ID '+thisID+' is NOT compatible');
                        $(this).attr('data-status', 'disabled').css({display:'none',opacity:''});
                        } else {
                        //console.log('ID '+thisID+' is compatible');
                        if (thisID == thisAbilityID){
                            //console.log('Cannot select same ID');
                            $(this).attr('data-status', 'disabled').css({display:''});
                            }
                        else if (thisID > 0 && thisAbilityEquipped.indexOf(thisID) != -1){
                            //console.log('Ability is already equipped');
                            $(this).attr('data-status', 'disabled').css({display:''});
                            }
                        else if (thisID == 0 && thisRobotAbilitiesEquipped < 2){
                            //console.log('Cannot remove last ability');
                            $(this).attr('data-status', 'disabled').css({display:''});
                            }
                        else {
                            //console.log('Ability is totally available');
                            $(this).removeAttr('data-status').css({display:''});
                            }
                        }
                    });

                });

            }
        });

    // Create the click event for the ability buttons in the canvas (select a new ability from the list)
    $('a.ability_name', thisAbilityCanvas).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }

        // Collect the target data from the ability canvas
        var targetPlayerToken =  thisAbilityCanvas.attr('data-player') != undefined ? thisAbilityCanvas.attr('data-player') : '';
        var targetRobotToken =  thisAbilityCanvas.attr('data-robot') != undefined ? thisAbilityCanvas.attr('data-robot') : '';
        var targetAbilityKey =  thisAbilityCanvas.attr('data-key') != undefined ? thisAbilityCanvas.attr('data-key') : '';

        // Collect referneces to the target objects
        var targetRobotEvent = $('.event[data-player='+targetPlayerToken+'][data-robot='+targetRobotToken+']', gameConsole);
        var targetAbilityLink = $('.ability_name[data-key='+targetAbilityKey+']', targetRobotEvent);

        // Collect references to this new ability link
        var thisAbilityLink = $(this);
        var thisAbilityStatus = thisAbilityLink.attr('data-status') != undefined ? thisAbilityLink.attr('data-status') : 'enabled';
        var thisAbilityToken =  thisAbilityLink.attr('data-ability') != undefined ? thisAbilityLink.attr('data-ability') : '';

        // Collect a list of this robot's current abilities so we can reference later
        var oldAbilityList = [];
        $('.ability_name[data-ability]', targetRobotEvent).each(function(index,element){
            var thisAbility = $(this).attr('data-ability');
            oldAbilityList.push(thisAbility);
            });
        //console.log('oldAbilityList =', oldAbilityList);

        //console.log('ability name in selection canvas clicked');
        //console.log('status:'+thisAbilityStatus+' | player:'+targetPlayerToken+' | robot:'+targetRobotToken+' | key:'+targetAbilityKey+' | ability:'+thisAbilityToken+'');

        // Ensure all data was provided before continuing
        if (thisAbilityStatus == 'disabled'){
            //console.log('link status disabled');
            return false;
            } else if (!targetPlayerToken.length){
            //console.log('ability player empty');
            return false;
            } else if (!targetRobotToken.length){
            //console.log('ability robot empty');
            return false;
            } else if (!targetAbilityKey.length){
            //console.log('ability key empty');
            return false;
            }

        // Prepare ajax data to be sent to the server
        var postData = {action:'ability',player:targetPlayerToken,robot:targetRobotToken,key:targetAbilityKey};
        if (thisAbilityToken.length){ postData.ability = thisAbilityToken; }
        else { postData.ability = ''; }
        //console.log('postData =', postData);

        // Post this change back to the server
        thisBody.addClass('loading');
        $.ajax({
            type: 'POST',
            url: 'frames/edit_robots.php',
            data: postData,
            success: function(data, status){

                // Break apart the response into parts
                var data = data.split('|');
                var dataStatus = data[0] != undefined ? data[0] : false;
                var dataMessage = data[1] != undefined ? data[1] : false;
                var dataContent = data[2] != undefined ? data[2] : false;

                // DEBUG
                //console.log('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');

                // If the ability change was a success, update the console link
                if (dataStatus == 'success'){

                    // Break apart the returned content into a list of new abilities
                    var newAbilityList = dataContent.split(',');
                    //console.log('newAbilityList =', newAbilityList);

                    // If the new ability list is completely empty (somehow), add Buster Shot
                    if (!newAbilityList.length){ newAbilityList.push('buster-shot'); }

                    // Collect references to the parent containers
                    var $parentAbilityContainer = targetAbilityLink.closest('.ability_container');
                    var $parentRobotContainer = targetAbilityLink.closest('.event[data-player][data-robot][data-token]');

                    // Check to see if this robot has need for the mecha support info span
                    var mechaSupportActive = false;
                    if (newAbilityList.indexOf('mecha-support') != -1){ mechaSupportActive = true; }
                    if (newAbilityList.indexOf('mecha-assault') != -1){ mechaSupportActive = true; }
                    if (newAbilityList.indexOf('mecha-party') != -1){ mechaSupportActive = true; }
                    if (newAbilityList.indexOf('friend-share') != -1){ mechaSupportActive = true; }
                    if (mechaSupportActive){ $parentRobotContainer.find('.robot_support_subtitle').removeClass('inactive'); }
                    else { $parentRobotContainer.find('.robot_support_subtitle').addClass('inactive'); }

                    // Check to see if this robot has need for the copy style info loading
                    var copyStyleShifted = false;
                    var hadCopyStyle = false;
                    var hasCopyStyle = false;
                    if (oldAbilityList.indexOf('copy-style') != -1){ hadCopyStyle = true; }
                    if (newAbilityList.indexOf('copy-style') != -1){ hasCopyStyle = true; }
                    if (hadCopyStyle && !hasCopyStyle){ copyStyleShifted = true; }
                    else if (hasCopyStyle && !hadCopyStyle){ copyStyleShifted = true; }

                    // If copy style is active, we're going to need to reload the entire ability window
                    if (copyStyleShifted){

                        // Reload the console robot as their type, stats, and image may have changed dramatically
                        reloadConsoleRobotMarkup(targetPlayerToken, targetRobotToken, function(){

                            var $newConsoleRobot = $('.event[data-token='+targetPlayerToken+'_'+targetRobotToken+']', gameConsole);
                            var $existingCanvasRobot = $('.robot_canvas .sprite[data-token='+targetPlayerToken+'_'+targetRobotToken+']', gameCanvas);
                            var $existingCanvasRobotSprite = $('.sprite', $existingCanvasRobot);

                            var newRobotName = $newConsoleRobot.attr('data-name');
                            var newRobotTypes = $newConsoleRobot.attr('data-types');
                            var newRobotImage = $newConsoleRobot.attr('data-image');
                            var newRobotImageSize = $newConsoleRobot.attr('data-image-size');
                            newRobotTypes = newRobotTypes.indexOf(',') != -1 ? newRobotTypes.split(',') : [newRobotTypes];
                            newRobotImageSize = parseInt(newRobotImageSize) > 0 ? parseInt(newRobotImageSize) : 40;
                            var newRobotImageSizeToken = newRobotImageSize+'x'+newRobotImageSize;
                            var newRobotImagePath = 'images/robots/'+newRobotImage+'/';
                            newRobotImagePath += 'sprite_right_'+newRobotImageSizeToken+'.png?'+gameSettings.cacheTime;
                            //console.log('Robot "'+targetRobotToken+'" has style changed!');
                            //console.log('newRobotName =', newRobotName);
                            //console.log('newRobotTypes =', newRobotTypes);
                            //console.log('newRobotImage =', newRobotImage);
                            //console.log('newRobotImageSize =', newRobotImageSize);
                            //console.log('newRobotImageSizeToken =', newRobotImageSizeToken);
                            //console.log('newRobotImagePath =', newRobotImagePath);
                            //console.log('backgroundOffset =', backgroundOffset);

                            $existingCanvasRobot.attr('title', newRobotName);
                            var robotTypeClasses = $existingCanvasRobot.attr('class').split(' ').filter(function(c) {
                                return c.lastIndexOf('robot_type_', 0) !== 0;
                                }).join(' ');
                            for (var i=0; i<newRobotTypes.length; i++){
                                robotTypeClasses += ' robot_type_'+newRobotTypes[i];
                                }
                            $existingCanvasRobot.attr('class', robotTypeClasses);

                            var $newCanvasRobotSprite = $('<span class="sprite"></span>');
                            $newCanvasRobotSprite.addClass('sprite_'+newRobotImageSizeToken);
                            $newCanvasRobotSprite.addClass('sprite_'+newRobotImageSizeToken+'_mugshot');
                            $newCanvasRobotSprite.css({
                                'background-image': 'url('+newRobotImagePath+')'
                                });
                            $existingCanvasRobot.append($newCanvasRobotSprite);
                            $existingCanvasRobotSprite.remove();

                            var copyStyleActive = false;
                            if (newRobotImage.indexOf(targetRobotToken) === -1){ copyStyleActive = true; }
                            //console.log('copyStyleActive =', copyStyleActive);
                            $existingCanvasRobot.find('i.persona').remove();
                            if (copyStyleActive){ $existingCanvasRobot.append('<i class="persona type copy"></i>'); }

                            });

                        }
                    // Otherwise we can loop through and manually update ability slots
                    else {

                        // Loop through all the ability slots and update them with relevant abilities
                        var $targetAbilityLinks = $parentAbilityContainer.find('a.ability_name[data-key]');
                        var $refAbilityLinks = $('a.ability_name[data-ability]', thisAbilityCanvas);
                        //console.log('$parentAbilityContainer.length =', $parentAbilityContainer.length);
                        //console.log('$targetAbilityLinks.length =', $targetAbilityLinks.length);
                        $targetAbilityLinks.each(function(index){

                            var slotKey = index;
                            var newAbilityToken = typeof newAbilityList[slotKey] !== 'undefined' ? newAbilityList[slotKey] : '';
                            //console.log('slotKey =', slotKey);
                            //console.log('newAbilityToken =', newAbilityToken);

                            var $targetAbilityLink = $(this);
                            //console.log('$targetAbilityLink.length =', $targetAbilityLink.length);
                            var $refAbilityLink = false;
                            if (newAbilityToken.length){
                                $refAbilityLink = $refAbilityLinks.filter('[data-ability="'+newAbilityToken+'"]');
                                //console.log('$refAbilityLink.length =', $refAbilityLink.length);
                            }

                            // If a non-empty ability token was provided, normal equip
                            if (newAbilityToken.length
                                && $refAbilityLink.length){

                                // Update the target ability link with new data
                                $targetAbilityLink.attr('class', $refAbilityLink.attr('class'));
                                $targetAbilityLink.attr('data-id', $refAbilityLink.attr('data-id'));
                                $targetAbilityLink.attr('data-ability', $refAbilityLink.attr('data-ability'));
                                $targetAbilityLink.attr('data-type', $refAbilityLink.attr('data-type'));
                                $targetAbilityLink.attr('data-type2', $refAbilityLink.attr('data-type2'));
                                $targetAbilityLink.attr('data-tooltip', $refAbilityLink.attr('data-tooltip'));
                                // Clone the inner html into the target ability link
                                $targetAbilityLink.html($refAbilityLink.html());

                                }
                            // Otherwise if this was an ability remove option, clear stuff
                            else {

                                // Update the target ability link with empty data
                                $targetAbilityLink.attr('class', 'ability_name');
                                $targetAbilityLink.attr('data-id', '0');
                                $targetAbilityLink.attr('data-ability', '');
                                $targetAbilityLink.attr('data-type', '');
                                $targetAbilityLink.attr('data-type2', '');
                                $targetAbilityLink.attr('data-tooltip', '');
                                // Remove the label text and replace with empty hyphen
                                $targetAbilityLink.find('label').attr('style', '').html('-');
                                $targetAbilityLink.find('.info').remove();

                                }

                            });

                        }

                    if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                        playSoundEffect('link-click-action', {volume: 1.0});
                        }

                    }

                thisBody.removeClass('loading');
                targetAbilityLink.triggerSilentClick();


                }
            });

        });



    // Define events for the "auto" preset actions (shuffle, randomize, etc.)
    $('.preset[data-preset]', gameConsole).live('click', function(e){

        // Prevent the default action
        e.preventDefault();
        // Collect the global reference objects
        var $thisLink = $(this);
        var $thisContainer = $thisLink.parent().parent();
        var thisContainerStatus = $thisContainer.attr('data-status') != undefined ? $thisContainer.attr('data-status') : 'enabled';
        var $thisSelect = $('select.ability_name', $thisContainer).eq(0);
        var $parentAbilityContainer = $thisLink.closest('.ability_container');
        var $parentRobotContainer = $thisLink.closest('.event[data-player][data-robot][data-token]');
        var thisLabel = 'presets';
        var dataKey = 0;
        var dataPlayer = $thisLink.attr('data-player');
        var dataRobot = $thisLink.attr('data-robot');
        var optionPresetToken = $thisLink.attr('data-preset');
        var optionSelected = $('option[value='+optionPresetToken+']', $thisSelect);
        var postData = {action:'ability',player:dataPlayer,robot:dataRobot,preset:optionPresetToken};

        // Ensure the parent container is enabled before sending any AJAX, else just update text
        if (thisContainerStatus == 'enabled'){

            // Change the body cursor to wait
            $('body').css('cursor', 'wait !important');
            // Temporarily disable the window while we update stuff
            $thisContainer.css({opacity:0.25}).attr('data-status', 'disabled');
            // Loop through all this ability links for this player and disable
            $('select.ability_name', $thisContainer).each(function(key, value){
                $(this).attr('disabled', 'disabled').prop('disabled', true);
                });

            // Collect a list of this robot's current abilities so we can reference later
            var oldAbilityList = [];
            $('.ability_name[data-ability]', $parentRobotContainer).each(function(index,element){
                var thisAbility = $(this).attr('data-ability');
                oldAbilityList.push(thisAbility);
                });
            //console.log('oldAbilityList =', oldAbilityList);

            // Post this change back to the server
            $.ajax({
                type: 'POST',
                url: 'frames/edit_robots.php',
                data: postData,
                success: function(data, status){

                    // Break apart the response into parts
                    var data = data.split('|');
                    var dataStatus = data[0] != undefined ? data[0] : false;
                    var dataMessage = data[1] != undefined ? data[1] : false;
                    var dataContent = data[2] != undefined ? data[2] : false;

                    // DEBUG
                    //console.log('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');

                    // If the ability change was a success, update the console link
                    if (dataStatus == 'success'){

                        // Break apart the returned content into a list of new abilities
                        var newAbilityList = dataContent.split(',');
                        //console.log('newAbilityList =', newAbilityList);

                        // If the new ability list is completely empty (somehow), add Buster Shot
                        if (!newAbilityList.length){ newAbilityList.push('buster-shot'); }

                        // Collect references to the parent containers
                        var $targetAbilityLinks = $parentAbilityContainer.find('a.ability_name[data-key]');
                        var $refAbilityLinks = $('a.ability_name[data-ability]', thisAbilityCanvas);
                        //console.log('$parentAbilityContainer.length =', $parentAbilityContainer.length);
                        //console.log('$targetAbilityLinks.length =', $targetAbilityLinks.length);

                        // Check to see if this robot has need for the mecha support info span
                        var mechaSupportActive = false;
                        if (newAbilityList.indexOf('mecha-support') != -1){ mechaSupportActive = true; }
                        if (newAbilityList.indexOf('mecha-assault') != -1){ mechaSupportActive = true; }
                        if (newAbilityList.indexOf('mecha-party') != -1){ mechaSupportActive = true; }
                        if (newAbilityList.indexOf('friend-share') != -1){ mechaSupportActive = true; }
                        if (mechaSupportActive){ $parentRobotContainer.find('.robot_support_subtitle').removeClass('inactive'); }
                        else { $parentRobotContainer.find('.robot_support_subtitle').addClass('inactive'); }

                        // Check to see if this robot has need for the copy style info loading
                        var copyStyleShifted = false;
                        var hadCopyStyle = false;
                        var hasCopyStyle = false;
                        if (oldAbilityList.indexOf('copy-style') != -1){ hadCopyStyle = true; }
                        if (newAbilityList.indexOf('copy-style') != -1){ hasCopyStyle = true; }
                        if (hadCopyStyle && !hasCopyStyle){ copyStyleShifted = true; }
                        else if (hasCopyStyle && !hadCopyStyle){ copyStyleShifted = true; }

                    // If copy style is active, we're going to need to reload the entire ability window
                    if (copyStyleShifted){

                        // Reload the console robot as their type, stats, and image may have changed dramatically
                        reloadConsoleRobotMarkup(dataPlayer, dataRobot, function(){

                            var $newConsoleRobot = $('.event[data-token='+dataPlayer+'_'+dataRobot+']', gameConsole);
                            var $existingCanvasRobot = $('.robot_canvas .sprite[data-token='+dataPlayer+'_'+dataRobot+']', gameCanvas);
                            var $existingCanvasRobotSprite = $('.sprite', $existingCanvasRobot);

                            var newRobotName = $newConsoleRobot.attr('data-name');
                            var newRobotTypes = $newConsoleRobot.attr('data-types');
                            var newRobotImage = $newConsoleRobot.attr('data-image');
                            var newRobotImageSize = $newConsoleRobot.attr('data-image-size');
                            newRobotTypes = newRobotTypes.indexOf(',') != -1 ? newRobotTypes.split(',') : [newRobotTypes];
                            newRobotImageSize = parseInt(newRobotImageSize) > 0 ? parseInt(newRobotImageSize) : 40;
                            var newRobotImageSizeToken = newRobotImageSize+'x'+newRobotImageSize;
                            var newRobotImagePath = 'images/robots/'+newRobotImage+'/';
                            newRobotImagePath += 'sprite_right_'+newRobotImageSizeToken+'.png?'+gameSettings.cacheTime;
                            //console.log('Robot "'+dataRobot+'" has style changed!');
                            //console.log('newRobotName =', newRobotName);
                            //console.log('newRobotTypes =', newRobotTypes);
                            //console.log('newRobotImage =', newRobotImage);
                            //console.log('newRobotImageSize =', newRobotImageSize);
                            //console.log('newRobotImageSizeToken =', newRobotImageSizeToken);
                            //console.log('newRobotImagePath =', newRobotImagePath);
                            //console.log('backgroundOffset =', backgroundOffset);

                            $existingCanvasRobot.attr('title', newRobotName);
                            var robotTypeClasses = $existingCanvasRobot.attr('class').split(' ').filter(function(c) {
                                return c.lastIndexOf('robot_type_', 0) !== 0;
                                }).join(' ');
                            for (var i=0; i<newRobotTypes.length; i++){
                                robotTypeClasses += ' robot_type_'+newRobotTypes[i];
                                }
                            $existingCanvasRobot.attr('class', robotTypeClasses);

                            var $newCanvasRobotSprite = $('<span class="sprite"></span>');
                            $newCanvasRobotSprite.addClass('sprite_'+newRobotImageSizeToken);
                            $newCanvasRobotSprite.addClass('sprite_'+newRobotImageSizeToken+'_mugshot');
                            $newCanvasRobotSprite.css({
                                'background-image': 'url('+newRobotImagePath+')'
                                });
                            $existingCanvasRobot.append($newCanvasRobotSprite);
                            $existingCanvasRobotSprite.remove();

                            var copyStyleActive = false;
                            if (newRobotImage.indexOf(dataRobot) === -1){ copyStyleActive = true; }
                            //console.log('copyStyleActive =', copyStyleActive);
                            $existingCanvasRobot.find('i.persona').remove();
                            if (copyStyleActive){ $existingCanvasRobot.append('<i class="persona type copy"></i>'); }

                            });

                        }
                    // Otherwise we can loop through and manually update ability slots
                    else {

                            // Loop through all the ability slots and update them with relevant abilities
                            $targetAbilityLinks.each(function(index){

                                var slotKey = index;
                                var newAbilityToken = typeof newAbilityList[slotKey] !== 'undefined' ? newAbilityList[slotKey] : '';
                                //console.log('slotKey =', slotKey);
                                //console.log('newAbilityToken =', newAbilityToken);

                                var $targetAbilityLink = $(this);
                                var $refAbilityLink = false;
                                if (newAbilityToken.length){ $refAbilityLink = $refAbilityLinks.filter('[data-ability="'+newAbilityToken+'"]'); }
                                //console.log('$targetAbilityLink.length =', $targetAbilityLink.length);
                                //console.log('$refAbilityLink.length =', $refAbilityLink.length);

                                // If a non-empty ability token was provided, normal equip
                                if (newAbilityToken.length
                                    && $refAbilityLink.length){

                                    // Update the target ability link with new data
                                    $targetAbilityLink.attr('class', $refAbilityLink.attr('class'));
                                    $targetAbilityLink.attr('data-id', $refAbilityLink.attr('data-id'));
                                    $targetAbilityLink.attr('data-ability', $refAbilityLink.attr('data-ability'));
                                    $targetAbilityLink.attr('data-type', $refAbilityLink.attr('data-type'));
                                    $targetAbilityLink.attr('data-type2', $refAbilityLink.attr('data-type2'));
                                    $targetAbilityLink.attr('data-tooltip', $refAbilityLink.attr('data-tooltip'));
                                    // Clone the inner html into the target ability link
                                    $targetAbilityLink.html($refAbilityLink.html());
                                    $targetAbilityLink.css({opacity:''});

                                    }
                                // Otherwise if this was an ability remove option, clear stuff
                                else {

                                    // Update the target ability link with empty data
                                    $targetAbilityLink.attr('class', 'ability_name');
                                    $targetAbilityLink.attr('data-id', '0');
                                    $targetAbilityLink.attr('data-ability', '');
                                    $targetAbilityLink.attr('data-type', '');
                                    $targetAbilityLink.attr('data-type2', '');
                                    $targetAbilityLink.attr('data-tooltip', '');
                                    // Remove the label text and replace with empty hyphen
                                    $targetAbilityLink.find('label').attr('style', '').html('-');
                                    $targetAbilityLink.find('.info').remove();

                                    }

                                });

                        }

                        if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                            playSoundEffect('link-click-action', {volume: 1.0});
                            }

                        }

                    thisBody.removeClass('loading');
                    $thisLink.triggerSilentClick();

                    // Change the body cursor back to default
                    $('body').css('cursor', '');
                    // Enable the conatiner again now that we're done
                    $thisContainer.css({opacity:1.0}).attr('data-status', 'enabled');
                    // Loop through all this ability links for this player and disable
                    $('select.ability_name', $thisContainer).each(function(key, value){
                        $(this).removeAttr('disabled').prop('disabled', false);
                        });


                    }

                });

            } else {

                // ...

            }

        });


    // -- PLAYER TRANSFER EVENTS -- //

    // Create the event for player buttons in the console (clicking the player slot itself)
    $('a.player_name', gameConsole).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }
        //console.log('gameConsole > a.player_name clicked');

        var thisLink = $(this);
        var thisContainer = thisLink.parent();
        var thisContainerStatus = thisContainer.attr('data-status') != undefined ? thisContainer.attr('data-status') : 'enabled';
        var thisLinkStatus = thisLink.attr('data-status') != undefined ? thisLink.attr('data-status') : 'enabled';
        var thisRobotEvent = thisLink.parents('.event[data-player][data-robot]');

        //console.log('thisContainerStatus = '+thisContainerStatus+', thisLinkStatus = '+thisLinkStatus);
        if (thisContainerStatus == 'disabled'){

            //console.log('container is disabled');

            return false;

            } else if (thisLinkStatus == 'pending'){

            //console.log('player already selected, revert to normal menu');

            return resetEditorMenus(thisRobotEvent);

            } else {

            var thisPlayerToken = thisRobotEvent.attr('data-player');
            var thisRobotToken = thisRobotEvent.attr('data-robot');
            var thisRobotName = thisRobotEvent.find('.header .title').html();

            thisLink.css({opacity:''});
            thisLink.attr('data-status', 'pending');
            $('a.player_name', thisRobotEvent).not(thisLink).css({opacity:0.3}).attr('data-status', 'disabled');
            $('a.ability_name', thisRobotEvent).css({opacity:0.3}).attr('data-status', 'disabled');

            loadCanvasPlayersMarkup(function(){

                //console.log('custom complete function!');

                thisPlayerCanvas.attr('data-player', thisPlayerToken);
                thisPlayerCanvas.attr('data-robot', thisRobotToken);

                thisAbilityCanvas.addClass('hidden');
                thisItemCanvas.addClass('hidden');
                thisRobotCanvas.addClass('hidden');
                thisPlayerCanvas.find('.wrapper_header').html('Select Player for '+thisRobotName);
                thisPlayerCanvas.removeClass('hidden');

                var playerRobotWrapper = $('.wrapper[data-player="'+thisPlayerToken+'"]', thisRobotCanvas);
                var playerRobotCount = playerRobotWrapper.find('.sprite_robot[data-player][data-robot]').length;
                //console.log('current player '+thisPlayerToken+' has '+playerRobotCount+' robots left');

                $('.wrapper_overflow', thisPlayerCanvas).scrollTop(0).perfectScrollbar('update');

                $('.player_name', thisPlayerCanvas).each(function(index, element){
                    var thisPlayer = $(this).attr('data-player');
                    if (thisPlayer == thisPlayerToken){
                        //console.log('Player '+thisPlayer+' is already selected');
                        $(this).attr('data-status', 'disabled');
                        } else if (playerRobotCount <= 1){
                        //console.log('Player '+thisPlayer+' is on last robot');
                        $(this).attr('data-status', 'disabled');
                        } else {
                        //console.log('Player '+thisPlayer+' is available for transfer');
                        $(this).removeAttr('data-status');
                        }
                    });

                });

            }
        });

    // Create the click event for the player buttons in the canvas (select a new player from the list)
    $('a.player_name', thisPlayerCanvas).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }
        //console.log('thisPlayerCanvas > a.player_name clicked');

        // Collect the robot token from the canvas
        var thisRobotToken =  thisPlayerCanvas.attr('data-robot') != undefined ? thisPlayerCanvas.attr('data-robot') : '';

        // Collect the old player token from the canvas
        var oldPlayerToken =  thisPlayerCanvas.attr('data-player') != undefined ? thisPlayerCanvas.attr('data-player') : '';

        // Collect the new player token from the link
        var newPlayerLink = $(this);
        var newPlayerStatus = newPlayerLink.attr('data-status') != undefined ? newPlayerLink.attr('data-status') : 'enabled';
        var newPlayerToken =  newPlayerLink.attr('data-player') != undefined ? newPlayerLink.attr('data-player') : '';

        // Collect referneces to the initiator link
        var thisRobotEvent = $('.event[data-player='+oldPlayerToken+'][data-robot='+newPlayerToken+']', gameConsole);
        var thisPlayerLink = $('.player_name', thisRobotEvent);

        //console.log('player name in selection canvas clicked');
        //console.log('robot:'+thisRobotToken+' | old-player:'+oldPlayerToken+' | new-player:'+newPlayerToken+' ('+newPlayerStatus+')');

        // Ensure all data was provided before continuing
        if (newPlayerStatus == 'disabled'){
            //console.log('link status disabled');
            return false;
            } else if (!thisRobotToken.length){
            //console.log('robot token empty');
            return false;
            } else if (!oldPlayerToken.length){
            //console.log('old player token empty');
            return false;
            } else if (!newPlayerToken.length){
            //console.log('new player token empty');
            return false;
            }

        // Trigger the player transfer function for this robot
        thisBody.addClass('loading');
        return transferRobotToPlayer(thisRobotToken, oldPlayerToken, newPlayerToken, true, true, function(){

            //console.log('robot transfer complete');

            if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                playSoundEffect('link-click-action', {volume: 1.0});
                }

            thisBody.removeClass('loading');
            resetEditorMenus(thisRobotEvent);
            //thisPlayerLink.triggerSilentClick();

            });

        });


    // -- ITEM EQUIPPING EVENTS -- //

    // Create the event for item buttons in the console (clicking the item slot itself)
    $('a.item_name', gameConsole).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }
        //console.log('gameConsole > a.item_name clicked');

        var thisLink = $(this);
        var thisContainer = thisLink.parent();
        var thisContainerStatus = thisContainer.attr('data-status') != undefined ? thisContainer.attr('data-status') : 'enabled';
        var thisLinkStatus = thisLink.attr('data-status') != undefined ? thisLink.attr('data-status') : 'enabled';
        var thisRobotEvent = thisLink.parents('.event[data-player][data-robot]');
        //console.log('thisContainerStatus = '+thisContainerStatus+', thisLinkStatus = '+thisLinkStatus);
        if (thisContainerStatus == 'disabled'){

            //console.log('container is disabled');

            return false;

            } else if (thisLinkStatus == 'pending'){

            //console.log('item already selected, revert to normal menu');

            return resetEditorMenus(thisRobotEvent);

            } else {

            var thisPlayerToken = thisRobotEvent.attr('data-player');
            var thisRobotToken = thisRobotEvent.attr('data-robot');
            var thisRobotName = thisRobotEvent.find('.header .title').html();

            thisLink.css({opacity:''});
            thisLink.attr('data-status', 'pending');
            $('a.item_name', thisRobotEvent).not(thisLink).css({opacity:0.3}).attr('data-status', 'disabled');
            $('a.player_name', thisRobotEvent).css({opacity:0.3}).attr('data-status', 'disabled');
            $('a.ability_name', thisRobotEvent).css({opacity:0.3}).attr('data-status', 'disabled');

            loadCanvasItemsMarkup(function(){

                //console.log('custom complete function!');

                thisItemCanvas.attr('data-player', thisPlayerToken);
                thisItemCanvas.attr('data-robot', thisRobotToken);

                thisPlayerCanvas.addClass('hidden');
                thisAbilityCanvas.addClass('hidden');
                thisRobotCanvas.addClass('hidden');
                thisItemCanvas.find('.wrapper_header').html('Select Hold Item for '+thisRobotName);
                thisItemCanvas.removeClass('hidden');

                $('.wrapper_overflow', thisItemCanvas).scrollTop(0).perfectScrollbar('update');

                $('.item_name[data-count]', thisItemCanvas).each(function(index, element){
                    var thisCount = parseInt($(this).attr('data-count'));
                    if (thisCount < 1){
                        //console.log('Count '+thisCount+' is less than one');
                        $(this).attr('data-status', 'disabled'); //.css({display:'none'});
                        } else {
                        //console.log('thisCount '+thisCount+' is more than one');
                        $(this).removeAttr('data-status'); //.css({display:''});
                        }
                    });

                });

            }
        });

    // Create the click event for the item buttons in the canvas (select a new item from the list)
    $('a.item_name', thisItemCanvas).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }
        //console.log('---------------------------------------');

        // Collect the target data from the item canvas
        var targetPlayerToken =  thisItemCanvas.attr('data-player') != undefined ? thisItemCanvas.attr('data-player') : '';
        var targetRobotToken =  thisItemCanvas.attr('data-robot') != undefined ? thisItemCanvas.attr('data-robot') : '';
        //console.log('thisItemCanvas > a.item_name clicked (p:'+targetPlayerToken+', r:'+targetRobotToken+')');

        // Collect referneces to the target objects
        var targetRobotEvent = $('.event[data-player='+targetPlayerToken+'][data-robot='+targetRobotToken+']', gameConsole);
        var targetItemLink = $('.item_name', targetRobotEvent);
        var targetRobotCores = targetRobotEvent.attr('data-types');

        // Collect the target robot's core type(s)
        if (targetRobotCores.match(',')){ targetRobotCores = targetRobotCores.split(','); }
        else { targetRobotCores = [targetRobotCores]; }

        //console.log('targetRobotEvent.length = '+targetRobotEvent.length+' | targetItemLink.length = '+targetItemLink.length+' | targetRobotCores.length = '+targetRobotCores.length+' ;');

        // Collect references to this new item link
        var thisItemLink = $(this);
        var thisItemStatus = thisItemLink.attr('data-status') != undefined ? thisItemLink.attr('data-status') : 'enabled';
        var oldItemToken =  targetItemLink.attr('data-item') != undefined ? targetItemLink.attr('data-item') : '';
        var thisItemToken =  thisItemLink.attr('data-item') != undefined ? thisItemLink.attr('data-item') : '';

        //console.log('item name in selection canvas clicked');
        //console.log('status:'+thisItemStatus+' | player:'+targetPlayerToken+' | robot:'+targetRobotToken+' | old-item:'+oldItemToken+' | new-item:'+thisItemToken+'');

        // Ensure all data was provided before continuing
        if (thisItemStatus == 'disabled'){
            //console.log('link status disabled');
            return false;
            } else if (!targetPlayerToken.length){
            //console.log('item player empty');
            return false;
            } else if (!targetRobotToken.length){
            //console.log('item robot empty');
            return false;
            }

        // Prepare ajax data to be sent to the server
        var postData = {action:'item',player:targetPlayerToken,robot:targetRobotToken};
        if (thisItemToken.length){ postData.item = thisItemToken; }
        else { postData.item = ''; }

        // Post this change back to the server
        thisBody.addClass('loading');
        $.ajax({
            type: 'POST',
            url: 'frames/edit_robots.php',
            data: postData,
            success: function(data, status){

                // Break apart the response into parts
                var data = data.split('|');
                var dataStatus = data[0] != undefined ? data[0] : false;
                var dataMessage = data[1] != undefined ? data[1] : false;
                var dataContent = data[2] != undefined ? data[2] : false;
                var dataAbilities = data[3] != undefined ? data[3] : false;

                // DEBUG
                //console.log('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+', dataAbilities = '+dataAbilities+' ');

                // If the item change was a success, update the console link
                if (dataStatus == 'success'){

                    // Increment the old item's count if not empty (we're putting it back)
                    if (oldItemToken.length){
                        //console.log('Increment the old item\'s count if not empty (we\'re putting it back)');
                        var tempLink = $('a.item_name[data-item='+oldItemToken+']', thisItemCanvas);
                        var oldCount = parseInt(tempLink.attr('data-count'));
                        var newCount = oldCount + 1;
                        tempLink.attr('data-count', newCount).find('.count').html('x '+newCount);
                        }

                    // Decrement the old item's count if not empty (we're taking it out)
                    if (thisItemToken.length){
                        //console.log('Decrement the old item\'s count if not empty (we\'re taking it out)');
                        var tempLink = $('a.item_name[data-item='+thisItemToken+']', thisItemCanvas);
                        var oldCount = parseInt(tempLink.attr('data-count'));
                        var newCount = oldCount - 1;
                        tempLink.attr('data-count', newCount).find('.count').html('x '+newCount);
                        }

                    // If a non-empty item token was provided, normal equip
                    if (thisItemToken.length){
                        //console.log('If a non-empty item token was provided, normal equip');

                        // Update the target item link with new data
                        targetItemLink.attr('class', thisItemLink.attr('class'));
                        targetItemLink.attr('data-id', thisItemLink.attr('data-id'));
                        targetItemLink.attr('data-item', thisItemLink.attr('data-item'));
                        targetItemLink.attr('data-type', thisItemLink.attr('data-type'));
                        targetItemLink.attr('data-type2', thisItemLink.attr('data-type2'));
                        targetItemLink.attr('data-tooltip', thisItemLink.attr('data-tooltip').replace(/\|\s[0-9]+\sUnits?/i, ''));
                        // Clone the inner html into the target item link
                        targetItemLink.html(thisItemLink.html()).find('.count').remove();

                        }
                    // Otherwise if this was an item remove option, clear stuff
                    else {
                        //console.log('Otherwise if this was an item remove option, clear stuff');

                        // Update the target item link with empty data
                        targetItemLink.attr('class', 'item_name type none');
                        targetItemLink.attr('data-id', '0');
                        targetItemLink.attr('data-item', '');
                        targetItemLink.attr('data-type', '');
                        targetItemLink.attr('data-type2', '');
                        targetItemLink.attr('data-tooltip', '');
                        // Remove the label text and replace with empty hyphen
                        targetItemLink.find('label').attr('style', '').html('No Item <span class="arrow">&#8711;</span>');
                        targetItemLink.find('.info').remove();

                        }

                    // Update this robot's ability compatibility
                    if (dataAbilities.length){

                        var thisAbilityContainer = targetRobotEvent.find('.ability_container');
                        var thisAbilityCompatible = dataAbilities.split(',');

                        thisAbilityContainer.attr('data-compatible', thisAbilityCompatible.join(','));

                        $('.ability_name[data-id]', thisAbilityContainer).each(function(index, element){
                            var thisID = $(this).attr('data-id');
                            var thisLink = $(this);
                            var thisIndex = thisLink.index();
                            //console.log('thisID('+thisID+')');
                            if (thisID > 0 && thisAbilityCompatible.indexOf(thisID) == -1){

                                //console.log('ID '+thisID+' is NOT compatible, triggering removal...');

                                thisLink.attr('class', 'ability_name');
                                thisLink.attr('data-id', '0');
                                thisLink.attr('data-ability', '');
                                thisLink.attr('title', '');
                                thisLink.attr('data-tooltip', '');
                                thisLink.removeAttr('data-type');
                                thisLink.removeAttr('data-type2');
                                thisLink.html('<label>-</label>');

                                } else {
                                //console.log('Ability ID '+thisID+' is totally compatible');
                                //$(this).removeAttr('data-status').css({display:''});
                                }
                            });


                    }

                    if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                        playSoundEffect('link-click-action', {volume: 1.0});
                        }

                    }

                thisBody.removeClass('loading');
                targetItemLink.triggerSilentClick();

                }
            });

        });


    // PROCESS ALT CHANGE ACTION

    // Collect the base href from the header
    var thisBaseHref = $('head base').attr('href');

    // Define a function for hovering over the image alt link
    $('.robot_image_alts', gameConsole).live({
        mouseenter: function (){
            //console.log('mousein robot image alt');
            var altSprite = $(this).find('.sprite_robot');
            if (altSprite.is(':animated')){ return false; }
            var altFrame = $(this).is('a') ? 'taunt' : 'defend';
            updateSpriteFrame(altSprite, altFrame);
            return true;
            },
        mouseleave: function (){
            //console.log('mousein robot image alt');
            var altSprite = $(this).find('.sprite_robot');
            if (altSprite.is(':animated')){ return false; }
            updateSpriteFrame(altSprite, 'base');
            return true;
            }
        });

    // Attach a click event to the sprite image switcher
    $('a.robot_image_alts', gameConsole).live('click', function(e){
        e.preventDefault();
        if (thisBody.hasClass('loading')){ return false; }
        if (!gameSettings.allowEditing){ return false; }

        // Collect references to the editor objects and player/robot tokens
        var thisLink = $(this);
        var thisSprite = thisLink.find('.sprite_robot');
        var thisPlayerToken = thisLink.attr('data-player');
        var thisRobotToken = thisLink.attr('data-robot');

        // If we're already animating, return false
        if (thisSprite.is(':animated')){ return false; }

        // Collect the alternate skin/image index for this robot, break it down, and find our positions
        var robotImageIndex = thisLink.attr('data-alt-index') != undefined ? thisLink.attr('data-alt-index') : 'base';
        robotImageIndex = robotImageIndex.match(',') ? robotImageIndex.split(',') : [robotImageIndex];
        var thisCurrentImageToken = thisLink.attr('data-alt-current') != undefined ? thisLink.attr('data-alt-current') : 'base';
        var thisCurrentImageIndex = robotImageIndex.indexOf(thisCurrentImageToken);

        // Generate the index key and file path for the skin/image we'll be switching to
        var newImageIndex = thisCurrentImageIndex + 1;
        if (newImageIndex >= robotImageIndex.length){ newImageIndex = 0; }
        var newImageToken = robotImageIndex[newImageIndex];

        return updateRobotImageAlt(thisPlayerToken, thisRobotToken, newImageToken);

        });


    // PROCESS FAVOURITE ACTION

    $('a.robot_favourite', gameConsole).live('click', function(e){

        if (!gameSettings.allowEditing){ return false; }

        var thisLink = $(this);
        var thisPlayerToken = thisLink.attr('data-player');
        var thisRobotToken = thisLink.attr('data-robot');

        e.preventDefault();

        //console.log('robot favourite clicked!');

        if (!thisLink.hasClass('robot_favourite_active')){ thisLink.addClass('robot_favourite_active'); }
        else { thisLink.removeClass('robot_favourite_active'); }

        // Show the overlay to prevent double-clicking
        //$('#edit_overlay', thisPrototype).css({display:'block'});

        // Post this change back to the server
        var postData = {action:'favourite',robot:thisRobotToken,player:thisPlayerToken};
        $.ajax({
            type: 'POST',
            url: 'frames/edit_robots.php',
            data: postData,
            success: function(data, status){

                // DEBUG
                //alert(data);

                // Break apart the response into parts
                var data = data.split('|');
                var dataStatus = data[0] != undefined ? data[0] : false;
                var dataMessage = data[1] != undefined ? data[1] : false;
                var dataContent = data[2] != undefined ? data[2] : false;

                // DEBUG
                //alert('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');
                //console.log( data);
                //console.log('dataStatus:'+dataStatus);
                //console.log('dataMessage:'+dataMessage);
                //console.log('dataContent:'+dataContent);

                // If the ability change was a success, flash the box green
                if (dataStatus == 'success'){


                    //console.log('success! now let\'s update the robot favourite marker...');
                    //console.log( data);

                    if (dataContent == 'added'){ thisLink.addClass('robot_favourite_active'); }
                    else if (dataContent == 'removed'){ thisLink.removeClass('robot_favourite_active'); }

                    //$('#edit_overlay', thisPrototype).css({display:'none'});
                    return true;

                    }


                // Hide the overlay to allow using the robot again
                //$('#edit_overlay', thisPrototype).css({display:'none'});
                return true;

                }
            });



        });

    // Attach resize events to the window
    thisWindow.resize(function(){ windowResizeFrame(); });
    setTimeout(function(){ windowResizeFrame(); }, 1000);
    windowResizeFrame();

    var windowHeight = $(window).height();
    var htmlHeight = $('html').height();
    var htmlScroll = $('html').scrollTop();
    //alert('windowHeight = '+windowHeight+'; htmlHeight = '+htmlHeight+'; htmlScroll = '+htmlScroll+'; ');

    // Fade in the leaderboard screen slowly
    thisBody.waitForImages(function(){
        var tempTimeout = setTimeout(function(){
            if (gameSettings.fadeIn){ thisBody.css({opacity:0}).removeClass('hidden').animate({opacity:1.0}, 800, 'swing'); }
            else { thisBody.removeClass('hidden').css({opacity:1}); }
            // Let the parent window know the menu has loaded
            parent.prototype_menu_loaded();
            }, 1000);
        }, false, true);

    // Initialize the canvas markup
    thisBody.addClass('loading');
    robotEditorCanvasInit();

});

// Create the windowResize event for this page
function windowResizeFrame(){

    var windowWidth = thisWindow.width();
    var windowHeight = thisWindow.height();
    var headerHeight = $('.header', thisBody).outerHeight(true);

    var newBodyHeight = windowHeight;
    var newFrameHeight = newBodyHeight - headerHeight;

    if (windowWidth > 800){ thisBody.addClass((gameSettings.wapFlag ? 'mobileFlag' : 'windowFlag')+'_landscapeMode'); }
    else { thisBody.removeClass((gameSettings.wapFlag ? 'mobileFlag' : 'windowFlag')+'_landscapeMode'); }

    thisBody.css({height:newBodyHeight+'px'});
    thisPrototype.css({height:newBodyHeight+'px'});

}

// Define an initialization function to run when document ready
function robotEditorCanvasInit(){
    //console.log('robotEditorCanvasInit()');

    var robotCanvas = $('.robot_canvas', gameCanvas);
    var robotCanvasPlayerWrappers = false;

    robotCanvas.addClass('hidden');
    robotCanvas.css({height:0,opacity:0});

    //console.log('sending request for canvas markup...');

    $.post('frames/edit_robots.php', {
        action: 'canvas_markup',
        wap: gameSettings.wapFlag,
        edit: gameSettings.allowEditing ? 'true' : 'false',
        user_id: gameSettings.userNumber
        }, function(data, status){
            //console.log('canvas data received, appending...', data);

            var $placeHolderBlock = gameCanvas.parent().find('.placeholder');
            if ($placeHolderBlock.length){
                var marginTop = (-1 * $placeHolderBlock.outerHeight()) + 'px';
                $placeHolderBlock.animate({opacity:0,marginTop:marginTop},600,'swing',function(){ $(this).remove(); });
                }

            $('.links', robotCanvas).empty().append(data);

            robotCanvas.removeClass('hidden');
            robotCanvas.animate({height:'154px',opacity:1},600,'swing',function(){
                //console.log('canvas animation complete');
                $(this).css({height:'auto'});
                });
            thisEditorData.playerTotal = $('.wrapper[data-player]', robotCanvas).length;
            thisEditorData.robotTotal = $('.sprite[data-robot]', robotCanvas).length;

            robotCanvasPlayerWrappers = $('.wrapper[data-player]', robotCanvas);

            // Append the CONSOLE markup after load to prevent halting display and waiting players
            //console.log('sending request for console');
            gameConsole.css({height:0,opacity:0});

            // Loop through all the robot links in the canvas and load their data
            countRobotLinks = $('.sprite[data-robot]', robotCanvas).length;
            countRobotsTriggered = 0;
            countRobotsLoaded = 0;
            countWrapperLoop = 0;
            $('.sprite[data-robot]', robotCanvas).addClass('notloaded');
            while (countRobotsTriggered < countRobotLinks){

                $('.wrapper[data-player]', robotCanvas).each(function(index){

                    var tempWrapper = $(this);
                    var tempPlayer = $(this).attr('data-player');
                    var tempRobot = $('.sprite[data-robot]', tempWrapper).eq(countWrapperLoop);
                    if (!tempRobot.length || tempRobot == undefined){
                        return false;
                        } else {
                        //console.log('calling loadConsoleRobotMarkup from checkpoint B');
                        loadConsoleRobotMarkup(tempRobot, index, function(){
                            showRobotInReadyRoom(tempPlayer, tempRobot.attr('data-robot'));
                            });
                        }
                    return false;

                    });

                countRobotsTriggered++;
                countWrapperLoop++;
                break;

                }

            if (gameSettings.allowEditing){

                // If the transfer program is unlocked allow free movement, else restrict to parent
                if (gameSettings.transferProgramUnlocked){
                    //console.log('transfer program unlocked, allowing free movement');

                    // Allow trasfering between players and free movement of robots
                    robotCanvas.sortable({
                        items: '.sprite[data-robot]',
                        cancel: '.sprite:only-of-type',
                        containment: robotCanvas, //'parent',
                        connectWidth: robotCanvasPlayerWrappers, //'.wrapper[data-player]',
                        opacity: 0.7,
                        //axis: 'y',
                        //handle: '.sprite',
                        start: function(event, ui) {
                            return startDragRobot(event, ui);
                            },
                        stop: function(event, ui) {
                            return stopDragRobot(event, ui);
                            },
                        update: function(event, ui) {
                            return completeDragRobot(event, ui);
                            }
                        });


                    } else {
                    //console.log('transfer program NOT unlocked, restricting movement');

                    // Restrict robot dragging to within each individual player only
                    robotCanvasPlayerWrappers.each(function(){
                        var thisPlayerWrapper = $(this);
                        thisPlayerWrapper.sortable({
                            items: '.sprite[data-robot]',
                            cancel: '.sprite:only-of-type',
                            containment: 'parent',
                            connectWidth: '.wrapper[data-player]',
                            opacity: 0.7,
                            //axis: 'y',
                            //handle: '.sprite',
                            start: function(event, ui) {
                                return startDragRobot(event, ui);
                                },
                            stop: function(event, ui) {
                                return stopDragRobot(event, ui);
                                },
                            update: function(event, ui) {
                                return completeDragRobot(event, ui);
                                }
                            });



                        });


                    }

                }

            // Resize the player wrapper when done
            resizePlayerWrapper();

            // Trigger scrollbars on any overflow containers
            $('.robot_canvas .wrapper_overflow', thisEditor).perfectScrollbar(thisScrollbarSettings);

            });


    // Load the player canvas in the background
    thisPlayerCanvas.addClass('hidden');
    loadCanvasPlayersMarkup();


    // Load the ability canvas in the background
    thisAbilityCanvas.addClass('hidden');
    loadCanvasAbilitiesMarkup(function(){

        // Automatically sort the ability canvas by whatever is default
        //console.log('autosort abilities (checkpoint 1)');
        var thisAbilityCanvas = $('#canvas .ability_canvas');
        //console.log('thisAbilityCanvas = ', thisAbilityCanvas);
        var sortDefault = thisAbilityCanvas.find('.sort_wrapper .sort[data-sort][data-default]');
        if (!sortDefault.length){ sortDefault = thisAbilityCanvas.find('.sort_wrapper .sort[data-sort]').first(); }
        //console.log('sortDefault', sortDefault);
        if (sortDefault.length){
            //console.log('autosort abilities by '+sortDefault.attr('data-sort')+' (checkpoint 2)');
            sortDefault.trigger('click');
            }
        });


    // Load the item canvas in the background
    thisItemCanvas.addClass('hidden');
    loadCanvasItemsMarkup();

}


//Define a function for changing a robot's image (to an alt, for example)
var updateRobotImageAltTimeout = false;
function updateRobotImageAlt(thisPlayerToken, thisRobotToken, newImageToken, updateSession){
    //console.log('updateRobotImageAlt(player:'+thisPlayerToken+', robot:'+thisRobotToken+', image:'+newImageToken+', update:'+updateSession+');');
    if (typeof updateSession !== 'boolean'){ updateSession = true; }

    // Collect references to the editor objects and player/robot tokens
    var thisLink = $('.robot_image_alts[data-player='+thisPlayerToken+'][data-robot='+thisRobotToken+']', gameConsole);
    var thisSprite = thisLink.find('.sprite_robot');

    // If we're already animating, return false
    if (thisSprite.is(':animated')){ return false; }

    // Collect all relevant sprites based on the above info from both the console and canvas areas
    var thisConsoleSprites = $('.event_visible[data-token='+thisPlayerToken+'_'+thisRobotToken+'] .sprite_robot', gameConsole);
    var thisCanvasSprites = $('.robot_canvas .sprite_robot[data-token='+thisPlayerToken+'_'+thisRobotToken+'] .sprite', gameCanvas);

    // DEBUG
    //console.log('robot image alt switch!  :D');

    // Collect the size of the clicked robot sprite and use it to generate classes
    var robotSize = thisSprite.hasClass('.sprite_80x80') ? 80 : 40;
    var robotSizeText = robotSize+'x'+robotSize;
    var robotSizeClass = '.sprite_'+robotSizeText;

    // Collect the alternate skin/image index for this robot, break it down, and find our positions
    var robotImageIndex = thisLink.attr('data-alt-index') != undefined ? thisLink.attr('data-alt-index') : 'base';
    robotImageIndex = robotImageIndex.match(',') ? robotImageIndex.split(',') : [robotImageIndex];
    var thisCurrentImageToken = thisLink.attr('data-alt-current') != undefined ? thisLink.attr('data-alt-current') : 'base';
    var thisCurrentImageIndex = robotImageIndex.indexOf(thisCurrentImageToken);
    var thisCurrentFilePath = '/'+thisRobotToken+(thisCurrentImageToken != 'base' ? '_'+thisCurrentImageToken : '')+'/';

    // Generate the index key and file path for the skin/image we'll be switching to
    var newImageIndex = robotImageIndex.indexOf(newImageToken);
    var newFilePath = '/'+thisRobotToken+(newImageToken != 'base' ? '_'+newImageToken : '')+'/';

    // Collect the background image for this sprite and generate the new path
    var thisCurrentBackgroundImage = thisSprite.css('background-image');
    var newBackgroundImage = thisCurrentBackgroundImage.replace(thisCurrentFilePath, newFilePath);
    // Start preloading the new sprite sheet and mugshot images
    var preloadImages = {sprite:false,mugshot:false};
    var newSpritePath = newBackgroundImage.replace(/^url\("?([^\)\(]+)"?\)$/i, '$1');
    preloadImages.sprite = new Image();
    preloadImages.sprite.src = newSpritePath;
    var newMugPath = newSpritePath.replace('sprite_', 'mug_');
    preloadImages.mugshot = new Image();
    preloadImages.mugshot.src = newMugPath;

    // Update this robot to its victory frame so it's ready for switching
    updateSpriteFrame(thisSprite, 'victory');

    // We should also update the image in the ready room so it looks nice for the player
    if (typeof window.parent.mmrpgReadyRoom !== 'undefined'
        && typeof window.parent.mmrpgReadyRoom.updateRobot !== 'undefined'){
        // If the extra data in dataExtra was not empty and is JSON, parse it into robotInfo
        if (updateRobotImageAltTimeout !== false){ clearTimeout(updateRobotImageAltTimeout); }
        var readyRoom = window.parent.mmrpgReadyRoom;
        var newRobotToken = thisRobotToken;
        var newRobotInfo = {frame: 'summon'};
        readyRoom.updateRobot(newRobotToken, newRobotInfo, 10);
        updateRobotImageAltTimeout = setTimeout(function(){
            clearTimeout(updateRobotImageAltTimeout);
            newRobotInfo = {frame: 'victory', image: thisRobotToken+(newImageToken !== 'base' ? '_'+newImageToken : '')};
            readyRoom.updateRobot(newRobotToken, newRobotInfo, 10);
            updateRobotImageAltTimeout = setTimeout(function(){
                clearTimeout(updateRobotImageAltTimeout);
                newRobotInfo = {frame: 'base'};
                readyRoom.updateRobot(newRobotToken, newRobotInfo, 1);
                }, 900);
            }, 900);
        }


    // DEBUG
    //console.log( {robotSize:robotSize,robotSizeText:robotSizeText,robotSizeClass:robotSizeClass});
    //console.log( {robotImageIndex:robotImageIndex,thisCurrentImageToken:thisCurrentImageToken,thisCurrentImageIndex:thisCurrentImageIndex,thisCurrentFilePath:thisCurrentFilePath});
    //console.log( {newImageIndex:newImageIndex,newImageToken:newImageToken,newFilePath:newFilePath});
    //console.log( {thisCurrentBackgroundImage:thisCurrentBackgroundImage,newBackgroundImage:newBackgroundImage});

    // Define a function for when all the background sprites have been updated
    var afterBackgroundUpdateComplete = function(nextGroup){
        //console.log('backgrounds have finished switching');
        if (nextGroup != undefined){

            // We still have to update the mugshot images and tokens
            //console.log('nextGroup provided, updating tokens and then mugshot background images');
            if (newImageIndex != -1){
                $('.token', thisLink).removeClass('token_active');
                $('.token', thisLink).eq(newImageIndex).addClass('token_active');
                }
            nextGroup.each(function(){ updateBackgroundImageFunction($(this)); });

            } else {
            //console.log('nextGroup undefined, updating server with new choice');

            // If we're not updating the session, we're done
            if (!updateSession){ return true; }

            // Post this change back to the server
            var postData = {action:'altimage',robot:thisRobotToken,player:thisPlayerToken,image:newImageToken};
            $.ajax({
                type: 'POST',
                url: 'frames/edit_robots.php',
                data: postData,
                success: function(data, status){

                    // If the `data` is multi-line, immediately break off anything after the first for later into a `dataExtra` var
                    //console.log('data =', data);
                    var newlineIndex = data.indexOf("\n");
                    var dataExtra = newlineIndex !== -1 ? data.substr(newlineIndex + 1) : false;
                    data = newlineIndex !== -1 ? data.substr(0, newlineIndex) : data;
                    //console.log('data (after) =', data);
                    //console.log('dataExtra =', dataExtra);

                    // DEBUG
                    //alert(data);
                    // Break apart the response into parts
                    var data = data.split('|');
                    var dataStatus = data[0] != undefined ? data[0] : false;
                    var dataMessage = data[1] != undefined ? data[1] : false;
                    var dataContent = data[2] != undefined ? data[2] : false;
                    // DEBUG
                    //console.log('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+',\n dataContent = '+dataContent+'; ');
                    //console.log( data);
                    //console.log('dataStatus:'+dataStatus);
                    //console.log('dataMessage:'+dataMessage);
                    //console.log('dataContent:'+dataContent);

                    // If the alt change was a success, flash the box green
                    if (dataStatus == 'success'){
                        //console.log('success! this robot alt image has been updated');
                        //console.log(data);
                        return true;
                        }


                    // Hide the overlay to allow using the robot again
                    return true;

                    }
                });

            }
     };

    // Define a function for updating the backgrounds images of all relevant sprites
    var updateBackgroundTimeout = false;
    var updateBackgroundImageFunction = function(thisSprite, nextGroup){
        var thisParent = thisSprite.parent();
        var thisCurrentBackgroundImage = thisSprite.css('background-image');
        var newBackgroundImage = thisCurrentBackgroundImage.replace(thisCurrentFilePath, newFilePath);
        //console.log( {thisCurrentBackgroundImage:thisCurrentBackgroundImage,newBackgroundImage:newBackgroundImage});

        // If this sprite's parent element was a wrapper
        if (thisParent.is('.sprite_wrapper')){
            //console.log('parent wrapper was a sprite link');
            thisSprite.css({zIndex:1});
            var cloneSprite = thisSprite.clone();
            cloneSprite.css({backgroundImage:newBackgroundImage,opacity:0,zIndex:100});
            cloneSprite.appendTo(thisParent);
            cloneSprite.animate({opacity:1},{duration:1000,easing:'swing',queue:false,complete:function(){
                //console.log('animation complete');
                thisSprite.remove();
                updateSpriteFrame(cloneSprite, 'base');
                }});
            }
        // Otherwise, just swap the image
        else {
            //console.log('parent wrapper was something else '+thisParent.attr('class'));
            thisSprite.css({backgroundImage:newBackgroundImage});
            updateSpriteFrame(thisSprite);
            }

        clearTimeout(updateBackgroundTimeout);
        updateBackgroundTimeout = setTimeout(function(){ afterBackgroundUpdateComplete(nextGroup); }, 1100);


        };

    thisLink.attr('data-alt-current', newImageToken);
    thisConsoleSprites.each(function(){ return updateBackgroundImageFunction($(this), thisCanvasSprites); });

    return true;

}

//Define a function for swapping the frame of a sprite
function updateSpriteFrame(thisSprite, newFrame){
    //console.log('updateSpriteFrame(thisSprite, '+newFrame+')');
    thisSprite.attr('class', function(index,classes){
     //console.log('thisSprite.attr(class, function('+index+','+classes+')');
     var newClasses = classes.replace(/(^|\s)(sprite_[0-9]+x[0-9]+_)([a-z0-9]+)(\s|$)/i, '$1$2'+newFrame+'$4');
     //console.log('classes.replace($1$2'+newFrame+'$4) | newClasses =  '+newClasses);
     return newClasses;
     });
}

//Define a function to trigger when a robot drag has started
function startDragRobot(event, ui){

    //console.log('robot drag has started');
    $('.robot_canvas', gameCanvas).addClass('dragging');
    $('.robot_canvas .wrapper_overflow', gameCanvas).each(function(){
        var overflowHeight = $(this).innerHeight();
        var overflowPlayer = $(this).parent().attr('data-player');
        //console.log('add hard-coded height of '+overflowHeight+' to '+overflowPlayer);
        //$(this).css({height:overflowHeight+'px',overflow:'visible'});
        $(this).css({overflow:'visible'});
        });


}

//Define a function to trigger when a robot drag has stopped
function stopDragRobot(event, ui){

    //console.log('robot drag has stopped');
    $('.robot_canvas', gameCanvas).removeClass('dragging');
    $('.robot_canvas .wrapper_overflow', gameCanvas).each(function(){
        var overflowPlayer = $(this).parent().attr('data-player');
        //console.log('remove hard-coded height from '+overflowPlayer);
        //$(this).css({height:'',overflow:''});
        $(this).css({overflow:''});
        });

}

// Define a function to trigger when a robot has been dragged around
function completeDragRobot(event, ui){

    var thisSortRobot = ui.item;
    var thisSortRobotToken = thisSortRobot.attr('data-robot');
    var thisSortRobotPlayer = thisSortRobot.attr('data-player');
    var thisSortParentPlayer = thisSortRobot.parents('.wrapper').attr('data-player');
    var thisSortRobotActive = thisSortRobot.hasClass('sprite_robot_current') ? true : false;

    thisBody.addClass('loading');

    // -- SORT ROBOT WITHIN SAME PARENT -- //

    // If the robot player and parent player are the same, this is a simple sort
    if (thisSortRobotPlayer == thisSortParentPlayer){

        updatePlayerSortOrder(thisSortRobotPlayer);
        thisBody.removeClass('loading');

    }

    // -- TRANSFER ROBOT THEN SORT BOTH PARENTS -- //

    // Else if the robot player and parent player are not the same, we've got some work to do
    else if (thisSortRobotPlayer != thisSortParentPlayer){

        var showInConsole = thisSortRobotActive ? true : false;
        var updateInCanvas = false;
        transferRobotToPlayer(thisSortRobotToken, thisSortRobotPlayer, thisSortParentPlayer, showInConsole, updateInCanvas, function(){
            updatePlayerSortOrder(thisSortRobotPlayer);
            updatePlayerSortOrder(thisSortParentPlayer);
            thisBody.removeClass('loading');
            });

    }

}

//Define a function to call when a player wrapper needs to have it's sort updated
function updatePlayerSortOrder(sortPlayer){

    //console.log('updatePlayerSortOrder(sortPlayer = '+sortPlayer+')');

    var thisSortToken = 'manual';
    var thisSortOrder = 'auto';
    var thisSortPlayer = sortPlayer; //thisSortRobot.attr('data-player');
    var thisPlayerContainer = $('.wrapper[data-player='+thisSortPlayer+']', gameCanvas);
    var thisPlayerRobots = $('.sprite[data-token]', thisPlayerContainer);
    var thisPlayerRobotsTokens = [];
    thisPlayerRobots.each(function(){ thisPlayerRobotsTokens.push($(this).attr('data-robot')); });
    thisPlayerRobotsTokens = thisPlayerRobotsTokens.join(',');
    //console.log('manual for '+thisSortPlayer+' sort; robot tokens = '+thisPlayerRobotsTokens);

    // Define the post options for the ajax call
    var postData = {action:'sort',token:thisSortToken,order:thisSortOrder,player:thisSortPlayer,robots:thisPlayerRobotsTokens};
    //console.log('postData', postData);

    // Reverse the sort direction for next click
    if (thisSortOrder == 'asc'){ thisSortButton.attr('data-order', 'desc'); }
    else if (thisSortOrder == 'desc'){ thisSortButton.attr('data-order', 'asc'); }
    // Post the sort request to the server
    thisBody.addClass('loading');
    $.ajax({
     type: 'POST',
     url: 'frames/edit_robots.php',
     data: postData,
     success: function(data, status){

         // DEBUG
         //alert(data);

         // Break apart the response into parts
         var data = data.split('|');
         var dataStatus = data[0] != undefined ? data[0] : false;
         var dataMessage = data[1] != undefined ? data[1] : false;
         var dataContent = data[2] != undefined ? data[2] : false;

         // If there was an error, reset select, otherwise, refresh the page
         if (dataStatus == 'error'){
             //console.log('error');
             //console.log( data);
             thisBody.removeClass('loading');
             return false;
             } else if (dataStatus == 'success'){
             //console.log('success');
             //console.log( data);
             // check if the array is the same
             //if (dataContent == thisPlayerRobotsTokens){ //console.log('dataContent == thisPlayerRobotsTokens'); }
             //else { //console.log('dataContent != thisPlayerRobotsTokens'); }
             // split the new order
             var myArrayOrder = dataContent.split(',');
             //console.log( myArrayOrder);
             // get array of elements
             var myArray = thisPlayerRobots;
             // sort based on timestamp attribute
             myArray.sort(function (a, b){
                 // convert to integers from strings
                 var robotToken1 = $(a).attr('data-robot');
                 var robotToken2 = $(b).attr('data-robot');
                 var robotToken1Position = myArrayOrder.indexOf(robotToken1);
                 var robotToken2Position = myArrayOrder.indexOf(robotToken2);
                 //console.log('robotToken1('+robotToken1+') = robotToken1Position('+robotToken1Position+');\n');
                 //console.log('robotToken2('+robotToken2+') = robotToken2Position('+robotToken2Position+');\n ');
                 // compare
                 if (robotToken1Position > robotToken2Position) { return 1; }
                 else if (robotToken1Position < robotToken2Position) { return -1; }
                 else { return 0; }
                 });
             // put sorted results back on page
             thisPlayerContainer.find('.sprite[data-token]').remove();
             thisPlayerContainer.find('.wrapper_overflow').append(myArray);
             thisBody.removeClass('loading');
             return true;
             } else {
             //console.log('ummmm');
             //console.log( data);
             thisBody.removeClass('loading');
             return false;
             }

         // DEBUG
         //alert('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');

         }
     });

}

// Define a function to call when a player wrapper needs to have it's sort updated
function transferRobotToPlayer(thisRobotToken, currentPlayerToken, newPlayerToken, showInConsole, updateInCanvas, onComplete){

    //console.log('transferRobotToPlayer(thisRobotToken = '+thisRobotToken+', currentPlayerToken = '+currentPlayerToken+', newPlayerToken = '+newPlayerToken+')');

    // Define the oncomplete if not defined
    if (onComplete == undefined){ onComplete = function(){ return true; }; }

    // Collect a reference to this select object
    var thisRobotConsole = $('.event[data-robot='+thisRobotToken+']', gameConsole);
    var thisPlayerLink = $('a.player_name[data-player="'+currentPlayerToken+'"]', thisRobotConsole);
    // Collect the current robot name and token
    var thisRobotName = $('a[data-robot='+thisRobotToken+']', gameCanvas).attr('title');
    // Collect the current and target player tokens
    var currentPlayerLabel = $('label', thisPlayerLink).html();
    // Count the number of other options for this player
    var thisParentWrapper = $('.wrapper_'+currentPlayerToken, gameCanvas);
    var countRobotOptions = $('a[data-token]', thisParentWrapper).length;

    // Define the post options for the ajax call
    var postData = {action:'player',robot:thisRobotToken,player1:currentPlayerToken,player2:newPlayerToken};
    // Trigger a transfer of this robot to the requested player
    var confirmTransfer = true;
    if (confirmTransfer){
        //$('#edit_overlay', thisPrototype).css({display:'block'});
        thisBody.addClass('loading');
        // Post the transfer request to the server
        //console.log('posting robot transfer request...', postData);
        $.ajax({
            type: 'POST',
            url: 'frames/edit_robots.php',
            data: postData,
            success: function(data, status){
                //console.log('robot transfer response received!', data);

                // DEBUG
                //alert(data);

                // Break apart the response into parts
                var data = data.split('|');
                var dataStatus = data[0] != undefined ? data[0] : false;
                var dataMessage = data[1] != undefined ? data[1] : false;
                var dataContent = data[2] != undefined ? data[2] : false;

                // If there was an error, reset select, otherwise, refresh the page
                if (dataStatus == 'error'){

                    // -- POST STATUS ERROR -- //

                    // Reset the select button's position and return false
                    //console.log('there was an error...', data);
                    //$('#edit_overlay', thisPrototype).css({display:'none'});
                    thisBody.removeClass('loading');
                    return false;

                    } else if (dataStatus == 'success'){

                    // -- POST STATUS SUCCESS -- //

                    //console.log('success! now let\'s move the robot...');
                    //console.log( data);
                    var newData = data.slice(2);
                    newData = newData.join('|');
                    if (showInConsole){ $('#console #robots').append(newData); }
                    // Collect the container and token references and prepare the move
                    var canvasButton = $('.sprite[data-robot='+thisRobotToken+']', gameCanvas);
                    var consoleEvent = $('.event[data-token='+currentPlayerToken+'_'+thisRobotToken+']', gameConsole);
                    var consolePlayerSelect = $('.player_select_block', consoleEvent);
                    //if (!consolePlayerSelect.length){ //console.log('player select block not found'); }
                    //consolePlayerSelect.css({backgroundColor:'blue !important'});
                    var newCanvasWrapper = $('.wrapper_'+newPlayerToken+'[data-select=robots] .wrapper_overflow', gameCanvas);
                    var newConsoleToken = newPlayerToken+'_'+thisRobotToken;
                    if (newPlayerToken == 'dr-light'){ var newPlayerName = 'Dr. Light'; }
                    else if (newPlayerToken == 'dr-wily'){ var newPlayerName = 'Dr. Wily'; }
                    else if (newPlayerToken == 'dr-cossack'){ var newPlayerName = 'Dr. Cossack'; }
                    // Remove this robot from the console
                    if (showInConsole){ consoleEvent.remove(); }
                    // Move this robot's button to the new wrapper and update their data token
                    //$('.current_player', consolePlayerSelect).removeClass('current_player_'+currentPlayerToken).addClass('current_player_'+newPlayerToken);
                    //$('.player_name label', consolePlayerSelect).html(newPlayerName);
                    //$('.player_name select', consolePlayerSelect).attr('data-player', newPlayerToken);
                    //$('select.ability_name', consoleEvent).attr('data-player', newPlayerToken);
                    canvasButton.removeClass('sprite_robot_'+currentPlayerToken);
                    if (updateInCanvas){ canvasButton.removeClass('sprite_robot_'+currentPlayerToken+'_current'); }
                    canvasButton.addClass('sprite_robot_'+newPlayerToken);
                    if (updateInCanvas){ canvasButton.addClass('sprite_robot_'+newPlayerToken+'_current'); }
                    canvasButton.attr('data-player', newPlayerToken);
                    canvasButton.attr('data-token', newConsoleToken);
                    if (updateInCanvas){ canvasButton.appendTo(newCanvasWrapper); }
                    //consoleEvent.attr('data-token', newConsoleToken);
                    // Reload the current page and return true
                    //window.location = window.location.href;
                    // Trigger the wrapper resize function based on the new robot amount
                    resizePlayerWrapper();
                    //$('#edit_overlay', thisPrototype).css({display:'none'});
                    thisBody.removeClass('loading');
                    return onComplete();

                    } else {

                    // -- POST STATUS UNKNOWN -- //

                    //console.log('ummmm');
                    //console.log(data);
                    //$('#edit_overlay', thisPrototype).css({display:'none'});
                    thisBody.removeClass('loading');
                    return false;

                    }

                // DEBUG
                //alert('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');

                }
            });

        }

    return false;

}

// Define a function for triggering a reload on robot console markup
function reloadConsoleRobotMarkup(playerToken, robotToken, onComplete){
    //console.log('reloadConsoleRobotMarkup('+playerToken+', '+robotToken+', onComplete)');

    if (playerToken == undefined){ playerToken = 'player'; }
    if (robotToken == undefined){ robotToken = 'robot'; }
    if (onComplete == undefined){ onComplete = function(){ return true; }; }

    var thisRobot = $('.sprite[data-robot='+robotToken+'][data-player='+playerToken+']', thisRobotCanvas);
    var thisRobotIndex = thisRobot.index();

    var $existingConsoleRobot = $('.event[data-token='+playerToken+'_'+robotToken+']', gameConsole);
    var newOnComplete = onComplete;
    if ($existingConsoleRobot.length){
        $existingConsoleRobot.addClass('event_reloading');
        var newOnComplete = function(){
            $existingConsoleRobot.stop().remove();
            var $newConsoleRobot = $('.event[data-token='+playerToken+'_'+robotToken+']', gameConsole);
            $newConsoleRobot.removeClass('event_hidden').addClass('event_visible');
            return onComplete();
            };
        }

    //console.log('calling loadConsoleRobotMarkup from checkpoint C');
    return loadConsoleRobotMarkup(thisRobot, thisRobotIndex, newOnComplete);

}

function resetEditorMenus(robotEvent, onComplete){

    if (onComplete == undefined){ onComplete = function(){}; }

    $('a.player_name', robotEvent).css({opacity:''}).removeAttr('data-status');
    $('a.item_name', robotEvent).css({opacity:''}).removeAttr('data-status');
    $('a.ability_name', robotEvent).css({opacity:''}).removeAttr('data-status');

    thisPlayerCanvas.attr('data-robot', '');
    thisPlayerCanvas.addClass('hidden');
    thisPlayerCanvas.find('.wrapper_header').html('Select Player');

    thisItemCanvas.attr('data-player', '');
    thisItemCanvas.attr('data-robot', '');
    thisItemCanvas.addClass('hidden');
    thisItemCanvas.find('.wrapper_header').html('Select Item');

    thisAbilityCanvas.attr('data-player', '');
    thisAbilityCanvas.attr('data-robot', '');
    thisAbilityCanvas.attr('data-key', '');
    thisAbilityCanvas.addClass('hidden');
    thisAbilityCanvas.find('.wrapper_header').html('Select Ability');

    thisRobotCanvas.removeClass('hidden');

    return onComplete();


}