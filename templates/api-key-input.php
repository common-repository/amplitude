<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<input class="regular-text ltr" placeholder="enter your API key and select 'Save Changes' below" type="text" name="<?php echo esc_attr($name); ?>" id="api_key" value="<?php echo esc_attr( $settings['api_key'] ); ?>" />
    <p class="aampli-plg-des">To retrieve and save your API key:</p>
        <ol class="aampli-plg-des">
            <li><a href='https://app.amplitude.com/signup?utm_medium=referral&utm_source=wordpress' target='_blank'>Go to Amplitude</a> to login or create an account</li>
            <li>Navigate to Organization Settings</a></li>
            <li>Select Projects to view your projects</li>
            <li>Select the relevant project</li>
            <li>Copy the API Key (NOT your Secret Key)</li>
            <li>Paste it above and select 'Save Changes' below</li>
            <li>Return to Amplitude to view user activity on your site</li>
        </ol>