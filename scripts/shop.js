// Generate the document ready events for this page
var thisBody = false;
var thisPrototype = false;
var thisWindow = false;
var thisShop = false;
var thisShopData = {shopTotal:0,zennyCounter:0,itemQuantities:{},itemPrices:{},allowEdit:true,unlockedPlayers:{},lastShopToken:''};
var resizePlayerWrapper = function(){};
$(document).ready(function(){

    // Update global reference variables
    thisBody = $('#mmrpg');
    thisPrototype = $('#prototype', thisBody);
    thisWindow = $(window);
    thisShop = $('#shop', thisBody);

    //console.log('thisShopData =', thisShopData);

    // -- SOUND EFFECT FUNCTIONALITY -- //

    // Define some interaction sound effects for the shop menu
    var thisContext = $('#shop');
    var playSoundEffect = function(){};
    if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){

        // Define a quick local function for routing sound effect plays to the parent
        playSoundEffect = function(soundName, options){
            if (this instanceof jQuery || this instanceof Element){
                if ($(this).data('silentClick')){ return; }
                if ($(this).is('.disabled')){ return; }
                if ($(this).is('.button_disabled')){ return; }
                if ($(this).is('.item_cell_disabled *')){ return; }
                }
            top.mmrpg_play_sound_effect(soundName, options);
            };

        // SHOP KEEPER LINKS

        // Add hover and click sounds to the buttons in the shop keeper menu
        $('#canvas .sprite_player', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'link-hover', {volume: 0.5});
            });
        $('#canvas .sprite_player', thisContext).live('click', function(){
            playSoundEffect.call(this, 'link-click', {volume: 1.0});
            });

        // SHOP TAB LINKS

        // Add hover and click sounds to the buttons in the shop keeper tabs
        $('#console .event .shop_tabs_links .tab_link', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event .shop_tabs_links .tab_link', thisContext).live('click', function(){
            playSoundEffect.call(this, 'icon-click', {volume: 1.0});
            });

        // SHOP INVENTORY LINKS

        // Add hover and click sounds to the buttons in the shop inventory spans
        $('#console .event .item_cell span[data-click-tooltip]', thisContext).live('mouseenter', function(){
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $('#console .event .item_cell span[data-click-tooltip]', thisContext).live('click', function(){
            // [tooltip takes care of this one]
            });

        // SHOP BUY/SELL BUTTONS

        // Add hover and click sounds to the buttons in the shop buy and sell buttons
        var selector = '';
        selector += '#console .event .buy_button';
        selector += ',#console .event .sell_button';
        selector += ',#console .event .confirm_button';
        selector += ',#console .event .cancel_button';
        selector += ',#console .event .player_button';
        selector += ',#console .event .item_quantity_mods a';
        $(selector, thisContext).live('mouseenter', function(){
            if ($(this).is('.cancel_button')){ playSoundEffect.call(this, 'back-hover', {volume: 0.75}); }
            playSoundEffect.call(this, 'icon-hover', {volume: 0.5});
            });
        $(selector, thisContext).live('click', function(){
            if ($(this).is('.cancel_button')){ playSoundEffect.call(this, 'back-click', {volume: 0.75}); }
            else if ($(this).is('.item_quantity_mods a')){ playSoundEffect.call(this, 'icon-click-mini', {volume: 0.75}); }
            else { playSoundEffect.call(this, 'icon-click', {volume: 0.75}); }
            });

        }

    // Update the player and player count by counting elements
    thisShopData.shopTotal = $('#canvas .wrapper[data-shop]', thisShop).length;
    //console.log('thisShopData', thisShopData);

    //console.log(thisShopData);

    // Trigger the resize wrapper on load

    //alert('I, the shop, have a wap setting of '+(gameSettings.wapFlag ? 'true' : 'false')+'?! and my body has a class of '+$('body').attr('class')+'!');

    // Start playing the appropriate stage music
    //top.mmrpg_music_load('misc/data-base');

    // Define a variable to hold the timeout for saving shop settings
    var saveShopSettingTimeout;
    var lastShopToken = thisShopData.lastShopToken.length ? thisShopData.lastShopToken.split('/') : [];
    //console.log('lastShopToken', lastShopToken);

    // Create the click event for canvas sprites
    //$('.sprite[data-token]', gameCanvas).live('click', function(e){
    $('.wrapper[data-shop]', gameCanvas).live('click', function(e){
        e.preventDefault();
        if (!thisShopData.allowEdit){ return false; }
        var dataParent = $(this);
        var dataSprite = $(this).find('.sprite[data-token]');
        var dataSelect = dataParent.attr('data-select');
        var dataToken = dataSprite.attr('data-token');
        var dataShop = dataSprite.attr('data-shop');
        var dataSelectorCurrent = '#'+dataSelect+' .event_visible';
        var dataSelectorNext = '#'+dataSelect+' .event[data-token='+dataToken+']';
        //console.log('.sprite[data-token] clicked!', {dataToken:dataToken,dataShop:dataShop,dataSelectorCurrent:dataSelectorCurrent,dataSelectorNext:dataSelectorNext});
        $('.wrapper_active', gameCanvas).removeClass('wrapper_active');
        $('.sprite_shop_current', gameCanvas).removeClass('sprite_shop_current');
        //console.log('updating perfect scrollbar 1');
        $('#console .scroll_wrapper', thisShop).perfectScrollbar('update');
        dataParent.addClass('wrapper_active').css({display:'block'});
        dataSprite.addClass('sprite_shop_current');
        var $firstTabLink = false;
        if ($(dataSelectorCurrent, gameConsole).length){
            //console.log('dataSelectorCurrent (', dataSelectorCurrent, ') exists');
            $(dataSelectorCurrent, gameConsole).stop().animate({opacity:0},250,'swing',function(){
                $(this).removeClass('event_visible').addClass('event_hidden').css({opacity:1});
                $(dataSelectorNext, gameConsole).css({opacity:0}).removeClass('event_hidden').addClass('event_visible').animate({opacity:1.0},250,'swing');
                if (lastShopToken.length && lastShopToken[0] === dataShop){
                    $firstTabLink = $(dataSelectorNext, gameConsole).find('.tab_link[data-tab="'+lastShopToken[1]+'"][data-tab-type="'+lastShopToken[2]+'"]');
                    } else {
                    $firstTabLink = $(dataSelectorNext, gameConsole).find('.tab_link').first();
                    }
                $firstTabLink.triggerSilentClick();
                //console.log('$firstTabLink', $firstTabLink);
                });
            } else {
            //console.log('dataSelectorCurrent (', dataSelectorCurrent, ') does NOT exist');
            $(dataSelectorNext, gameConsole).css({opacity:0}).removeClass('event_hidden').addClass('event_visible').animate({opacity:1.0},250,'swing');
            if (lastShopToken.length && lastShopToken[0] === dataShop){
                //console.log('shop ('+dataShop+') matches last shop ('+lastShopToken[0]+'), let\'s click the tab matching data-tab:', lastShopToken[1], ', data-tab-type:', lastShopToken[2]);
                $firstTabLink = $(dataSelectorNext, gameConsole).find('.tab_link[data-tab="'+lastShopToken[1]+'"][data-tab-type="'+lastShopToken[2]+'"]');
                } else {
                //console.log('shop ('+dataShop+') does NOT match last shop ('+lastShopToken[0]+'), let\'s click the first tab');
                $firstTabLink = $(dataSelectorNext, gameConsole).find('.tab_link').first();
                }
            $firstTabLink.triggerSilentClick();
            //console.log('$firstTabLink', $firstTabLink);
            }
        if (saveShopSettingTimeout){ clearTimeout(saveShopSettingTimeout); }
        saveShopSettingTimeout = setTimeout(function(){
            var newLastShopToken = dataShop+($firstTabLink ? '/'+$firstTabLink.attr('data-tab') : '')+($firstTabLink ? '/'+$firstTabLink.attr('data-tab-type') : '');
            //console.log('NOT saving shop settings for '+newLastShopToken);
            $.post('scripts/script.php',{requestType:'session',requestData:'battle_settings,last_shop_token,'+newLastShopToken});
            lastShopToken = newLastShopToken.split('/');
            }, 2000);
        return true;
        });

    // Attach tab events to any shop tabs so that we can switch between selling/buying
    $('.shop_tabs_links .tab_link[data-tab]', gameConsole).live('click', function(e){
        e.preventDefault();
        if (!thisShopData.allowEdit){ return false; }
        var thisTab = $(this);
        var thisTabToken = thisTab.attr('data-tab');
        var thisTabType = thisTab.attr('data-tab-type');
        var thisTabShopToken = thisTab.closest('.event[data-token]').attr('data-token');
        //console.log('clicked [data-token='+thisTabShopToken+'] .tab_link[data-tab='+thisTabToken+'][data-tab-type='+thisTabType+']');
        var tabLinkBlock = thisTab.parent();
        var eventContainer = tabLinkBlock.parent();
        var tabContainerBlock = $('.shop_tabs_containers', eventContainer);
        var thiContainer = $('.tab_container[data-tab='+thisTabToken+'][data-tab-type='+thisTabType+']', eventContainer);
        $('.shop_tabs_links .tab_link[data-tab]', gameConsole).removeClass('tab_link_active');
        thisTab.addClass('tab_link_active');
        $('.shop_tabs_containers .tab_container[data-tab]', gameConsole).removeClass('tab_container_active');
        thiContainer.addClass('tab_container_active');
        var thisConfirmCell = thiContainer.find('.item_cell_confirm');
        thisConfirmCell.attr('data-kind', '').attr('data-action', '').attr('data-token', '').attr('data-price', '').attr('data-quantity', '');
        thisConfirmCell.empty().html('<div class="placeholder">&hellip;</div>');
        //console.log('updating perfect scrollbar 2');
        $('#console .scroll_wrapper', thisShop).perfectScrollbar('update');
        if (saveShopSettingTimeout){ clearTimeout(saveShopSettingTimeout); }
        saveShopSettingTimeout = setTimeout(function(){
            var newLastShopToken = thisTabShopToken+'/'+thisTabToken+'/'+thisTabType;
            //console.log('NOT saving shop settings for '+newLastShopToken);
            $.post('scripts/script.php',{requestType:'session',requestData:'battle_settings,last_shop_token,'+newLastShopToken});
            lastShopToken = newLastShopToken.split('/');
            }, 2000);
        return true;
        });

    // Define a function for creating necessary click-events for any quantity mod buttons
    var generateQuantityModEvents = function($itemCellConfirm, thisAction){
        if ($itemCellConfirm.attr('data-kind') != 'item'){ return false; }
        //console.log('generateQuantityModEvents($itemCellConfirm, thisAction)', $itemCellConfirm);

        // Collect basic details about he item and cell
        var itemToken = $itemCellConfirm.attr('data-token');
        var $itemCell = $('.item_cell[data-token="'+itemToken+'"][data-action="'+thisAction+'"]', gameConsole);
        var unitPrice = parseInt($itemCell.find('.item_price').attr('data-price') || 0);
        var currentQuantity = parseInt($itemCellConfirm.attr('data-quantity'));
        //console.log('itemToken =', itemToken, '$itemCell = ', $itemCell.length, 'unitPrice = ', unitPrice);

        // Define a quick function for determining current and max values, based on action type
        var getCurrentAndMax = function(){
            var currentQuantity = parseInt($itemCellConfirm.attr('data-quantity'));
            if (thisAction == 'sell'){
                var maxQuantity = parseInt($itemCell.find('.item_quantity').attr('data-quantity') || 0);
                } else if (thisAction == 'buy'){
                var maxItemDiff = 99 - parseInt($itemCell.find('.item_quantity').attr('data-quantity') || 0);
                var maxQuantity = Math.floor(thisShopData.zennyCounter / unitPrice);
                if (maxQuantity > maxItemDiff){ maxQuantity = maxItemDiff; }
                //console.log('currentQuantity =', currentQuantity, 'maxItemDiff = ', maxItemDiff, 'maxQuantity = ', maxQuantity);
                }
            return [currentQuantity, maxQuantity];
            };

        // Collect a reference to all the mod butons so we loop through them
        var $modButtons = $('a[data-inc],a[data-dec]', $itemCellConfirm);

        // Collect temp and max quantity values
        var tempValues = getCurrentAndMax();
        var currentQuantity = tempValues[0];
        var maxQuantity = tempValues[1];
        //console.log('currentQuantity =', currentQuantity, 'maxQuantity = ', maxQuantity);

        // Add the disabled class to all buttons, then remove where applicable
        $modButtons.addClass('disabled');
        if ((currentQuantity + 1) <= maxQuantity){ $modButtons.filter('[data-inc]').removeClass('disabled'); }
        if (currentQuantity > 1){ $modButtons.filter('[data-dec]').removeClass('disabled'); }

        // Bind the click action to each of the mod buttonsw with functionality
        $modButtons.unbind('click').bind('click', function(e){
            e.preventDefault();
            var $button = $(this);
            if ($button.hasClass('disabled')){ return false; }
            var modKind = $button.is('[data-inc]') ? 'inc' : 'dec';
            var modAmount = parseInt($button.attr('data-'+modKind));
            var tempValues = getCurrentAndMax();
            var currentQuantity = tempValues[0];
            var maxQuantity = tempValues[1];
            var newQuantity = modKind == 'inc' ? (currentQuantity + modAmount) : (currentQuantity - modAmount);
            //console.log('currentQuantity =', currentQuantity, 'maxQuantity = ', maxQuantity, 'newQuantity = ', newQuantity);
            if (newQuantity > maxQuantity){ newQuantity = maxQuantity; }
            else if (newQuantity < 1){ newQuantity = 1; }
            var newPrice = newQuantity * unitPrice;
            $itemCellConfirm.attr('data-quantity', newQuantity).attr('data-price', newPrice);
            $itemCellConfirm.find('.item_quantity').attr('data-quantity', newQuantity).html('x '+newQuantity);
            $itemCellConfirm.find('.item_price').attr('data-price', newPrice).html('&hellip; '+printNumberWithCommas(newPrice)+'z');
            //console.log('currentQuantity is '+currentQuantity+' | '+modKind+' by '+modAmount+' | '+newQuantity);
            $modButtons.removeClass('disabled');
            if (newQuantity >= maxQuantity){ $modButtons.filter('[data-inc]').addClass('disabled'); }
            if (newQuantity <= 1){ $modButtons.filter('[data-dec]').addClass('disabled'); }
            });

        };

    // Functionality to the sell links for all shops
    $('.item_cell[data-token][data-action="sell"] .sell_button', gameConsole).live('click', function(e){
        e.preventDefault();
        if (!thisShopData.allowEdit){ return false; }
        var thisButton = $(this);
        var thisCell = thisButton.parent();
        var thisSeller = thisButton.parents('.event[data-token]').attr('data-token');
        var thisTab = thisCell.parents('.tab_container[data-tab]');
        var thisKind = thisCell.attr('data-kind');
        var thisAction = thisCell.attr('data-action');
        var thisToken = thisCell.attr('data-token');
        var thisQuantity = thisCell.find('.item_quantity').attr('data-quantity') || 0;
        var thisPrice = thisCell.find('.item_price').attr('data-price') || 0;
        thisQuantity = parseInt(thisQuantity);
        thisPrice = parseInt(thisPrice);
        var sellQuantity = 1;
        var sellPrice = thisPrice;
        var thisDisabled = thisCell.hasClass('item_cell_disabled') ? true : false;
        if (thisDisabled){ return false; }
        var thisItemName = thisCell.find('.item_name').clone();
        //console.log(thisSeller+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / x'+thisQuantity+' / '+thisPrice+'z');
        var itemCellConfirm = $('.item_cell_confirm', thisTab);
        itemCellConfirm.removeClass('with_mods');
        if (thisKind == 'item'){ itemCellConfirm.addClass('with_mods'); }
        if (itemCellConfirm.attr('data-token') == thisToken){
            var sellQuantity = parseInt(itemCellConfirm.attr('data-quantity')) + 1;
            if (sellQuantity > thisQuantity){ sellQuantity = thisQuantity; }
            var sellPrice = sellQuantity * parseInt(thisPrice);
            itemCellConfirm.attr('data-quantity', sellQuantity).attr('data-price', sellPrice);
            itemCellConfirm.find('.item_quantity').attr('data-quantity', sellQuantity).html('x '+sellQuantity);
            itemCellConfirm.find('.item_price').attr('data-price', sellPrice).html('&hellip; '+printNumberWithCommas(sellPrice)+'z');
            } else {
            itemCellConfirm.empty();
            itemCellConfirm.attr('data-kind', thisKind).attr('data-action', thisAction).attr('data-token', thisToken).attr('data-price', thisPrice).attr('data-quantity', sellQuantity).attr('data-shop', thisSeller);
            itemCellConfirm.append('<a class="cancel_button ability_type ability_type_attack" href="#">Cancel</a>');
            itemCellConfirm.append('<a class="confirm_button ability_type ability_type_energy" href="#">Confirm</a>');
            itemCellConfirm.append('<label class="item_price" data-price="'+sellPrice+'">&hellip; '+printNumberWithCommas(sellPrice)+'z</label>');
            if (thisKind == 'item'){
                itemCellConfirm.append('<label class="item_quantity_mods">'
                    + '<a class="inc ability_type ability_type_none" data-inc="1"><i>+1</i></a>'
                    + '<a class="inc ability_type ability_type_none" data-inc="10"><i>+10</i></a>'
                    + '</label>');
                itemCellConfirm.append('<label class="item_quantity" data-quantity="'+sellQuantity+'">x '+sellQuantity+'</label>');
                itemCellConfirm.append('<label class="item_quantity_mods">'
                    + '<a class="dec ability_type ability_type_none disabled" data-dec="10"><i>-10</i></a>'
                    + '<a class="dec ability_type ability_type_none disabled" data-dec="1"><i>-1</i></a>'
                    + '</label>');
                } else {
                itemCellConfirm.append('<label class="item_quantity" data-quantity="'+sellQuantity+'">x '+sellQuantity+'</label>');
                }
            itemCellConfirm.append(thisItemName);
            }
        if (thisKind == 'item'){
            generateQuantityModEvents(itemCellConfirm, thisAction);
            }
        if (thisKind == 'star'){
            //console.log('We should auto-confirm stars being shown!');
            thisCell.addClass('item_cell_disabled');
            itemCellConfirm.find('a.cancel_button').remove();
            itemCellConfirm.find('a.confirm_button').html('Scanning&hellip;').triggerSilentClick();
            }
        return true;
        });

    // Functionality to the buy links for all shops
    $('.item_cell[data-token][data-action="buy"] .buy_button', gameConsole).live('click', function(e){
        e.preventDefault();
        if (!thisShopData.allowEdit){ return false; }
        var thisButton = $(this);
        var thisCell = thisButton.parent();
        var thisBuyer = thisButton.parents('.event[data-token]').attr('data-token');
        var thisTab = thisCell.parents('.tab_container[data-tab]');
        var thisKind = thisCell.attr('data-kind');
        var thisAction = thisCell.attr('data-action');
        var thisToken = thisCell.attr('data-token');
        var thisQuantity = thisCell.find('.item_quantity').attr('data-quantity') || 0;
        var thisPrice = thisCell.find('.item_price').attr('data-price') || 0;
        thisQuantity = parseInt(thisQuantity);
        thisPrice = parseInt(thisPrice);
        var buyQuantity = 1;
        var buyPrice = thisPrice;
        var thisDisabled = thisCell.hasClass('item_cell_disabled') ? true : false;
        if (thisDisabled){ return false; }
        var thisItemName = thisCell.find('.item_name').clone();
        //console.log(thisBuyer+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / '+thisPrice+'z');
        var itemCellConfirm = $('.item_cell_confirm', thisTab);
        itemCellConfirm.removeClass('with_mods');
        if (thisKind == 'item'){ itemCellConfirm.addClass('with_mods'); }
        if (thisKind == 'ability'){
            var actualQuantity = 1;
            var actualPrice = buyPrice * actualQuantity;
            var thisUnlocked = thisCell.attr('data-unlocked').split(',');
            //console.log('thisUnlocked', thisUnlocked);
            //console.log('ABILITY-A) '+thisBuyer+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / x'+actualQuantity+' / '+thisPrice+'z '+' / '+(thisUnlocked ? 'Unlocked' : 'Not Unlocked'));
            itemCellConfirm.empty();
            itemCellConfirm.attr('data-kind', thisKind).attr('data-action', thisAction).attr('data-token', thisToken).attr('data-price', thisPrice).attr('data-quantity', buyQuantity).attr('data-shop', thisBuyer).attr('data-player', 'all');
            itemCellConfirm.append('<a class="cancel_button ability_type ability_type_attack" href="#">Cancel</a>');
            itemCellConfirm.append('<a class="confirm_button ability_type ability_type_energy" href="#">Confirm</a>');
            var buttonCounter = 0;
            var buttonMarkup = [];
            buttonMarkup.reverse();
            for (i in buttonMarkup){
                var newButton = $(buttonMarkup[i]);
                if (i == (buttonMarkup.length - 1)){ newButton.css({marginLeft:'20px'}); }
                newButton.appendTo(itemCellConfirm);
                }
            itemCellConfirm.append('<label class="item_price" data-price="'+actualPrice+'">&hellip; '+printNumberWithCommas(actualPrice)+'z</label>');
            itemCellConfirm.append('<label class="item_quantity" data-quantity="'+buyQuantity+'">x '+buyQuantity+'</label>');
            itemCellConfirm.append(thisItemName);
            } else if (thisKind == 'item'){
            if (itemCellConfirm.attr('data-token') == thisToken){
                buyQuantity = parseInt(itemCellConfirm.attr('data-quantity')) + 1;
                //console.log('ITEM-A) '+thisBuyer+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / x'+buyQuantity+' / '+thisPrice+'z');
                buyPrice = buyQuantity * parseInt(thisPrice);
                if (buyQuantity + thisQuantity > 99){
                    //console.log('buyQuantity + thisQuantity > 99 | '+buyQuantity+' + '+thisQuantity+' > 99');
                    buyQuantity = 99 - thisQuantity;
                    buyPrice = buyQuantity * parseInt(thisPrice);
                    } else if (buyPrice > thisShopData.zennyCounter){
                    //console.log('buyPrice > thisShopData.zennyCounter | '+buyPrice+' > '+thisShopData.zennyCounter+' ');
                    buyQuantity = buyQuantity - 1;
                    buyPrice = buyQuantity * parseInt(thisPrice);
                    }
                itemCellConfirm.attr('data-quantity', buyQuantity).attr('data-price', buyPrice);
                itemCellConfirm.find('.item_quantity').attr('data-quantity', buyQuantity).html('x '+buyQuantity);
                itemCellConfirm.find('.item_price').attr('data-price', buyPrice).html('&hellip; '+printNumberWithCommas(buyPrice)+'z');
                } else {
                //console.log('ITEM-B) '+thisBuyer+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / x'+buyQuantity+' / '+thisPrice+'z');
                itemCellConfirm.empty();
                itemCellConfirm.attr('data-kind', thisKind).attr('data-action', thisAction).attr('data-token', thisToken).attr('data-price', thisPrice).attr('data-quantity', buyQuantity).attr('data-shop', thisBuyer);
                itemCellConfirm.append('<a class="cancel_button ability_type ability_type_attack" href="#">Cancel</a>');
                itemCellConfirm.append('<a class="confirm_button ability_type ability_type_energy" href="#">Confirm</a>');
                itemCellConfirm.append('<label class="item_price" data-price="'+buyPrice+'">&hellip; '+printNumberWithCommas(buyPrice)+'z</label>');
                itemCellConfirm.append('<label class="item_quantity_mods">'
                    + '<a class="inc ability_type ability_type_none" data-inc="1"><i>+1</i></a>'
                    + '<a class="inc ability_type ability_type_none" data-inc="10"><i>+10</i></a>'
                    + '</label>');
                itemCellConfirm.append('<label class="item_quantity" data-quantity="'+buyQuantity+'">x '+buyQuantity+'</label>');
                itemCellConfirm.append('<label class="item_quantity_mods">'
                    + '<a class="dec ability_type ability_type_none disabled" data-dec="10"><i>-10</i></a>'
                    + '<a class="dec ability_type ability_type_none disabled" data-dec="1"><i>-1</i></a>'
                    + '</label>');
                itemCellConfirm.append(thisItemName);
                }
            } else {
            //console.log('OTHER-A) '+thisBuyer+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / x'+buyQuantity+' / '+thisPrice+'z');
            itemCellConfirm.empty();
            itemCellConfirm.attr('data-kind', thisKind).attr('data-action', thisAction).attr('data-token', thisToken).attr('data-price', thisPrice).attr('data-quantity', buyQuantity).attr('data-shop', thisBuyer);
            itemCellConfirm.append('<a class="cancel_button ability_type ability_type_attack" href="#">Cancel</a>');
            itemCellConfirm.append('<a class="confirm_button ability_type ability_type_energy" href="#">Confirm</a>');
            itemCellConfirm.append('<label class="item_price" data-price="'+buyPrice+'">&hellip; '+printNumberWithCommas(buyPrice)+'z</label>');
            itemCellConfirm.append('<label class="item_quantity" data-quantity="'+buyQuantity+'">x '+buyQuantity+'</label>');
            itemCellConfirm.append(thisItemName);
            }
        if (thisKind == 'item'){
            generateQuantityModEvents(itemCellConfirm, thisAction);
            }
        return true;
        });

    // Functionality to the cancel links for all shops
    $('.item_cell_confirm .cancel_button', gameConsole).live('click', function(e){
        e.preventDefault();
        if (!thisShopData.allowEdit){ return false; }
        var thisButton = $(this);
        var thisConfirmCell = thisButton.parent();
        thisConfirmCell.attr('data-kind', '').attr('data-action', '').attr('data-token', '').attr('data-price', '').attr('data-quantity', '').attr('data-player', '');
        thisConfirmCell.empty().html('<div class="placeholder">&hellip;</div>');
        //console.log('cancel_button:click');
        return true;
        });

    // Functionality to the confirm links for all shops
    $('.item_cell_confirm .confirm_button', gameConsole).live('click', function(e){
        e.preventDefault();
        if (!thisShopData.allowEdit){ return false; }
        var thisButton = $(this);
        var thisConfirmCell = thisButton.parent();
        var thisKeeper = thisConfirmCell.attr('data-shop');
        var thisKind = thisConfirmCell.attr('data-kind');
        var thisAction = thisConfirmCell.attr('data-action');
        var thisToken = thisConfirmCell.attr('data-token');
        var thisQuantity = thisConfirmCell.find('.item_quantity').attr('data-quantity') || 0;
        var thisPrice = thisConfirmCell.find('.item_price').attr('data-price') || 0;
        var thisPlayer = thisConfirmCell.attr('data-player') || '';
        if (thisButton.hasClass('confirm_button_disabled')){ return false; }
        var thisTab = thisButton.parents('.tab_container');
        var thisItemCell = $('.item_cell[data-token='+thisToken+']', thisTab);
        //console.log('confirm_button: click / '+thisKeeper+' / '+thisKind+' / '+thisAction+' / '+thisToken+' / x'+thisQuantity+' / '+thisPrice+'z / '+thisPlayer+'');

        var $shopDiv = thisTab.closest('.event[data-token="'+thisKeeper+'"]');
        var $shopSprite = $('> .this_sprite', $shopDiv);
        //console.log('$shopDiv = ', $shopDiv.length, $shopDiv);
        //console.log('$shopSprite = ', $shopSprite.length, $shopSprite);

        // Define the post options for the ajax call
        var postData = {shop:thisKeeper,kind:thisKind,action:thisAction,token:thisToken,quantity:thisQuantity,price:thisPrice,player:thisPlayer};
        thisConfirmCell.css({opacity:0.3});
        thisShopData.allowEdit = false;
        $shopSprite.addClass('pending');

        // Post the sort request to the server
        $.ajax({
            type: 'POST',
            url: 'frames/shop.php',
            data: postData,
            success: function(data, status){

                // If the `data` is multi-line, immediately break off anything after the first for later into a `dataExtra` var
                //console.log('data =', data);
                var newlineIndex = data.indexOf("\n");
                var dataExtra = newlineIndex !== -1 ? data.substr(newlineIndex + 1) : false;
                data = newlineIndex !== -1 ? data.substr(0, newlineIndex) : data;
                //console.log('data (after) =', data);
                //console.log('dataExtra =', dataExtra);

                // Break apart the response into parts
                var data = data.split('|');
                var dataStatus = data[0] != undefined ? data[0] : false;
                var dataMessage = data[1] != undefined ? data[1] : false;

                // Check the status of the response and respond
                if (dataStatus == 'error'){
                    //console.log('error');
                    //console.log(data);

                    thisShopData.allowEdit = true;
                    return false;

                    } else if (dataStatus == 'success'){
                    //console.log('success');
                    //console.log(data);

                    // Update this item's global quantity
                    var newItemCount = data[2] != undefined ? parseInt(data[2]) : false;
                    var newZennyTotal = data[3] != undefined ? parseInt(data[3]) : false;
                    var newPointsTotal = data[4] != undefined && data[4].indexOf('points:') !== -1 ? parseInt(data[4].replace('points:', '')) : false;
                    var newLeaderboardRank = data[5] != undefined && data[5].indexOf('rank:') !== -1 ? data[5].replace('rank:', '') : false;
                    var zennyDifference = Math.abs(newZennyTotal - thisShopData.zennyCounter);
                    //console.log({newItemCount:newItemCount,newZennyTotal:newZennyTotal,newPointsTotal:newPointsTotal,newLeaderboardRank:newLeaderboardRank});

                    // Define the change text
                    if (thisAction == 'buy'){ var thisChangeText = '<span class="zenny" style="color: #C35E5E;">-'+printNumberWithCommas(thisPrice)+'z</span>'; }
                    else if (thisAction == 'sell'){ var thisChangeText = '<span class="zenny" style="color: #8CEB80;">+'+printNumberWithCommas(thisPrice)+'z</span>'; }

                    // Empty then unhide the parent cell
                    thisConfirmCell.empty().attr('data-kind', '').attr('data-action', '').attr('data-token', '').attr('data-quantity', '').attr('data-price', '');
                    thisConfirmCell.append('<div class="success">Success! '+thisChangeText+'</div>');

                    thisShopData.itemQuantities[thisToken] = newItemCount;
                    thisShopData.zennyCounter = newZennyTotal;

                    /*
                    if (thisKind == 'ability' && thisAction == 'buy'){
                        //thisShopData.itemQuantities[thisAction][thisToken] += 1;
                        var unlockedPlayers = thisItemCell.attr('data-unlocked');
                        //console.log('successful buy of ability data unlocked for '+thisToken); //+' / '+unlockedPlayers);
                        unlockedPlayers = unlockedPlayers.split(',');
                        unlockedPlayers.push(thisPlayer);
                        unlockedPlayers = unlockedPlayers.join(',');
                        thisItemCell.attr('data-unlocked', unlockedPlayers);
                        }
                        */

                    var thisZennyFormatted = printNumberWithCommas(newZennyTotal);
                    $('#zenny_counter', thisBody).css({color:'#8CEB80'}).html(thisZennyFormatted);
                    if (window.self !== window.parent){
                        parent.prototype_update_zenny(thisZennyFormatted+' z');
                        if (newPointsTotal !== false){
                            var newPointsTotalFormatted = printNumberWithCommas(newPointsTotal);
                            parent.prototype_update_battle_points(newPointsTotalFormatted);
                            }
                        if (newLeaderboardRank !== false){
                            parent.prototype_update_leaderboard_rank(newLeaderboardRank);
                            }
                        }

                    updateItemCells();

                    // If we're allowed to, play a sound effect
                    if (typeof parent.mmrpg_play_sound_effect !== 'undefined'){
                        playSoundEffect('shop-success', {volume: 1.0});
                        playSoundEffect('zenny-spent', {volume: 1.0});
                        var loops = Math.ceil(zennyDifference / 1000);
                        for (var i = 0; i < loops; i++){
                            setTimeout(function(){
                                playSoundEffect('zenny-spent', {volume: 1.0});
                                }, 100 + (100 * i));
                            }
                        }

                    // Animate the shopkeeper briefly by putting them into their victory frames
                    $shopSprite.removeClass('pending').addClass('success');
                    setTimeout(function(){
                        $shopSprite.removeClass('success');
                        }, 1000);

                    // Animate the cell to show that an action has been completed
                    thisConfirmCell.stop().animate({opacity:1.0},300,'swing',function(){
                        thisConfirmCell.animate({opacity:0.3},600,'swing',function(){
                            $(this).css({opacity:1.0}).empty().append('<div class="placeholder">&hellip;</div>');
                            $('#zenny_counter', thisBody).css({color:''});
                            //console.log('we just completed an action... postData = ', postData);

                            // If the completed action was buying a new robot, refresh page
                            if (postData.kind === 'robot'
                                && postData.action === 'buy'
                                && postData.quantity >= 1){

                                // We have a popup now so editing is totally allowed
                                thisShopData.allowEdit = true;

                                // We should add the new robot to the parent ready room
                                if (typeof window.parent.mmrpgReadyRoom !== 'undefined'
                                    && typeof window.parent.mmrpgReadyRoom.addRobot !== 'undefined'){
                                    // If the extra data in dataExtra was not empty and is JSON, parse it into robotInfo
                                    var readyRoom = window.parent.mmrpgReadyRoom;
                                    var newRobotToken = postData.token;
                                    newRobotToken = newRobotToken.replace(/^robot-/i, '');
                                    var newRobotInfo = {};
                                    if (dataExtra !== false){
                                        try {
                                            newRobotInfo = JSON.parse(dataExtra);
                                            }
                                        catch (e) {
                                            //console.log('error parsing robotInfo JSON: ', e);
                                            }
                                        }
                                    //console.log('newRobotToken = ', newRobotToken);
                                    //console.log('newRobotInfo = ', newRobotInfo);
                                    if (typeof newRobotInfo.token !== 'undefined'
                                        && newRobotInfo.token === newRobotToken){
                                        //console.log('newRobotToken(', newRobotToken, ') === newRobotInfo.token(', newRobotInfo.token, ')');
                                        readyRoom.addRobot(newRobotToken, newRobotInfo, true);
                                        }
                                    }

                                }
                            // Otherwise turn editing back on and process other actions
                            else {

                                // Allow editing again for the user now that complete
                                thisShopData.allowEdit = true;

                                // Update other relavant UI in the background

                                // If the completed action was selling an elemental core, update guage
                                if (postData.kind === 'item'
                                    && postData.action === 'sell'
                                    && postData.quantity >= 1
                                    && postData.token.match(/-core$/)){
                                        //console.log('we just sold a core! ', postData);
                                        var coreType = postData.token.replace(/-core$/, '');
                                        var $eventWrap = thisConfirmCell.closest('.event[data-token]');
                                        var $coreGauge = $eventWrap.find('.gauge.cores');
                                        var $coreWrap = $coreGauge.find('.element[data-type="'+coreType+'"]');
                                        if ($coreWrap.length){
                                            //console.log('coreType =', coreType);
                                            //console.log('$eventWrap = ', $eventWrap.length, '$coreGauge = ', $coreGauge.length, '$coreWrap =', $coreWrap.length);
                                            var oldCount = parseInt($coreWrap.attr('data-count'));
                                            var newCount = oldCount + parseInt(postData.quantity);
                                            var maxCount = parseInt($coreWrap.attr('data-max-count'));
                                            //console.log('oldCount =', oldCount, 'newCount =', newCount);
                                            $coreWrap.attr('data-count', newCount);
                                            $coreWrap.attr('data-click-tooltip', $coreWrap.attr('data-click-tooltip').replace(/\s[0-9]+$/, ' '+newCount));
                                            $coreWrap.css({opacity:(0.1 + ((newCount < 3 ? newCount / 3 : 1) * 0.9))});
                                            if (newCount < maxCount){ $coreWrap.find('.count').html(newCount); }
                                            else { $coreWrap.find('.count').html('&bigstar;'); }
                                            }
                                    }


                                }


                            });
                        });

                    // Make sure we always poll the server for popup events after our action
                    //console.log('queuing the windowEventsPull event (via shop)');
                    if (typeof window.top.mmrpg_queue_for_game_start !== 'undefined'){
                        window.top.mmrpg_queue_for_game_start(function(){
                            //console.log('i guess the game has started');
                            setTimeout(function(){ parent.windowEventsPull(); }, 1000);
                            });
                        }
                    else if (typeof window.top.windowEventsPull !== 'undefined'){
                        //console.log('i guess we pull events manually');
                        setTimeout(function(){ parent.windowEventsPull(); }, 1000);
                        }

                    return true;

                    } else {

                    //console.log('ummmm');
                    //console.log(data);


                    thisShopData.allowEdit = true;
                    return false;

                    }

                // DEBUG
                //alert('dataStatus = '+dataStatus+', dataMessage = '+dataMessage+', dataContent = '+dataContent+'; ');

                }
            });

        return true;
        });

    // Append the markup after load to prevent halting display and waiting shops
    $('#console #shops').append(shopConsoleMarkup);
    $('#canvas #links').append(shopCanvasMarkup);

    // Attach the scrollbar to the battle events container
    $('#console .scroll_wrapper', thisShop).perfectScrollbar({suppressScrollX: true, scrollYMarginOffset: 6});

    // Automatically click the first shop link
    if (lastShopToken.length){
        var $lastShop = $('#canvas #links .sprite[data-token="'+lastShopToken[0]+'"]');
        $lastShop.triggerSilentClick();
        } else {
        var $firstShop = $('#canvas #links .sprite[data-token]').first();
        $firstShop.triggerSilentClick();
        }


    // Update the scrollbar to make sure it's sized correctly
    //console.log('updating perfect scrollbar 3');
    $('#console .scroll_wrapper', thisShop).perfectScrollbar('update');

    // Update all the item cells automatically
    updateItemCells();


    /*
     * OTHER STUFF
     */

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
            //console.log('updating perfect scrollbar 4');
            $('#console .scroll_wrapper', thisShop).perfectScrollbar('update');
            // Let the parent window know the menu has loaded
            parent.prototype_menu_loaded();
            }, 1000);
        }, false, true);

});

