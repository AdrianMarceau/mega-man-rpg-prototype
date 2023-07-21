<?

// Define an array to hold our local mapping of sound effects to sprite names
$sound_effects_aliases_index = array(

    // Game Start
    'game-start' => 'selected_mmv-gb',

    // Menu Sound Effects
    'link-hover' => 'cursor_mm10-xbox', //'cursor_mmii-gb',
    'link-click' => 'pause_mmv-gb',
    'link-click-special' => 'get-beat_mmv-gb',
    'link-click-robot' => 'land_mmv-gb',
    'link-click-action' => 'selected_mmv-gb',
    'back-hover' => 'cursor_mmv-gb',
    'back-click' => 'transform_mmv-gb',
    'back-click-loading' => 'giant-suzy-bounce_mmv-gb',
    'icon-hover' => 'dink_mmi-gb',
    'icon-click' => 'pause_mmv-gb',
    'icon-click-mini' => 'wily-escape-iii-a_mmv-gb',
    'lets-go' => 'selected_mmv-gb',
    'lets-go-robots' => 'beam-out_mmv-gb',
    'text-sound' => 'text_mmv-gb',
    'event-sound' => 'get-beat_mmv-gb',
    'tooltip-open' => 'refill_mmii-gb',
    'tooltip-text' => 'atomic-fire-charge-b_mmi-gb',
    'shop-success' => 'selected_mmv-gb',
    'zenny-spent' => 'refill_mmv-gb',

    // Battle Sound Effects
    'teleport-in' => 'land_mmv-gb',
    'switch-in' => 'pause_mmi-gb',
    'master-teleport-in' => 'land_mmv-gb',
    'master-switch-in' => 'pause_mmi-gb',
    'mecha-teleport-in' => 'beam-in_mmv-gb',
    'mecha-switch-in' => 'land_mmi-gb',
    'boss-teleport-in' => 'ladder-press_mmv-gb',
    'boss-switch-in' => 'floor-break_mmv-gb',
    'background-spawn' => 'yoku_mmv-gb',
    'foreground-spawn' => 'shutter_mmv-gb',
    'victory-result' => 'enker-absorb_mmi-gb',
    'failure-result' => 'dark-moon-stomp_mmv-gb',
    'exit-mission' => 'beam-out_mmii-gb',
    'battle-victory-sound' => 'stage-clear_mm6-nes',
    'battle-defeat-sound' => 'game-over_mm4-nes',
    'damage' => 'hurt_mmv-gb',
    'damage-reverb' => 'small-boom-b_mmv-gb',
    'damage-critical' => 'enemy-hit_mmv-gb',
    'damage-reduced' => 'hurt_mmii-gb',
    'damage-hindered' => 'dink_mmii-gb',
    'damage-stats' => 'wily-star-missile_mmv-gb',
    'recovery-energy' => 'password-okay_mmi-gb',
    'recovery-weapons' => 'refill_mmv-gb',
    'recovery-stats' => 'terra-freeze_mmv-gb',
    'destroyed-sound' => 'dead_mmi-gb',
    'master-destroyed-sound' => 'dead_mmi-gb',
    'mecha-destroyed-sound' => 'dead_mmv-gb',
    'boss-destroyed-sound' => 'dead_mmii-gb',
    'super-boss-destroyed-sound' => 'biggest-boom_mmv-gb',
    'get-item' => 'one-up_mmv-gb',
    'get-big-item' => 'get-beat_mmv-gb',
    'get-weird-item' => 'weapon-get_mmii-gb',
    'use-recovery-item' => 'rush-jet-move-in_mmv-gb',
    'use-reviving-item' => 'password-okay_mmv-gb',
    'experience-points' => 'refill_mmv-gb',
    'level-up' => 'password-okay_mmv-gb',
    'stat-bonus' => 'password-okay_mmi-gb',
    'star-collected' => 'wpn-get-iii_mmv-gb',
    'shards-fusing' => 'wily-fortress-appear_mmv-gb',
    'marker-destroyed' => 'dark-moon-stomp_mmv-gb',
    'field-boost' => 'wily-escape-iv-a_mmv-gb',
    'field-break' => 'wily-escape-iii-b_mmv-gb',
    'scan-start' => 'shutter_mmii-gb',
    'scan-success' => 'ring-boomerang_mmv-gb',
    'scan-success-new' => 'enker-absorb_mmi-gb',
    'defend-sound' => 'dink_mmi-gb',
    'battle-start-sound' => 'megaman_ready_mm8-psx',
    'master-taunt-sound' => 'sound-95_mmxi-gb',
    'mecha-taunt-sound' => 'land_mmi-gb',
    'boss-taunt-sound' => 'dark-moon-stomp_mmv-gb',
    'no-effect' => 'text_mmv-gb',

    // Global Action Sound Effects
    'shot-sound' => 'shot-a_mmv-gb',
    'shot-sound-alt' => 'shot-b_mmv-gb',
    'shot-sound-alt2' => 'shot_mmii-gb',
    'charge-sound' => 'charge_mmxi-gb',
    'slide-sound' => 'charge-kick_mmv-gb',
    'summon-sound' => 'yoku_mmv-gb',
    'stomp-sound' => 'enemy-hit_mmi-gb',
    'throw-sound' => 'shot_mmii-gb',
    'growing-sound' => 'rush-jet-move-in_mmv-gb',
    'summon-positive' => 'wily-escape-iv-a_mmv-gb',
    'summon-negative' => 'wily-escape-iii-b_mmv-gb',
    'full-screen-woosh' => 'wily-fortress-appear_mmv-gb',
    'full-screen-down' => 'wily-escape-iii-b_mmv-gb',
    'full-screen-suck' => 'space-suck-cropped_mmv-gb',
    'intense-charge-sound' => 'buzzsaw_mmi-gb',
    'intense-growing-sound' => 'rush-space-zoom-cropped_mmv-gb',
    'hyper-charge-sound' => 'charge_mmxi-gb',
    'hyper-slide-sound' => 'power-stone_mmv-gb',
    'hyper-summon-sound' => 'pharoah-shot-a_mmv-gb',
    'hyper-blast-sound' => 'mid-scene-mega-shoot_mmv-gb',
    'hyper-stomp-sound' => 'dark-moon-stomp_mmv-gb',
    'buff-received' => 'selected_mmv-gb',
    'debuff-received' => 'dark-moon-stomp_mmv-gb',
    'small-buff-received' => 'pause-open_mmii-gb',
    'small-debuff-received' => 'wily-waggle_mmv-gb',

    // Reusable Ability Sound Effects
    'spawn-sound' => 'yoku_mmv-gb',
    'blast-sound' => 'mid-scene-mega-shoot_mmv-gb',
    'suck-sound' => 'charge_mmv-gb',
    'suction-sound' => 'enemy-hit_mmii-gb',
    'swing-sound' => 'pharoah-shot-b_mmv-gb',
    'upward-impact' => 'floor-break_mmv-gb',
    'downward-impact' => 'punk-dig_mmv-gb',
    'rapid-fire-sound' => 'break-dash_mmv-gb',
    'horn-toot-sound' => 'charge-man-toot_mmv-gb',
    'horn-blow-sound' => 'buzz_mmv-gb',
    'bounce-sound' => 'giant-suzy-bounce_mmv-gb',
    'smack-sound' => 'ladder-press_mmv-gb',
    'barrage-sound' => 'power-stone_mmv-gb',
    'punching-sound' => 'shot-b_mmv-gb',
    'beeping-sound' => 'shutter_mmii-gb',
    'shield-break-sound' => 'power-stone_mmv-gb',

    // Elemental Ability Sound Effects
    'flame-sound' => 'fire-storm_mmi-gb',
    'fireball-sound' => 'pharoah-shot-b_mmv-gb',
    'explode-sound' => 'biggerest-boom_mmv-gb',
    'splash-sound' => 'splash_mmv-gb',
    'water-sound' => 'salt-water_mmv-gb',
    'rain-sound' => 'rain-flush_mmv-gb',
    'bubble-sound' => 'wily-escape-iv-b-crop-2_mmv-gb',
    'spray-sound' => 'space-suck-cropped-2_mmv-gb',
    'blob-sound' => 'wily-escape-iv-b-crop-3_mmv-gb',
    'wobble-sound' => 'wily-waggle-mod-1_mmv-gb',
    'thunder-sound' => 'thunder_mmv-gb',
    'electric-sound' => 'electric-shock_mmv-gb',
    'electric-laser-sound' => 'thunder-beam_mmi-gb',
    'cannon-sound' => 'big-boom_mmv-gb',
    'big-cannon-sound' => 'bigger-boom_mmv-gb',
    'black-hole-sound' => 'black-hole_mmv-gb',
    'drilling-sound' => 'drill-bomb_mmv-gb',
    'timer-sound' => 'flash-stopper_mmv-gb',
    'quick-sound' => 'floor-break_mmv-gb',
    'vector-sound' => 'grab-buster_mmv-gb',
    'ice-sound' => 'ice-slasher_mmi-gb',
    'missile-sound' => 'photon-missile_mmv-gb',
    'digging-sound' => 'punk-dig_mmv-gb',
    'spinning-sound' => 'punk-spin_mmv-gb',
    'blade-sound' => 'ring-boomerang_mmv-gb',
    'space-sound' => 'rush-space-loop_mmv-gb',
    'cosmic-sound' => 'wpn-get-iii_mmv-gb',
    'saucer-sound' => 'wily-saucer-fall_mmv-gb',
    'ambush-sound' => 'shadow-man-attack_mmv-gb',
    'zephyr-sound' => 'shot-c_mmv-gb',
    'whirlwind-sound' => 'shot-d_mmv-gb',
    'traintrack-sound' => 'train-tracks_mmv-gb',
    'laser-sound' => 'wily-star-laser-b_mmv-gb',
    'long-laser-sound' => 'wily-star-laser-c_mmv-gb',
    'shining-sound' => 'selected-mod-1_mmv-gb',
    'blowing-sound' => 'rush-space-zoom-mod-1_mmv-gb',

    /*
    '' => 'hurt_mmi-gb',
    '' => 'hurt_mmi-gb',
    '' => 'hurt_mmi-gb',
    '' => 'hurt_mmi-gb',
    '' => 'hurt_mmi-gb',
    '' => 'hurt_mmi-gb',
    */

    );

?>