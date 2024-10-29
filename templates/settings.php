<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap">
    <h2>Amplitude Settings</h2>
    <form method="post" action="options.php">
      <?php
        settings_fields( self::SLUG );
        do_settings_sections( self::SLUG );
        submit_button();
      ?>
    </form>
</div>