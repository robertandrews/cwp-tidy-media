<?php

$mytaxonomy = 'company';

$terms = get_terms(array(
    'taxonomy' => $mytaxonomy,
    'hide_empty' => false,
    // only 5
    // 'number' => 500,
));

// loop through all terms, re-saving each to trigger the edit_term hook
foreach ($terms as $term) {
    echo $term->slug . "\n";
    wp_update_term($term->term_id, $mytaxonomy, array(
        'name' => $term->name,
        'slug' => $term->slug,
    ));
}
