<?

// Define the name of the folder we'll be scanning for gallery images
$this_gallery_kind = isset($this_gallery_kind) ? $this_gallery_kind : 'screenshot';
$this_gallery_folder = isset($this_gallery_folder) ? $this_gallery_folder : 'screenshots';

// Define the cache file name and path given everything we've learned
$cache_file_name = 'cache.gallery-'.$this_gallery_folder.'.json';
$cache_file_path = MMRPG_CONFIG_CACHE_PATH.'indexes/'.$cache_file_name;
// Check to see if a file already exists and collect its last-modified date
if (file_exists($cache_file_path)){ $cache_file_exists = true; $cache_file_date = date('Ymd-Hi', filemtime($cache_file_path)); }
else { $cache_file_exists = false; $cache_file_date = '00000000-0000'; }

// LOAD FROM CACHE if data exists and is current, otherwise continue so JSON can refresh and replace
if (MMRPG_CONFIG_CACHE_INDEXES && $cache_file_exists && $cache_file_date >= MMRPG_CONFIG_CACHE_DATE){
    $cache_file_markup = file_get_contents($cache_file_path);
    $mmrpg_gallery_index = json_decode($cache_file_markup, true);
    return;
}

// Define the base directory for gallery images so we can loop through 'em
$this_gallery_path = 'images/gallery/'.$this_gallery_folder.'/';
$this_gallery_basedir = MMRPG_CONFIG_ROOTDIR.$this_gallery_path;
$this_gallery_baseurl = MMRPG_CONFIG_ROOTURL.$this_gallery_path;

// Define an empty gallery index to append files to
$mmrpg_gallery_index = array();

// Scan the requested gallery directory to get a list of all files
$raw_gallery_paths = getDirContents($this_gallery_basedir);

// Sort the full gallery paths with the newest ones first
sort($raw_gallery_paths, SORT_REGULAR);
$raw_gallery_paths = array_reverse($raw_gallery_paths); // newest first

// Loop through and filter out only the ones we want that are actual files
foreach ($raw_gallery_paths AS $key => $full_path){

    // Clean the path to remove the gallery base directory
    $rel_path = str_replace($this_gallery_basedir, '', $full_path);
    // If this is a directory and not a file, continue
    if (!preg_match('/\.(jpg|png|gif)$/i', $rel_path)){ continue; }
    // If this is the thumbnail directory, continue
    if (preg_match('/^thumbs\//i', $rel_path)){ continue; }

    // Break the path apart into folder and filename for categorization
    list($file_folder, $file_name) = explode('/', $rel_path);

    // Reformat the date into a more readable format
    $file_date = preg_replace('#^([0-9]{4})([0-9]{2})([0-9]{2})$#', '$1/$2/$3', $file_folder);

    // Collect the unix modified-time for the file
    $file_time = filemtime($full_path);

    // Generate the href and thumb path for this file
    $file_href = str_replace(MMRPG_CONFIG_ROOTURL, '', $this_gallery_baseurl.$rel_path);
    $file_thumb = str_replace(MMRPG_CONFIG_ROOTURL, '', $this_gallery_baseurl.'thumbs/'.preg_replace('/\.(png|gif|jpg)$/i', '_thumb.jpg', str_replace('/', '_', $rel_path)));

    // Generate the markup for this gallery image
    $this_title = trim($file_name);
    $this_title = preg_replace('/.(png|jpg|gif)/i', '', $this_title);
    $this_title = preg_replace('/^([0-9]+)-/i', '', $this_title);
    $this_title = str_replace('dr', 'dr.', $this_title);
    $this_title = trim($this_title, '-');
    $this_title = ucwords(str_replace('-', ' ', $this_title));
    // Re-order some title words so they make more sense
    $this_title = preg_replace('/^Mission\s(.*)$/i', '$1 Mission', $this_title);
    $this_title = preg_replace('/^(.*)\sMobile$/i', '$1 (Mobile)', $this_title);

    // Create an entry in the index for this date if not exists
    if (!isset($mmrpg_gallery_index[$file_date])){ $mmrpg_gallery_index[$file_date] = array(); }

    // Generate the file info array with all this image's details
    $file_info = array(
        'name' => $file_name,
        'title' => $this_title,
        'time' => $file_time,
        'href' => $file_href,
        'thumb' => $file_thumb
        );

    // Now add this file to the index with its name and other details
    $mmrpg_gallery_index[$file_date][] = $file_info;

}

// Write the index to a cache file, if caching is enabled
if (MMRPG_CONFIG_CACHE_INDEXES === true){
    // Compress the index into a JSON string for the file
    $cache_file_markup = json_encode($mmrpg_gallery_index);
    // Write the index to a cache file, if caching is enabled
    $this_cache_file = fopen($cache_file_path, 'w');
    fwrite($this_cache_file, $cache_file_markup);
    fclose($this_cache_file);
}

// Count the total number of elements in the gallery index
$mmrpg_gallery_size = array_sum(array_map("count", $mmrpg_gallery_index));

?>