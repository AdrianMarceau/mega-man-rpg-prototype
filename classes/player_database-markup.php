<?

// Define the global variables
global $DB;
global $mmrpg_index, $this_current_uri, $this_current_url;
global $mmrpg_database_players, $mmrpg_database_robots, $mmrpg_database_abilities, $mmrpg_database_types;

// Define the print style defaults
if (!isset($print_options['layout_style'])){ $print_options['layout_style'] = 'website'; }
if ($print_options['layout_style'] == 'website'){
  if (!isset($print_options['show_basics'])){ $print_options['show_basics'] = true; }
  if (!isset($print_options['show_mugshot'])){ $print_options['show_mugshot'] = true; }
  if (!isset($print_options['show_quotes'])){ $print_options['show_quotes'] = true; }
  if (!isset($print_options['show_description'])){ $print_options['show_description'] = true; }
  if (!isset($print_options['show_sprites'])){ $print_options['show_sprites'] = true; }
  if (!isset($print_options['show_abilities'])){ $print_options['show_abilities'] = true; }
  if (!isset($print_options['show_records'])){ $print_options['show_records'] = true; }
  if (!isset($print_options['show_footer'])){ $print_options['show_footer'] = true; }
  if (!isset($print_options['show_key'])){ $print_options['show_key'] = false; }
} elseif ($print_options['layout_style'] == 'website_compact'){
  if (!isset($print_options['show_basics'])){ $print_options['show_basics'] = true; }
  if (!isset($print_options['show_mugshot'])){ $print_options['show_mugshot'] = true; }
  if (!isset($print_options['show_quotes'])){ $print_options['show_quotes'] = true; }
  if (!isset($print_options['show_description'])){ $print_options['show_description'] = false; }
  if (!isset($print_options['show_sprites'])){ $print_options['show_sprites'] = false; }
  if (!isset($print_options['show_abilities'])){ $print_options['show_abilities'] = false; }
  if (!isset($print_options['show_records'])){ $print_options['show_records'] = false; }
  if (!isset($print_options['show_footer'])){ $print_options['show_footer'] = true; }
  if (!isset($print_options['show_key'])){ $print_options['show_key'] = false; }
}

// Collect the player sprite dimensions
$player_image_size = !empty($player_info['player_image_size']) ? $player_info['player_image_size'] : 40;
$player_image_size_text = $player_image_size.'x'.$player_image_size;
$player_image_token = !empty($player_info['player_image']) ? $player_info['player_image'] : $player_info['player_token'];
$player_type_token = !empty($player_info['player_type']) ? $player_info['player_type'] : 'none';

// Define the sprite sheet alt and title text
$player_sprite_size = $player_image_size * 2;
$player_sprite_size_text = $player_sprite_size.'x'.$player_sprite_size;
$player_sprite_title = $player_info['player_name'];
//$player_sprite_title = $player_info['player_number'].' '.$player_info['player_name'];
//$player_sprite_title .= ' Sprite Sheet | Robot Database | Mega Man RPG Prototype';

// Define the sprite frame index for robot images
$player_sprite_frames = array('base','taunt','victory','defeat','command','damage');

