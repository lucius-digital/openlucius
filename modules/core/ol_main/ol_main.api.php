<?php

/**
 * Define hook for other modules to add sections in groups.
 * @param array $sections
 */
function hook_add_ol_section(array &$sections) {
}

/**
 * Define hook for other modules to add global menu items.
 * @param array $sections
 */
function hook_add_global_menu_item(array &$sections) {
}

/**
 * Define hook for other modules to alter default group types.
 * @param array $group_types
 */
function hook_alter_group_types(array &$group_types) {
}

/**
 * Define hook for other modules to alter default site name.
 *
 * @param string $site_name
 */
function hook_alter_site_name(&$site_name) {
}

/**
 * Define hook for other modules to alter default site logo.
 *
 * @param string $site_logo
 */
function hook_alter_site_logo(&$site_logo) {
}
/**
 * Define hook for other modules to add counts for sections nav badges.
 *
 * @param $stream_item
 */
function hook_sections_badges_count($stream_item) {
}

/**
 * Define hook for other modules to add links for stream items.
 *
 * @param $stream_item
 * @param $gid
 */
function hook_add_comment_links($stream_item, $gid) {
}

/**
 * Define hook for other modules to add links in top right menu.
 *
 * @param array $items
 */
function hook_add_menu_top_right_links(array &$items) {
}

/**
 * Define hook for other modules to add links in user menu.
 *
 * @param array $user_menu_items
 */
function hook_add_user_menu_links(array &$user_menu_items) {
}

/**
 * Define hook for other modules to add links in the bottom user menu.
 *
 * @param array $user_menu_items_bottom
 */
function hook_add_user_menu_links_bottom(array &$user_menu_items_bottom) {
}

/**
 * Define hook for other modules to add homepage tabs.
 *
 * @param array $tabs
 */
function hook_add_home_tab(array &$tabs) {
}

/**
 * Define hook for other modules to provide homepage content when active
 *
 * @param $active_tab
 */
function hook_provide_home_tab_content($active_tab) {
}
