<?
/**
 * Mega Man RPG Target
 * <p>The battle target class for the Mega Man RPG Prototype.</p>
 */
class rpg_target {

    // Define a trigger for using one of this robot's abilities in battle
    public static function trigger_ability_target($this_robot, $target_robot, $this_ability, $trigger_options = array()){
        global $db;

        // Return false if either are missing
        if (empty($this_robot) || empty($target_robot)){ return false; }

        // Define the event console options
        $event_options = array();
        $event_options['console_container_height'] = 1;
        $event_options['this_ability'] = $this_ability;
        $event_options['this_ability_target'] = $target_robot->robot_id.'_'.$target_robot->robot_token;
        $event_options['this_ability_target_key'] = $target_robot->robot_key;
        $event_options['this_ability_target_position'] = $target_robot->robot_position;
        $event_options['this_ability_results'] = array();
        $event_options['console_show_target'] = false;

        // Empty any text from the previous ability result
        $this_ability->ability_results['this_text'] = '';

        // Update this robot's history with the triggered ability
        $this_robot->history['triggered_targets'][] = $target_robot->robot_token;

        // Backup this and the target robot's frames to revert later
        $this_robot_backup_frame = $this_robot->robot_frame;
        $this_player_backup_frame = $this_robot->player->player_frame;
        $target_robot_backup_frame = $target_robot->robot_frame;
        $target_player_backup_frame = $target_robot->player->player_frame;
        $this_ability_backup_frame = $this_ability->ability_frame;

        // Update this robot's frames using the target options
        $this_robot->robot_frame = $this_ability->target_options['target_frame'];
        if ($this_robot->robot_id != $target_robot->robot_id){ $target_robot->robot_frame = 'defend'; }
        $this_robot->player->player_frame = 'command';
        $this_robot->player->update_session();
        $this_ability->ability_frame = $this_ability->target_options['ability_success_frame'];
        $this_ability->ability_frame_span = $this_ability->target_options['ability_success_frame_span'];
        $this_ability->ability_frame_offset = $this_ability->target_options['ability_success_frame_offset'];

        // If the target player is on the bench, alter the ability scale
        $temp_ability_styles_backup = $this_ability->ability_frame_styles;
        if ($target_robot->robot_position == 'bench' && $event_options['this_ability_target'] != $this_robot->robot_id.'_'.$this_robot->robot_token){
            $temp_scale = 1 - ($target_robot->robot_key * 0.06);
            $temp_translate = 20 + ($target_robot->robot_key * 20);
            $temp_translate2 = ceil($temp_translate / 10) * -1;
            $temp_translate = $temp_translate * ($target_robot->player->player_side == 'left' ? -1 : 1);
            $this_ability->ability_frame_styles .= 'transform: scale('.$temp_scale.', '.$temp_scale.') translate('.$temp_translate.'px, '.$temp_translate2.'px); -webkit-transform: scale('.$temp_scale.', '.$temp_scale.') translate('.$temp_translate.'px, '.$temp_translate2.'px); -moz-transform: scale('.$temp_scale.', '.$temp_scale.') translate('.$temp_translate.'px, '.$temp_translate2.'px); ';
        }

        // Create a message to show the initial targeting action
        if ($this_robot->robot_id != $target_robot->robot_id && empty($trigger_options['prevent_default_text'])){
            $this_ability->ability_results['this_text'] .= "{$this_robot->print_name()} targets {$target_robot->print_name()}!<br />";
        }

        // Update the ability results with the the trigger kind
        $this_ability->ability_results['trigger_kind'] = isset($trigger_options['override_trigger_kind']) ? $trigger_options['override_trigger_kind'] : 'target';
        $this_ability->ability_results['this_result'] = 'success';

        // Append the targetting text to the event body
        $result_text_field = 'target_'.$this_ability->ability_results['this_result'].'_text';
        if (isset($this_ability->target_options[$result_text_field])){
            $this_ability->ability_results['this_text'] .= $this_ability->target_options[$result_text_field];
        } else {
            $this_ability->ability_results['this_text'] .= $this_ability->target_options['target_text'];
        }

        // Update the event options with the ability results
        $event_options['this_ability_results'] = $this_ability->ability_results;
        if (isset($trigger_options['canvas_show_this_ability'])){ $event_options['canvas_show_this_ability'] = $trigger_options['canvas_show_this_ability'];  }

        // Set the camera options for this target event
        $event_options['event_flag_camera_action'] = true;
        $event_options['event_flag_camera_side'] = $this_robot->player->player_side;

        // Create a new entry in the event log for the targeting event
        $temp_event_header = $this_ability->target_options['target_header'];
        $temp_event_body = $this_ability->ability_results['this_text'];
        $temp_event_body = str_replace('{this_robot}', $this_robot->print_name(), $temp_event_body);
        $temp_event_body = str_replace('{target_robot}', $target_robot->print_name(), $temp_event_body);
        $this_robot->battle->events_create($this_robot, $target_robot, $temp_event_header, $temp_event_body, $event_options);

        // Update this ability's history with the triggered ability data and results
        $this_ability->history['ability_results'][] = $this_ability->ability_results;

        // Refresh the ability styles from any changes
        $this_ability->ability_frame_styles = '';

        // restore this and the target robot's frames to their backed up state
        $this_robot->robot_frame = $this_robot_backup_frame;
        $this_robot->player->player_frame = $this_player_backup_frame;
        $target_robot->robot_frame = $target_robot_backup_frame;
        $target_robot->player->player_frame = $target_player_backup_frame;
        $this_ability->ability_frame = $this_ability_backup_frame;
        $this_ability->target_options_reset();

        // Update internal variables
        $this_robot->update_session();
        $this_robot->player->update_session();
        $target_robot->update_session();
        $this_ability->update_session();

        // Return the ability results
        return $this_ability->ability_results;

    }

