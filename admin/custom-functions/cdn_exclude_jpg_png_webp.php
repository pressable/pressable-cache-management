<?php //Plugin Name: Exempt Images .jpg, .jpeg, .png, .gif, .webp From CDN


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

if (!defined('IS_PRESSABLE'))
{
    return;
}

function remove_query_string($html)
{
	if (empty($html))
	{
	return;
	}


  	$dom = new DOMDocument();
	$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

	$images = $dom->getElementsByTagName("img");
	foreach ($images as $img) {
    $src = $img->getAttribute("src");
    $src = str_replace(DB_NAME . '.v2.pressablecdn.com', $_SERVER['SERVER_NAME'], $src);
    $img->setAttribute("src", $src);

    $srcset = $img->getAttribute("srcset");
    if (!empty($srcset)) {
        $srcset = str_replace(DB_NAME . '.v2.pressablecdn.com', $_SERVER['SERVER_NAME'], $srcset);
        $img->setAttribute("srcset", $srcset);
    }
}

return $dom->saveHTML();

}

function pcm_jpg_png_webp_cdn_exempter($html) {
		
	//$html = preg_replace('/' . DB_NAME . '.v2.pressablecdn.com(.*)(.jpg|.jpeg|.png|.gif|.webp)/i', $_SERVER['SERVER_NAME'] . '$1$2', $html);

	//Removes the CDN URL from both the src and srcset attributes:
	$html = remove_query_string($html);
	
  return $html;
	
}

function pcm_jpg_png_webp_cdn_template_redirect() {
  ob_start();
  ob_start('pcm_jpg_png_webp_cdn_exempter');
  ob_flush();
}

add_action('template_redirect', 'pcm_jpg_png_webp_cdn_template_redirect');
