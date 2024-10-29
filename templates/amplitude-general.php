<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php
    ?>
    <h2>
    <?php
    if($this->get_api_key()) {
        ?>
        Head on over to <a target="_blank" href='https://app.amplitude.com/login?utm_medium=referral&utm_source=wordpress'>Amplitude</a> to view your data!
        </h2>
        <?php
    } else if (current_user_can('manage_options')) {
        ?>
            <p>Enter your API key in <a href="<?php echo esc_url($settings_page_url); ?>">Settings</a> to begin tracking events with Amplitude Analytics!</p>
            <p><a class="button-primary" href="<?php echo esc_url($settings_page_url); ?>">Connect Site to Amplitude</a></p>
        <?php
    } else {
        ?>
            <p>Contact your Wordpress administrator to connect your site to Amplitude on the <a href="<?php echo esc_url($settings_page_url); ?>">Settings</a> page to view data.</p>
        <?php
    }
    ?>
    
    <?php
?>