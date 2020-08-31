<?

// Define an index array to hold all the content type details
$content_types_index = array();

// Populate the content type index with names, paths, git URLs, etc.
$content_types_index['sql'] = array(
    'token' => 'sql',
    'xtoken' => 'sql',
    'object_class' => false,
    'primary_key' => false,
    'database_table' => false,
    'content_path' => '.sql/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_sql',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_sql.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_sql.git'
        )
    );
$content_types_index['types'] = array(
    'token' => 'type',
    'xtoken' => 'types',
    'object_class' => 'rpg_type',
    'primary_key' => 'token',
    'database_table' => 'mmrpg_index_types',
    'content_path' => 'types/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_types',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_types.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_types.git'
        )
    );
$content_types_index['players'] = array(
    'token' => 'player',
    'xtoken' => 'players',
    'object_class' => 'rpg_player',
    'primary_key' => 'token',
    'database_table' => 'mmrpg_index_players',
    'content_path' => 'players/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_players',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_players.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_players.git'
        )
    );
$content_types_index['robots'] = array(
    'token' => 'robot',
    'xtoken' => 'robots',
    'object_class' => 'rpg_robot',
    'primary_key' => 'token',
    'database_table' => 'mmrpg_index_robots',
    'content_path' => 'robots/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_robots',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_robots.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_robots.git'
        )
    );
$content_types_index['abilities'] = array(
    'token' => 'ability',
    'xtoken' => 'abilities',
    'object_class' => 'rpg_ability',
    'primary_key' => 'token',
    'database_table' => 'mmrpg_index_abilities',
    'content_path' => 'abilities/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_abilities',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_abilities.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_abilities.git'
        )
    );
$content_types_index['items'] = array(
    'token' => 'item',
    'xtoken' => 'items',
    'object_class' => 'rpg_item',
    'primary_key' => 'token',
    'database_table' => 'mmrpg_index_items',
    'content_path' => 'items/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_items',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_items.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_items.git'
        )
    );
$content_types_index['fields'] = array(
    'token' => 'field',
    'xtoken' => 'fields',
    'object_class' => 'rpg_fields',
    'primary_key' => 'token',
    'database_table' => 'mmrpg_index_fields',
    'content_path' => 'fields/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_fields',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_fields.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_fields.git'
        )
    );
$content_types_index['battles'] = array(
    'token' => 'battle',
    'xtoken' => 'battles',
    'object_class' => 'rpg_battle',
    'primary_key' => 'token',
    'database_table' => false,
    'content_path' => 'battles/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_battles',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_battles.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_battles.git'
        )
    );
$content_types_index['stars'] = array(
    'token' => 'star',
    'xtoken' => 'stars',
    'object_class' => false,
    'primary_key' => 'id',
    'database_table' => 'mmrpg_rogue_stars',
    'content_path' => 'stars/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_stars',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_stars.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_stars.git'
        )
    );
$content_types_index['challenges'] = array(
    'token' => 'challenge',
    'xtoken' => 'challenges',
    'object_class' => false,
    'primary_key' => 'id',
    'database_table' => 'mmrpg_challenges',
    'content_path' => 'challenges/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_challenges',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_challenges.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_challenges.git'
        )
    );
$content_types_index['pages'] = array(
    'token' => 'page',
    'xtoken' => 'pages',
    'object_class' => false,
    'primary_key' => 'url',
    'database_table' => 'mmrpg_website_pages',
    'content_path' => 'pages/',
    'github_repo' => array(
        'name' => 'mmrpg-prototype_pages',
        'http' => 'https://github.com/AdrianMarceau/mmrpg-prototype_pages.git',
        'ssh' => 'git@github.com:AdrianMarceau/mmrpg-prototype_pages.git'
        )
    );

?>