// Define a function for refreshing all items
function updateItemCells(){
    var itemCells = $('.item_cell[data-token]', gameConsole);
    //console.log('updateItems('+itemCells.length+')');
    //console.log('thisShopData', thisShopData);
    itemCells.each(function(index, value){ $(this).removeClass('item_cell_disabled'); });
    updateItemQuantities();
    updateItemPrices();
}

// Define a function for updating item quantities
function updateItemQuantities(){
    //console.log('updateItemQuantities()');
    for (var itemToken in thisShopData.itemQuantities){
        var itemQuantity = thisShopData.itemQuantities[itemToken];
        updateItemQuantity(itemToken, itemQuantity);
    }
}
// Define a function for updating a single item's quantity
function updateItemQuantity(itemToken, itemQuantity){
    var itemCells = $('.item_cell[data-token='+itemToken+']', gameConsole);
    itemCells.each(function(index, value){
        var thisCell = $(this);
        var thisKind = thisCell.attr('data-kind');
        var thisAction = thisCell.attr('data-action');
        //console.log('updateItemQuantity('+thisKind+' / '+thisAction+' / '+itemToken+' / '+itemQuantity+' / '+(thisCell.hasClass('item_cell_disabled') ? 'item_cell_disabled' : 'item_cell_enabled')+')');
        if (thisKind == 'item'){

            thisCell.find('label[data-quantity]').attr('data-quantity', itemQuantity).html('x '+itemQuantity);
            if (thisAction == 'buy' && itemQuantity >= 99){ thisCell.addClass('item_cell_disabled');  }
            else if (thisAction == 'sell' && itemQuantity <= 0){ thisCell.addClass('item_cell_disabled');  }

            } else if (thisKind == 'ability'){

            thisCell.find('label[data-quantity]').attr('data-quantity', itemQuantity).html('&nbsp;');
            if (itemQuantity < 0){ thisCell.addClass('item_cell_disabled').find('label[data-quantity]').html('&nbsp;'); }
            else if (itemQuantity >= 1){ thisCell.addClass('item_cell_disabled').find('label[data-quantity]').html('&#10004;'); }

            } else if (thisKind == 'field' || thisKind == 'robot'){

            if (itemQuantity >= 1){ thisCell.addClass('item_cell_disabled');  }

            thisCell.find('label[data-quantity]').attr('data-quantity', itemQuantity).html('&nbsp;');
            if (itemQuantity < 0){ thisCell.addClass('item_cell_disabled').find('label[data-quantity]').html('&nbsp;'); }
            else if (itemQuantity >= 1){ thisCell.addClass('item_cell_disabled').find('label[data-quantity]').html('&#10004;'); }

            } else if (thisKind == 'alt'){

            if (itemQuantity >= 1){ thisCell.addClass('item_cell_disabled');  }

            thisCell.find('label[data-quantity]').attr('data-quantity', itemQuantity).html('&nbsp;');
            if (itemQuantity < 0){ thisCell.addClass('item_cell_disabled').find('label[data-quantity]').html('&nbsp;'); }
            else if (itemQuantity >= 1){ thisCell.addClass('item_cell_disabled').find('label[data-quantity]').html('&#10004;'); }

            } else if (thisKind == 'star'){

            if (itemQuantity < 1){ thisCell.addClass('item_cell_disabled');  }

            }
        });
    return true;
}


