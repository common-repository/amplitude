<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<input name="<?php echo esc_attr( $name ); ?>" type="checkbox" id="server_zone_eu" value="1" <?php checked(1, $settings['server_zone_eu']); ?>/>