<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Empty Placeholder Text
    |--------------------------------------------------------------------------
    |
    | The text to display when a column has no value.
    |
    */

    'placeholder' => env('ACTIONABLE_COLUMN_PLACEHOLDER', 'N/A'),

    /*
    |--------------------------------------------------------------------------
    | Custom CSS Path
    |--------------------------------------------------------------------------
    |
    | Path to a custom CSS file loaded after the default styles.
    | Prevents overwrites during composer operations.
    | relative to the project root
    | Ex:
    | ACTIONABLE_COLUMN_CUSTOM_CSS_PATH=resources/css/actionable-column-custom.css
    */

    'custom_css_path' => env('ACTIONABLE_COLUMN_CUSTOM_CSS_PATH', null),
];
