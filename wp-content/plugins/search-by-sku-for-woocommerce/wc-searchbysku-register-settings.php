<?php

add_filter('plugin_row_meta', 'support_and_faq_links', 10, 4);

function support_and_faq_links($links_array, $plugin_file_name, $plugin_data, $status)
{
    $my_plugin = 'search-by-model-for-woocommerce';

    if (strpos($plugin_file_name, $my_plugin) !== false) {
?>
        <style>
            .unroll-button {
                color: #fff;
                padding-left: 1.25rem;
                padding-right: 1.25rem;
                padding-top: .25rem;
                padding-bottom: .25rem;
                line-height: 1.5rem;
                font-weight: 500;
                border-width: 1px;
                font-weight: bold;
                border-color: transparent;
                background-color: #3182ce;
                border-radius: .375rem;
            }

            .unroll-button:hover {
                background-color: #4299e1;
                color: #fff;
            }
        </style>
<?php

        $links_array[] = '<a class="unroll-button" href="https://www.3niinfotech.com/">Get Expert Help</a>';
    }

    return $links_array;
}