    // Define a trigger for using one of this robot's items in battle
    public static function trigger_item_target($this_robot, $target_robot, $this_item, $trigger_options = array()){
        global $db;

        // Define the event console options
        $event_options = array();
        $event_options['console_container_height'] = 1;
        $event_options['this_item'] = $this_item;
        $event_options['this_item_target'] = $target_robot->robot_id.'_'.$target_robot->robot_token;
        $event_options['this_item_target_key'] = $target_robot->robot_key;
        $event_options['this_item_target_position'] = $target_robot->robot_position;
        $event_options['this_item_results'] = array();
        $event_options['console_show_target'] = false;

        // Empty any text from the previous item result
        $this_item->item_results['this_text'] = '';

        // Update this robot's history with the triggered item
        $this_robot->history['triggered_targets'][] = $target_robot->robot_token;

        // Backup this and the target robot's frames to revert later
        $this_robot_backup_frame = $this_robot->robot_frame;
        $this_player_backup_frame = $this_robot->player->player_frame;
        $target_robot_backup_frame = $target_robot->robot_frame;
        $target_player_backup_frame = $target_robot->player->player_frame;
        $this_item_backup_frame = $this_item->item_frame;

        // Update this robot's frames using the target options
        $this_robot->robot_frame = $this_item->target_options['target_frame'];
        if ($this_robot->robot_id != $target_robot->robot_id){ $target_robot->robot_frame = 'defend'; }
        $this_robot->player->player_frame = 'command';
        $this_robot->player->update_session();
        $this_item->item_frame = $this_item->target_options['item_success_frame'];
        $this_item->item_frame_span = $this_item->target_options['item_success_frame_span'];
        $this_item->item_frame_offset = $this_item->target_options['item_success_frame_offset'];

        // If the target player is on the bench, alter the item scale
        $temp_item_styles_backup = $this_item->item_frame_styles;
        if ($target_robot->robot_position == 'bench' && $event_options['this_item_target'] != $this_robot->robot_id.'_'.$this_robot->robot_token){
            $temp_scale = 1 - ($target_robot->robot_key * 0.06);
            $temp_translate = 20 + ($target_robot->robot_key * 20);
            $temp_translate2 = ceil($temp_translate / 10) * -1;
            $temp_translate = $temp_translate * ($target_robot->player->player_side == 'left' ? -1 : 1);
            $this_item->item_frame_styles .= 'transform: scale('.$temp_scale.', '.$temp_scale.') translate('.$temp_translate.'px, '.$temp_translate2.'px); -webkit-transform: scale('.$temp_scale.', '.$temp_scale.') translate('.$temp_translate.'px, '.$temp_translate2.'px); -moz-transform: scale('.$temp_scale.', '.$temp_scale.') translate('.$temp_translate.'px, '.$temp_translate2.'px); ';
        }

        // Create a message to show the initial targeting action
        if ($this_robot->robot_id != $target_robot->robot_id && empty($trigger_options['prevent_default_text'])){
            $this_item->item_results['this_text'] .= "{$this_robot->print_name()} targets {$target_robot->print_name()}!<br />";
        }

        // Append the targetting text to the event body
        $this_item->item_results['this_text'] .= $this_item->target_options['target_text'];

        // Update the item results with the the trigger kind
        $this_item->item_results['trigger_kind'] = isset($trigger_options['override_trigger_kind']) ? $trigger_options['override_trigger_kind'] : 'target';
        $this_item->item_results['this_result'] = 'success';

        // Update the event options with the item results
        $event_options['this_item_results'] = $this_item->item_results;
        if (isset($trigger_options['canvas_show_this_item'])){ $event_options['canvas_show_this_item'] = $trigger_options['canvas_show_this_item'];  }

        // Set the camera options for this target event
        $event_options['event_flag_camera_action'] = true;
        $event_options['event_flag_camera_side'] = $this_robot->player->player_side;

        // Create a new entry in the event log for the targeting event
        $this_robot->battle->events_create($this_robot, $target_robot, $this_item->target_options['target_header'], $this_item->item_results['this_text'], $event_options);

        // Update this item's history with the triggered item data and results
        $this_item->history['item_results'][] = $this_item->item_results;

        // Refresh the item styles from any changes
        $this_item->item_frame_styles = '';

        // restore this and the target robot's frames to their backed up state
        $this_robot->robot_frame = $this_robot_backup_frame;
        $this_robot->player->player_frame = $this_player_backup_frame;
        $target_robot->robot_frame = $target_robot_backup_frame;
        $target_robot->player->player_frame = $target_player_backup_frame;
        $this_item->item_frame = $this_item_backup_frame;
        $this_item->target_options_reset();

        // Update internal variables
        $this_robot->update_session();
        $this_robot->player->update_session();
        $target_robot->update_session();
        $this_item->update_session();

        // Return the item results
        return $this_item->item_results;

    }

