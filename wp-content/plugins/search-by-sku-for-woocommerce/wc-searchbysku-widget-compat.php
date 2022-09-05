<?php

/**
 * A drop in replacement of WC_Admin_Post_Types::product_search()
 */
add_filter('posts_clauses', 'product_search_sku', 11, 1);
function product_search_sku($args)
{
    $where = $args['where'];
    global $pagenow, $wpdb, $wp;

    if ((is_admin() && 'edit.php' != $pagenow)
        || !is_search()
        || !isset($wp->query_vars['s'])
        //post_types can also be arrays..
        || (isset($wp->query_vars['post_type']) && 'product' != $wp->query_vars['post_type'])
        || (isset($wp->query_vars['post_type']) && is_array($wp->query_vars['post_type']) && !in_array('product', $wp->query_vars['post_type']))
    ) {
        return $args;
    }
    $search_ids = array();
    $terms = explode(',', $wp->query_vars['s']);

    foreach ($terms as $term) {
        $term = trim($term);
        //Include the search by id if admin area.
        if (is_admin() && is_numeric($term)) {
            $search_ids[] = $term;
        }
        // search for variations with a matching sku and return the parent.

        $sku_to_parent_id = $wpdb->get_col($wpdb->prepare("SELECT p.post_parent as post_id FROM {$wpdb->posts} as p join {$wpdb->postmeta} pm on p.ID = pm.post_id and pm.meta_key='_tni_model' and pm.meta_value LIKE '%%%s%%' where p.post_parent <> 0 group by p.post_parent", wc_clean($term)));

        //Search for a regular product that matches the sku.
        $sku_to_id = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tni_model' AND meta_value LIKE '%%%s%%';", wc_clean($term)));

        $search_ids = array_merge($search_ids, $sku_to_id, $sku_to_parent_id);
    }

    $search_ids = array_unique(array_filter(array_map('absint', $search_ids)));
    if (sizeof($search_ids) > 0) {
        $where = str_replace('))', ") OR ({$wpdb->posts}.ID IN (" . implode(',', $search_ids) . ")))", $where);
    }
    $args['where'] = $where;
    remove_filters_for_anonymous_class('posts_search', 'WC_Admin_Post_Types', 'product_search', 10);
    return $args;
}
