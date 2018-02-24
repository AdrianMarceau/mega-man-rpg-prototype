<?
// TARGET MAN
$robot = array(
    'robot_number' => 'PCR-005',
    'robot_game' => 'MM19',
    'robot_group' => 'MMAZ/Masters/MM19',
    'robot_name' => 'Target Man',
    'robot_token' => 'target-man',
    'robot_core' => 'missile',
    'robot_description' => 'Powerful Missiles Robot',
    'robot_energy' => 100,
    'robot_attack' => 100,
    'robot_defense' => 100,
    'robot_speed' => 100,
    'robot_weaknesses' => array(),
    'robot_resistances' => array(),
    'robot_affinities' => array(),
    'robot_abilities' => array(
        //'plant-barrier',
        'buster-shot', 'buster-charge',
        'attack-boost', 'attack-break', 'attack-swap', 'attack-mode',
        'defense-boost', 'defense-break', 'defense-swap', 'defense-mode',
        'speed-boost', 'speed-break', 'speed-swap', 'speed-mode',
        'energy-boost', 'energy-break', 'energy-swap', 'repair-mode',
        'field-support', 'mecha-support',
        'light-buster', 'wily-buster', 'cossack-buster'
        ),
    'robot_rewards' => array(
        'abilities' => array(
                array('level' => 0, 'token' => 'super-missile'),
                array('level' => 10, 'token' => 'element-missile')
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