    // Define a trigger for using one of this robot's skills in battle
    public static function trigger_skill_target($this_robot, $target_robot, $this_skill, $trigger_options = array()){
        global $db;

        // Define the event console options
        $event_options = array();
        $event_options['console_container_height'] = 1;
        $event_options['this_skill'] = $this_skill;
        $event_options['this_skill_target'] = $target_robot->robot_id.'_'.$target_robot->robot_token;
        $event_options['this_skill_target_key'] = $target_robot->robot_key;
        $event_options['this_skill_target_position'] = $target_robot->robot_position;
        $event_options['this_skill_results'] = array();
        $event_options['console_show_target'] = false;
        $event_options['console_show_this'] = true;
        $event_options['console_show_this_player'] = false;
        $event_options['console_show_this_robot'] = true;

        // Empty any text from the previous skill result
        $this_skill->skill_results['this_text'] = '';

        // Update this robot's history with the triggered skill
        $this_robot->history['triggered_targets'][] = $target_robot->robot_token;

        // Backup this and the target robot's frames to revert later
        $this_robot_backup_frame = $this_robot->robot_frame;
        $this_player_backup_frame = $this_robot->player->player_frame;
        $target_robot_backup_frame = $target_robot->robot_frame;
        $target_player_backup_frame = $target_robot->player->player_frame;

        // Update this robot's frames using the target options
        $this_robot->robot_frame = $this_skill->target_options['target_frame'];
        if ($this_robot->robot_id != $target_robot->robot_id){ $target_robot->robot_frame = 'defend'; }
        $this_robot->player->player_frame = 'command';
        $this_robot->player->update_session();

        // Create a message to show the initial targeting action
        if ($this_robot->robot_id != $target_robot->robot_id && empty($trigger_options['prevent_default_text'])){
            $this_skill->skill_results['this_text'] .= "{$this_robot->print_name()} targets {$target_robot->print_name()}!<br />";
        }

        // Append the targetting text to the event body
        $this_skill->skill_results['this_text'] .= $this_skill->target_options['target_text'];

        // Update the skill results with the the trigger kind
        $this_skill->skill_results['trigger_kind'] = isset($trigger_options['override_trigger_kind']) ? $trigger_options['override_trigger_kind'] : 'target';
        $this_skill->skill_results['this_result'] = 'success';

        // Update the event options with the skill results
        $event_options['this_skill_results'] = $this_skill->skill_results;
        $event_options['canvas_show_this_skill'] = false;

        // Set the camera options for this target event
        $event_options['event_flag_camera_action'] = true;
        $event_options['event_flag_camera_side'] = $this_robot->player->player_side;

        // Create a new entry in the event log for the targeting event
        $this_robot->battle->events_create($this_robot, $target_robot, $this_skill->target_options['target_header'], $this_skill->skill_results['this_text'], $event_options);

        // Update this skill's history with the triggered skill data and results
        $this_skill->history['skill_results'][] = $this_skill->skill_results;

        // restore this and the target robot's frames to their backed up state
        $this_robot->robot_frame = $this_robot_backup_frame;
        $this_robot->player->player_frame = $this_player_backup_frame;
        $target_robot->robot_frame = $target_robot_backup_frame;
        $target_robot->player->player_frame = $target_player_backup_frame;
        $this_skill->target_options_reset();

        // Update internal variables
        $this_robot->update_session();
        $this_robot->player->update_session();
        $target_robot->update_session();
        $this_skill->update_session();

        // Return the skill results
        return $this_skill->skill_results;

    }

}
?>