<?php // Extend Batcache for Pressable site 

if (!defined('IS_PRESSABLE'))
    {
        return;
    }


//Function to cache pages for 24 hours - useful for static site with less traffic 
global $batcache;
if ( is_object($batcache) ) {
     $batcache->max_age = 86400; // Seconds the cached render of a page will be stored
     $batcache->seconds = 3600; // The amount of time at least 2 people are required to visit your page for a cached render to be stored.
}
