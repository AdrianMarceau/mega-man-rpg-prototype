<?

// -- SHOP BUYING ITEMS -- //

?>

<table class="full" style="margin-bottom: 5px;">
    <colgroup>
        <col width="50%" />
        <col width="50%" />
    </colgroup>
    <thead>
        <tr>
            <th class="left">
                <span class="sell_button sell_button_header">&nbsp;</span>
                <label class="item_quantity item_quantity_header">Own</label>
                <label class="item_price item_price_header">Sell</label>
            </th>
            <th class="right">
                <span class="sell_button sell_button_header">&nbsp;</span>
                <label class="item_quantity item_quantity_header">Own</label>
                <label class="item_price item_price_header">Sell</label>
            </th>
        </tr>
    </thead>
</table>

<div class="scroll_wrapper">

    <table class="full" style="margin-bottom: 5px;">
        <colgroup>
            <col width="50%" />
            <col width="50%" />
        </colgroup>
        <tbody>
            <tr>

                <?

                // Collect the items for buying and slice/shuffle if nessary
                $item_list_array = $shop_info['shop_items']['items_buying'];

                // Loop through the items and print them one by one
                $item_counter = 0;
                foreach ($item_list_array AS $token => $price){
                    if (isset($mmrpg_database_items[$token])){ $item_info = $mmrpg_database_items[$token]; }
                    else { continue; }

                    $item_counter++;
                    $item_info_token = $token;
                    $item_info_price = $price;
                    $item_info_name = '<span class="wrap">'.$item_info['item_name'].'</span>';
                    $item_info_quantity = !empty($_SESSION[$session_token]['values']['battle_items'][$token]) ? $_SESSION[$session_token]['values']['battle_items'][$token] : 0;
                    $item_info_type = !empty($item_info['item_type']) ? $item_info['item_type'] : 'none';
                    if ($item_info_type != 'none' && !empty($item_info['item_type2'])){ $item_info_type .= '_'.$item_info['item_type2']; }
                    elseif ($item_info_type == 'none' && !empty($item_info['item_type2'])){ $item_info_type = $item_info['item_type2']; }

                    $global_item_quantities[$item_info_token] = $item_info_quantity;
                    $global_item_prices['sell'][$item_info_token] = $item_info_price;

                    $item_cell_float = $item_counter % 2 == 0 ? 'right' : 'left';
                    $temp_info_tooltip = rpg_item::print_editor_title_markup($robot_info, $item_info, array('show_quantity' => false));
                    $temp_info_tooltip = htmlentities($temp_info_tooltip, ENT_QUOTES, 'UTF-8', true);

                    $item_sprite_image = !empty($item_info['item_image']) ? $item_info['item_image'] : $item_info['item_token'];
                    $item_sprite_image_markup = '<span class="icon_sprite"><span class="sprite sprite_40x40 sprite_40x40_00" style="background-image: url(images/items/'.$item_sprite_image.'/icon_right_40x40.png?'.MMRPG_CONFIG_CACHE_DATE.');"></span></span>';
                    $item_info_name = $item_sprite_image_markup.'<span class="wrap">'.$item_info_name.'</span>';

                    ?>
                        <td class="<?= $item_cell_float ?> item_cell" data-kind="item" data-action="sell" data-token="<?= $item_info_token ?>">
                            <span class="item_name ability_type ability_type_<?= $item_info_type ?>" data-click-tooltip="<?= $temp_info_tooltip ?>"><?= $item_info_name ?></span>
                            <a class="sell_button ability_type ability_type_none" href="#">Sell</a>
                            <label class="item_quantity" data-quantity="0">x 0</label>
                            <label class="item_price" data-price="<?= $item_info_price ?>">&hellip; <?= $item_info_price ?>z</label>
                        </td>
                    <?

                    if ($item_cell_float == 'right'){ echo '</tr><tr>'; }

                }

                if ($item_counter % 2 != 0){
                    ?>
                        <td class="right item_cell item_cell_disabled">
                            &nbsp;
                        </td>
                    <?
                }

                ?>

            </tr>
        </tbody>
    </table>

</div>