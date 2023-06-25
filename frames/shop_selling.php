<?

// -- SELLING MARKUP -- //

// Loop through the selling tokens and display tabs for them
foreach ($shop_selling_tokens AS $selling_token){

    ?>

        <div class="tab_container tab_container_selling" data-tab="selling" data-tab-type="<?= $selling_token ?>">

            <div class="shop_quote shop_quote_selling">&quot;<?= isset($shop_info['shop_quote_selling'][$selling_token]) ? $shop_info['shop_quote_selling'][$selling_token] : $shop_info['shop_quote_selling']  ?>&quot;</div>

            <?

            // If this shop has items to sell, print them out
            if (
                ($selling_token == 'items' && !empty($shop_info['shop_items']['items_selling'])) ||
                ($selling_token == 'parts' && !empty($shop_info['shop_parts']['parts_selling'])) ||
                ($selling_token == 'cores' && !empty($shop_info['shop_cores']['cores_selling']))
                ){

                // Include the selling markup for items and cores
                require(MMRPG_CONFIG_ROOTDIR.'frames/shop_selling_items.php');

            }
            // If this shop has abilities to sell, print them out
            elseif (
                ($selling_token == 'abilities' && !empty($shop_info['shop_abilities']['abilities_selling'])) ||
                ($selling_token == 'weapons' && !empty($shop_info['shop_weapons']['weapons_selling']))
                ){

                // Include the selling markup for support abilities and special weapons
                require(MMRPG_CONFIG_ROOTDIR.'frames/shop_selling_abilities.php');

            }
            // If this shop has robots to sell, print them out
            elseif (
                ($selling_token == 'robots' && !empty($shop_info['shop_robots']['robots_selling']))
                ){

                // Include the selling markup for robot masters to fight battles
                require(MMRPG_CONFIG_ROOTDIR.'frames/shop_selling_robots.php');

            }
            // If this shop has alts to sell, print them out
            elseif (
                ($selling_token == 'alts' && !empty($shop_info['shop_alts']['alts_selling']))
                ){

                // Include the selling markup for robot alternate images
                require(MMRPG_CONFIG_ROOTDIR.'frames/shop_selling_alts.php');

            }

            ?>

            <table class="full" style="margin-bottom: 5px;">
                <colgroup>
                    <col width="100%" />
                </colgroup>
                <tbody>
                    <tr>
                        <td colspan="2" class="left item_cell_confirm" data-kind="" data-action="" data-token="" data-price="" data-quantity="">
                            <div class="placeholder">&hellip;</div>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    <?

}

?>