// Start the output buffer
ob_start();
?>
<div class="database_container database_player_container" data-token="<?=$player_info['player_token']?>" style="<?= $print_options['layout_style'] == 'website_compact' ? 'margin-bottom: 2px !important;' : '' ?>">
  <a class="anchor" id="<?=$player_info['player_token']?>">&nbsp;</a>
  <div class="subbody event event_triple event_visible" data-token="<?=$player_info['player_token']?>" style="<?= $print_options['layout_style'] == 'website_compact' ? 'margin-bottom: 2px !important;' : '' ?>">

    <? if($print_options['show_mugshot']): ?>
      <div class="this_sprite sprite_left" style="height: 40px;">
        <? if($print_options['show_key'] !== false): ?>
          <div class="mugshot player_type player_type_<?= !empty($player_info['player_type']) ? $player_info['player_type'] : 'none' ?>" style="font-size: 9px; line-height: 11px; text-align: center; margin-bottom: 2px; padding: 0 0 1px !important;"><?= 'No.'.($print_options['show_key'] + 1) ?></div>
        <? endif; ?>
        <div class="mugshot player_type player_type_<?= !empty($player_info['player_type']) ? $player_info['player_type'] : 'none' ?>"><div style="background-image: url(images/players/<?= $player_image_token ?>/mug_right_<?= $player_image_size_text ?>.png?<?=MMRPG_CONFIG_CACHE_DATE?>); " class="sprite sprite_player sprite_40x40 sprite_40x40_mug sprite_size_<?= $player_image_size_text ?> sprite_size_<?= $player_image_size_text ?>_mug player_status_active player_position_active"><?=$player_info['player_name']?>'s Mugshot</div></div>
      </div>
    <? endif; ?>


    <? if($print_options['show_basics']): ?>
      <h2 class="header header_left player_type_<?= $player_type_token ?>" style="margin-right: 0;">
        <? if($print_options['layout_style'] == 'website_compact'): ?>
          <a href="database/players/<?= $player_info['player_token'] ?>/"><?= $player_info['player_name'] ?></a>
        <? else: ?>
          <?= $player_info['player_name'] ?>&#39;s Data
        <? endif; ?>
        <? if (!empty($player_info['player_type'])): ?>
          <span class="header_core ability_type" style="border-color: rgba(0, 0, 0, 0.2) !important; background-color: rgba(0, 0, 0, 0.2) !important;"><?= ucfirst($player_info['player_type']) ?> Type</span>
        <? endif; ?>
      </h2>
      <div class="body body_left" style="margin-right: 0; padding: 2px 3px;">
        <table class="full" style="margin: 5px auto 10px;">
          <colgroup>
            <col width="48%" />
            <col width="1%" />
            <col width="48%" />
          </colgroup>
          <tbody>
            <tr>
              <td class="right">
                <label style="display: block; float: left;">Name :</label>
                <span class="player_name player_type"><?=$player_info['player_name']?></span>
              </td>
              <td class="middle">&nbsp;</td>
              <td class="right">
                <label style="display: block; float: left;">Bonus :</label>
                <?
                  // Display any special boosts this player has
                  if (!empty($player_info['player_energy'])){ echo '<span class="player_name player_type player_type_energy">Robot Energy +'.$player_info['player_energy'].'%</span>'; }
                  elseif (!empty($player_info['player_attack'])){ echo '<span class="player_name player_type player_type_attack">Robot Attack +'.$player_info['player_attack'].'%</span>'; }
                  elseif (!empty($player_info['player_defense'])){ echo '<span class="player_name player_type player_type_defense">Robot Defense +'.$player_info['player_defense'].'%</span>'; }
                  elseif (!empty($player_info['player_speed'])){ echo '<span class="player_name player_type player_type_speed">Robot Speed +'.$player_info['player_speed'].'%</span>'; }
                  else { echo '<span class="player_name player_type player_type_none">None</span>'; }
                ?>
              </td>
            </tr>
          </tbody>
        </table>
        <? if($print_options['show_quotes']): ?>
          <table class="full" style="margin: 5px auto 10px;">
            <colgroup>
              <col width="100%" />
            </colgroup>
            <tbody>
              <tr>
                <td class="right">
                  <label style="display: block; float: left;">Start Quote : </label>
                  <span class="player_quote">&quot;<?= !empty($player_info['player_quotes']['battle_start']) ? $player_info['player_quotes']['battle_start'] : '&hellip;' ?>&quot;</span>
                </td>
              </tr>
              <tr>
                <td class="right">
                  <label style="display: block; float: left;">Taunt Quote : </label>
                  <span class="player_quote">&quot;<?= !empty($player_info['player_quotes']['battle_taunt']) ? $player_info['player_quotes']['battle_taunt'] : '&hellip;' ?>&quot;</span>
                </td>
              </tr>
              <tr>
                <td class="right">
                  <label style="display: block; float: left;">Victory Quote : </label>
                  <span class="player_quote">&quot;<?= !empty($player_info['player_quotes']['battle_victory']) ? $player_info['player_quotes']['battle_victory'] : '&hellip;' ?>&quot;</span>
                </td>
              </tr>
              <tr>
                <td class="right">
                  <label style="display: block; float: left;">Defeat Quote : </label>
                  <span class="player_quote">&quot;<?= !empty($player_info['player_quotes']['battle_defeat']) ? $player_info['player_quotes']['battle_defeat'] : '&hellip;' ?>&quot;</span>
                </td>
              </tr>
            </tbody>
          </table>
        <? endif; ?>
      </div>
    <? endif; ?>

    <? if($print_options['show_description'] && !empty($player_info['player_description2'])): ?>

      <h2 class="header header_left player_type_<?= $player_type_token ?>" style="margin-right: 0;">
        <?= $player_info['player_name'] ?>&#39;s Description
      </h2>
      <div class="body body_left" style="margin-right: 0; margin-bottom: 5px; padding: 2px 0; min-height: 10px;">
        <table class="full" style="margin: 5px auto 10px;">
          <colgroup>
            <col width="100%" />
          </colgroup>
          <tbody>
            <tr>
              <td class="right">
                <div class="player_description" style="text-align: justify; padding: 0 4px;"><?= $player_info['player_description2'] ?></div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    <? endif; ?>

    <? if($print_options['show_sprites'] && (!isset($player_info['player_image_sheets']) || $player_info['player_image_sheets'] !== 0) && $player_image_token != 'player' ): ?>
      <h2 id="sprites" class="header header_full player_type_<?= $player_type_token ?>" style="margin: 10px 0 0; text-align: left;">
        <?=$player_info['player_name']?>&#39;s Sprites
      </h2>
      <div class="body body_full" style="margin: 0; padding: 10px; min-height: auto;">
        <div style="border: 1px solid rgba(0, 0, 0, 0.20); border-radius: 0.5em; -moz-border-radius: 0.5em; -webkit-border-radius: 0.5em; background: #4d4d4d url(images/sprite-grid.gif) scroll repeat -10px -30px; overflow: hidden; padding: 10px 30px;">
          <?
          // Show the player mugshot sprite
          foreach (array('right', 'left') AS $temp_direction){
            $temp_title = $player_sprite_title.' | Mugshot Sprite '.ucfirst($temp_direction);
            $temp_label = 'Mugshot '.ucfirst(substr($temp_direction, 0, 1));
            echo '<div style="'.($player_sprite_size <= 80 ? 'padding-top: 20px; ' : '').'float: left; position: relative; margin: 0; box-shadow: inset 1px 1px 5px rgba(0, 0, 0, 0.75); width: '.$player_sprite_size.'px; height: '.$player_sprite_size.'px; overflow: hidden;">';
              echo '<img style="margin-left: 0;" title="'.$temp_title.'" alt="'.$temp_title.'" src="images/players/'.$player_image_token.'/mug_'.$temp_direction.'_'.$player_sprite_size_text.'.png?'.MMRPG_CONFIG_CACHE_DATE.'" />';
              echo '<label style="position: absolute; left: 5px; top: 0; color: #EFEFEF; font-size: 10px; text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);">'.$temp_label.'</label>';
            echo '</div>';
          }
          // Loop through the different frames and print out the sprite sheets
          foreach ($player_sprite_frames AS $this_key => $this_frame){
            $margin_left = ceil((0 - $this_key) * $player_sprite_size);
            foreach (array('right', 'left') AS $temp_direction){
              $temp_title = $player_sprite_title.' | '.ucfirst($this_frame).' Sprite '.ucfirst($temp_direction);
              $temp_label = ucfirst($this_frame).' '.ucfirst(substr($temp_direction, 0, 1));
              echo '<div style="'.($player_sprite_size <= 80 ? 'padding-top: 20px; ' : '').'float: left; position: relative; margin: 0; box-shadow: inset 1px 1px 5px rgba(0, 0, 0, 0.75); width: '.$player_sprite_size.'px; height: '.$player_sprite_size.'px; overflow: hidden;">';
                echo '<img style="margin-left: '.$margin_left.'px;" title="'.$temp_title.'" alt="'.$temp_title.'" src="images/players/'.$player_image_token.'/sprite_'.$temp_direction.'_'.$player_sprite_size_text.'.png?'.MMRPG_CONFIG_CACHE_DATE.'" />';
                echo '<label style="position: absolute; left: 5px; top: 0; color: #EFEFEF; font-size: 10px; text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);">'.$temp_label.'</label>';
              echo '</div>';
            }
          }
          ?>
        </div>
        <?
        // Define the editor title based on ID
        $temp_editor_title = 'Undefined';
        if (!empty($player_info['player_image_editor'])){
          if ($player_info['player_image_editor'] == 412){ $temp_editor_title = 'Adrian Marceau / Ageman20XX'; }
          elseif ($player_info['player_image_editor'] == 110){ $temp_editor_title = 'MetalMarioX100 / EliteP1'; }
          elseif ($player_info['player_image_editor'] == 18){ $temp_editor_title = 'Sean Adamson / MetalMan'; }
        } else {
          $temp_editor_title = 'Adrian Marceau / Ageman20XX';
        }
        ?>
        <p class="text text_editor" style="text-align: center; color: #868686; font-size: 10px; line-height: 10px; margin-top: 6px;">Sprite Editing by <strong><?= $temp_editor_title ?></strong> <span style="color: #565656;"> | </span> Original Artwork by <strong>Capcom</strong></p>
      </div>
    <? endif; ?>

    <? if($print_options['show_abilities']): ?>
      <h2 id="abilities" class="header header_full player_type_<?= $player_type_token ?>" style="margin: 10px 0 0; text-align: left;">
        <?=$player_info['player_name']?>&#39;s Abilities
      </h2>
      <div class="body body_full" style="margin: 0; padding: 2px 3px;">
        <table class="full" style="margin: 5px auto 10px;">
          <colgroup>
            <col width="100%" />
          </colgroup>
          <tbody>
            <tr>
              <td class="right">
                <div class="ability_container">
                <?
                $index_player = $mmrpg_index['players'][$player_info['player_token']];
                $player_ability_core = !empty($index_player['player_type']) ? $index_player['player_type'] : false;
                $player_ability_list = !empty($index_player['player_abilities']) ? $index_player['player_abilities'] : array();
                $player_ability_rewards = !empty($player_info['player_rewards']['abilities']) ? $player_info['player_rewards']['abilities'] : array();
                $new_ability_rewards = array();
                foreach ($player_ability_rewards AS $this_info){
                  $new_ability_rewards[$this_info['token']] = $this_info;
                }
                $player_copy_program = $player_ability_core == 'copy' ? true : false;
                //if ($player_copy_program){ $player_ability_list = $temp_all_ability_tokens; }
                $player_ability_core_list = array();
                if (!empty($player_ability_core)){
                  foreach ($mmrpg_database_abilities AS $token => $info){
                    if (!empty($info['ability_type']) && ($player_copy_program || $info['ability_type'] == $player_ability_core)){
                      $player_ability_list[] = $info['ability_token'];
                      $player_ability_core_list[] = $info['ability_token'];
                    }
                  }
                }
                foreach ($player_ability_list AS $this_token){
                  if ($this_token == '*'){ continue; }
                  if (!isset($new_ability_rewards[$this_token])){
                    if (in_array($this_token, $player_ability_core_list)){ $new_ability_rewards[$this_token] = array('level' => 'Player', 'token' => $this_token); }
                    else { $new_ability_rewards[$this_token] = array('level' => 'Player', 'token' => $this_token); }

                  }
                }
                $player_ability_rewards = $new_ability_rewards;

                //die('<pre>'.print_r($player_ability_rewards, true).'</pre>');

                if (!empty($player_ability_rewards)){
                  $temp_string = array();
                  $ability_key = 0;
                  $ability_method_key = 0;
                  $ability_method = '';
                  $temp_robot_info = mmrpg_robot::get_index_info('mega-man');
                  $temp_abilities_index = $DB->get_array_list("SELECT * FROM mmrpg_index_abilities WHERE ability_flag_complete = 1;", 'ability_token');
                  foreach ($player_ability_rewards AS $this_info){
                    $this_points = $this_info['points'];
                    $this_ability = rpg_ability::parse_index_info($temp_abilities_index[$this_info['token']]);
                    $this_ability_token = $this_ability['ability_token'];
                    $this_ability_name = $this_ability['ability_name'];
                    $this_ability_image = !empty($this_ability['ability_image']) ? $this_ability['ability_image']: $this_ability['ability_token'];
                    $this_ability_type = !empty($this_ability['ability_type']) ? $this_ability['ability_type'] : false;
                    if (!empty($this_ability_type) && !empty($mmrpg_index['types'][$this_ability_type])){ $this_ability_type = $mmrpg_index['types'][$this_ability_type]['type_name'].' Type'; }
                    else { $this_ability_type = ''; }
                    $this_ability_damage = !empty($this_ability['ability_damage']) ? $this_ability['ability_damage'] : 0;
                    $this_ability_damage2 = !empty($this_ability['ability_damage2']) ? $this_ability['ability_damage2'] : 0;
                    $this_ability_damage_percent = !empty($this_ability['ability_damage_percent']) ? true : false;
                    $this_ability_damage2_percent = !empty($this_ability['ability_damage2_percent']) ? true : false;
                    if ($this_ability_damage_percent && $this_ability_damage > 100){ $this_ability_damage = 100; }
                    if ($this_ability_damage2_percent && $this_ability_damage2 > 100){ $this_ability_damage2 = 100; }
                    $this_ability_recovery = !empty($this_ability['ability_recovery']) ? $this_ability['ability_recovery'] : 0;
                    $this_ability_recovery2 = !empty($this_ability['ability_recovery2']) ? $this_ability['ability_recovery2'] : 0;
                    $this_ability_recovery_percent = !empty($this_ability['ability_recovery_percent']) ? true : false;
                    $this_ability_recovery2_percent = !empty($this_ability['ability_recovery2_percent']) ? true : false;
                    if ($this_ability_recovery_percent && $this_ability_recovery > 100){ $this_ability_recovery = 100; }
                    if ($this_ability_recovery2_percent && $this_ability_recovery2 > 100){ $this_ability_recovery2 = 100; }
                    $this_ability_accuracy = !empty($this_ability['ability_accuracy']) ? $this_ability['ability_accuracy'] : 0;
                    $this_ability_description = !empty($this_ability['ability_description']) ? $this_ability['ability_description'] : '';
                    $this_ability_description = str_replace('{DAMAGE}', $this_ability_damage, $this_ability_description);
                    $this_ability_description = str_replace('{RECOVERY}', $this_ability_recovery, $this_ability_description);
                    $this_ability_description = str_replace('{DAMAGE2}', $this_ability_damage2, $this_ability_description);
                    $this_ability_description = str_replace('{RECOVERY2}', $this_ability_recovery2, $this_ability_description);
                    //$this_ability_title_plain = $this_ability_name;
                    //if (!empty($this_ability_type)){ $this_ability_title_plain .= ' | '.$this_ability_type; }
                    //if (!empty($this_ability_damage)){ $this_ability_title_plain .= ' | '.$this_ability_damage.' Damage'; }
                    //if (!empty($this_ability_recovery)){ $this_ability_title_plain .= ' | '.$this_ability_recovery.' Recovery'; }
                    //if (!empty($this_ability_accuracy)){ $this_ability_title_plain .= ' | '.$this_ability_accuracy.'% Accuracy'; }
                    //if (!empty($this_ability_description)){ $this_ability_title_plain .= ' | '.$this_ability_description; }
                    $this_ability_title_plain = rpg_ability::print_editor_title_markup($temp_robot_info, $this_ability);

                    $this_ability_method = 'points';
                    $this_ability_method_text = 'Battle Points';
                    $this_ability_title_html = '<strong class="name">'.$this_ability_name.'</strong>';
                    if ($this_points > 1){ $this_ability_title_html .= '<span class="points">'.str_pad($this_points, 2, '0', STR_PAD_LEFT).' BP</span>'; }
                    else { $this_ability_title_html .= '<span class="points">Start</span>'; }
                    if (!empty($this_ability_type)){ $this_ability_title_html .= '<span class="type">'.$this_ability_type.'</span>'; }
                    if (!empty($this_ability_damage)){ $this_ability_title_html .= '<span class="damage">'.$this_ability_damage.(!empty($this_ability_damage_percent) ? '%' : '').' '.($this_ability_damage && $this_ability_recovery ? 'D' : 'Damage').'</span>'; }
                    if (!empty($this_ability_recovery)){ $this_ability_title_html .= '<span class="recovery">'.$this_ability_recovery.(!empty($this_ability_recovery_percent) ? '%' : '').' '.($this_ability_damage && $this_ability_recovery ? 'R' : 'Recovery').'</span>'; }
                    if (!empty($this_ability_accuracy)){ $this_ability_title_html .= '<span class="accuracy">'.$this_ability_accuracy.'% Accuracy</span>'; }
                    $this_ability_sprite_path = 'images/abilities/'.$this_ability_image.'/icon_left_40x40.png';
                    if (!file_exists(MMRPG_CONFIG_ROOTDIR.$this_ability_sprite_path)){ $this_ability_sprite_path = 'images/abilities/ability/icon_left_40x40.png'; }
                    $this_ability_sprite_html = '<span class="icon"><img src="'.$this_ability_sprite_path.'?'.MMRPG_CONFIG_CACHE_DATE.'" alt="'.$this_ability_name.' Icon" /></span>';
                    $this_ability_title_html = '<span class="label">'.$this_ability_title_html.'</span>';
                    //$this_ability_title_html = (is_numeric($this_points) && $this_points > 1 ? 'Lv '.str_pad($this_points, 2, '0', STR_PAD_LEFT).' : ' : $this_points.' : ').$this_ability_title_html;
                    if ($ability_method != $this_ability_method){
                      $temp_separator = '<div class="ability_separator">'.$this_ability_method_text.'</div>';
                      $temp_string[] = $temp_separator;
                      $ability_method = $this_ability_method;
                      $ability_method_key++;
                    }
                    if ($this_points >= 0 || !$player_copy_program){
                      $temp_markup = '<a href="'.MMRPG_CONFIG_ROOTURL.'database/abilities/'.$this_ability['ability_token'].'/"  class="ability_name ability_type ability_type_'.(!empty($this_ability['ability_type']) ? $this_ability['ability_type'] : 'none').'" title="'.$this_ability_title_plain.'" style="">';
                      $temp_markup .= '<span class="chrome">'.$this_ability_sprite_html.$this_ability_title_html.'</span>';
                      $temp_markup .= '</a>';
                      $temp_string[] = $temp_markup;
                      $ability_key++;
                      continue;
                    }
                  }
                  echo implode(' ', $temp_string);
                } else {
                  echo '<span class="player_ability player_type_none"><span class="chrome">None</span></span>';
                }
                ?>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    <? endif; ?>

    <? if($print_options['show_footer'] && $print_options['layout_style'] == 'website'): ?>

      <a class="link link_top" data-href="#top" rel="nofollow">^ Top</a>
      <a class="link link_permalink permalink" href="database/players/<?= $player_info['player_token'] ?>/" rel="permalink">+ Permalink</a>

    <? elseif($print_options['show_footer'] && $print_options['layout_style'] == 'website_compact'): ?>

      <a class="link link_top" data-href="#top" rel="nofollow">^ Top</a>
      <a class="link link_permalink permalink" href="database/players/<?= $player_info['player_token'] ?>/" rel="permalink">+ View More</a>

    <? endif; ?>

  </div>
</div>
<?
// Collect the outbut buffer contents
$this_markup = trim(ob_get_clean());

?>