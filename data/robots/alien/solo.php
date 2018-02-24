<?
// SOLO
$robot = array(
    'robot_number' => 'EXN-001',
    'robot_class' => 'boss',
    'robot_game' => 'MMEXE',
    'robot_name' => 'Solo',
    'robot_token' => 'solo',
    'robot_core' => 'space',
    'robot_description' => 'Intergalactic Chairman',
    'robot_energy' => 100,
    'robot_attack' => 100,
    'robot_defense' => 100,
    'robot_speed' => 100,
    'robot_weaknesses' => array('water', 'electric'),
    'robot_immunities' => array('space'),
    'robot_abilities' => array(
        'energy-fist', 'comet-attack', 'meteor-knuckle',
        'buster-shot',
        'attack-boost', 'attack-break', 'attack-swap', 'attack-mode',
        'defense-boost', 'defense-break', 'defense-swap', 'defense-mode',
        'speed-boost', 'speed-break', 'speed-swap', 'speed-mode',
        'energy-boost', 'energy-break', 'energy-swap', 'energy-mode',
        'field-support', 'mecha-support',
        'light-buster', 'wily-buster', 'cossack-buster'
        ),
    'robot_rewards' => array(
        'abilities' => array(
                array('level' => 0, 'token' => 'buster-shot')
            )
        ),
    'robot_quotes' => array(
        'battle_start' => '',
        'battle_taunt' => '',
        'battle_victory' => '',
        'battle_defeat' => ''
        ),
    'robot_flag_hidden' => true
    );
?>