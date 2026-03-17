<?php //Pressable Cache Management - Custom function to turn on/off Pressable branding


/******************************
 * Show branding Option
 *******************************/

$pressable_branding = false;

$hide_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

//Check if options are set before processing
if (isset($hide_pressable_branding_tab_options['branding_on_off_radio_button']) && !empty($hide_pressable_branding_tab_options['branding_on_off_radio_button']))
{

    $hide_pressable_branding_tab_options = sanitize_text_field($hide_pressable_branding_tab_options['branding_on_off_radio_button']);

}

//Set radion button state to defualt
if ('enable' === $hide_pressable_branding_tab_options)
{

    $hide_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

    // echo 'Show Branding';
    //run your functions here if radio button is enabled
    
}

/******************************
 * Hide branding Option
 *******************************/

else
{

    $pressable_branding = false;

    $pressable_branding = get_option('remove_pressable_branding_tab_options');

    //Check if options are set before processing
    if (isset($hide_pressable_branding_tab_options['branding_on_off_radio_button']) && !empty($hide_pressable_branding_tab_options['branding_on_off_radio_button']))
    {

        $hide_pressable_branding_tab_options = sanitize_text_field($hide_pressable_branding_tab_options['branding_on_off_radio_button']);

    }

    //Set radio button state to defualt
    if ('disable' === $hide_pressable_branding_tab_options)
    {

        $hide_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

        // echo 'Hide Branding';
        //run your functions here if radio button is disbaled      

        
    }

}