// Define a function for updating item prices
function updateItemPrices(){
    //console.log('updateItemPrices()');
    for (var itemAction in thisShopData.itemPrices){
        //console.log('var itemListArray = thisShopData.itemPrices['+itemAction+'];');
        var itemListArray = thisShopData.itemPrices[itemAction];
        for (var itemToken in itemListArray){
            //console.log('var itemPrice = thisShopData.itemPrices['+itemAction+']['+itemToken+'];');
            var itemPrice = thisShopData.itemPrices[itemAction][itemToken];
            updateItemPrice(itemAction, itemToken, itemPrice);
        }
    }
}
// Define a function for updating a single item's price
function updateItemPrice(itemAction, itemToken, itemPrice){
    var itemCells = $('.item_cell[data-action='+itemAction+'][data-token='+itemToken+']', gameConsole);
    itemCells.each(function(index, value){
        var thisCell = $(this);
        var thisKind = thisCell.attr('data-kind');
        var thisAction = thisCell.attr('data-action');
        //console.log('updateItemPrice('+thisKind+' / '+thisAction+' / '+itemToken+' / '+itemPrice+' / '+(thisCell.hasClass('item_cell_disabled') ? 'item_cell_disabled' : 'item_cell_enabled')+')');
        var thisLabel = thisCell.find('label[data-price]');
        if (thisLabel != undefined){ thisLabel.attr('data-price', itemPrice).html('&hellip; '+printNumberWithCommas(itemPrice)+'z'); }
        if (thisAction == 'buy' && itemPrice > thisShopData.zennyCounter){ thisCell.addClass('item_cell_disabled');  }
        else if (thisAction == 'sell' && itemPrice <= 0){ thisCell.addClass('item_cell_disabled');  }
        });
    return true;
}

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

    //console.log('windowWidth = '+windowWidth+'; parentWidth = '+parentWidth+'; thisTypeContainerWidth = '+thisTypeContainerWidth+'; thisStarContainerWidth = '+thisStarContainerWidth+'; ');

}

// Define a function for printing a number with commas as thousands separators
function printNumberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}