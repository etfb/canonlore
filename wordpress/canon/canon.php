<?php
/**
 * Plugin Name: Canon Lore
 * Plugin URI: http://huoncs.com.au/wordpress/canonlore
 * Description: The SCA Lochac Canon Lore OP in plugin form.
 * Version: 0.0.1
 * Author: Paul Sleigh
 * Author URI: http://huoncs.com.au/
 * License: GPL2
 *
 *
 * Copyright 2015, Paul Sleigh (email: paul@huoncs.com.au)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

require_once 'functions/canondb.php';
require_once 'functions/formatter.php';
require_once 'functions/options.php';
require_once 'functions/rewriter.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

date_default_timezone_set(get_option('timezone_string') ?: 'Australia/Hobart');
load_default_textdomain();

register_activation_hook(__FILE__, (('canon_activate')));
register_deactivation_hook(__FILE__, (('canon_deactivate')));
 
add_action('init', (('canon_init')));
add_shortcode('canon-fixme', (('canon_shortcode_fixme')));
add_shortcode('canon-link', (('canon_shortcode_link')));
add_shortcode('canon-placeholder', (('canon_shortcode_placeholder')));
add_shortcode('canon-recent', (('canon_shortcode_recent')));

add_action('wp_enqueue_scripts', (('canon_enqueue_stuff')));
add_filter('the_title', ('canon_page_title'), 10, 2);
add_filter('wp_title', ('canon_header_title'), 10, 2);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Action: prepare the rewrite rules for /person/###, /branch/###, etc

function canon_activate()
{
    list($rewriter,$options) = canon_prepare();
    $rewriter->SetUpRules();
}

// Action: tidy up the rewrite rules prepared by canon_activate()

function canon_deactivate()
{
    list($rewriter,$options) = canon_prepare();
    $rewriter->TidyRules();
}

// Action: set up CSS and JS files to be added to the <head>

function canon_enqueue_stuff()
{
    wp_enqueue_style('canon_css', plugin_dir_url(__FILE__) . 's/canon.css');
    wp_enqueue_script('canon_js', plugin_dir_url(__FILE__) . 's/canon.js', ['jquery',
                                                                            'jquery-ui-tabs',
                                                                            'jquery-ui-button',
                                                                            'jquery-ui-accordion']);
    wp_enqueue_style('canon_jqui_css', plugin_dir_url(__FILE__) . 's/jquery-ui.min.css');
}

// Filter: return a title suitable for *&*&*&*&*&*&*

function canon_header_title($title,$sep)
{
    $t = canon_title($title);
    if ($t) $t .= $sep;
    return $t;
}

// Action: prepare the rewrite rules, as above

function canon_init()
{
    list($rewriter,$options) = canon_prepare();
    $rewriter->SetUpRules();
}

// Filter: return a title suitable for *&*&*&*&*&*&*

function canon_page_title($title,$id)
{
    return get_the_ID() == $id ? canon_title($title) : $title;
}

// Utility function: prepare the utility classes so they are created at exactly the latest possible moment

function canon_prepare()
{
    static $canon_running = FALSE;
    static $options = NULL;
    static $rewriter = NULL;
    
    if (!$canon_running) {
        $options = new Options(__FILE__);
        $rewriter = new Rewriter($options);
        $canon_running = TRUE;
    }

    return [$rewriter,$options];
}

// Shortcode [canon-fixme $TYPE="$ID"]

function canon_shortcode_fixme($attr)
{
    list($rewriter,$options) = canon_prepare();
    $b = new Block();
    return $b->FixMeLink(@$attr['type'], @$attr['id']);
}

// Shortcode [canon-link $TYPE="$ID|list"]

function canon_shortcode_link($attr)
{
    list($rewriter,$options) = canon_prepare();
    return '/';
}

// Shortcode [canon-placeholder]

function canon_shortcode_placeholder($attr)
{
    list($rewriter,$options) = canon_prepare();
    
    $db = $rewriter->DB();
    $f = new Formatter();
    $page = $rewriter->Page();
    $id = $rewriter->ID();
    $here = $options->Get('here');
    
    //  /(person|branch|award|event|reign|op)/###           --> if 0, list
    //  /(person|branch|award|event|reign|op)/list
    //  /recommend/###                                      --> if 0, general recommendation form
    //  /current/(crown|gentry)
    //  /provost
    
    switch ($page) {
        case 'person':
            return +$id ? $f->FormatPerson($db->GetPerson($id)) : $f->FormatPersonList($db->GetPersonList());
        case 'branch':
            return +$id ? $f->FormatBranch($db->GetBranch($id)) : $f->FormatBranchList($db->GetBranchList());
        case 'award':
            return +$id ? $f->FormatAward($db->GetAward($id)) : $f->FormatAwardList($db->GetAwardList(), $here);
        case 'event':
            return +$id ? $f->FormatEvent($db->GetEvent($id)) : $f->FormatEventList($db->GetEventList());
        case 'reign':
            return +$id ? $f->FormatReign($db->GetReign($id)) : $f->FormatReignList($db->GetReignList(), $here);
        case 'op':
            return +$id ? $f->FormatOP($db->GetOP($id)) : $f->FormatOPList($db->GetOPList());
        case 'recommend':
            return +$id ? $f->FormatRecommendation($db->GetPerson($id)) : $f->FormatRecommendation(NULL);
        case 'current':
            return $f->FormatCurrentHats($db->GetCurrentHats($id == 'crown'));
        case 'provost':
            return $f->FormatProvostReport();
        default:
            return sprintf('<b>Canon Lore 2.0</b>');
    }
}

function canon_shortcode_recent($attr)
{
    list($rewriter,$options) = canon_prepare();
    return '<b>Coming Soon</b>';
}

// Utility function used by canon_header_title and canon_page_title

function canon_title($title)
{
    list($rewriter,$options) = canon_prepare();
    return $rewriter->Title($title);
}



