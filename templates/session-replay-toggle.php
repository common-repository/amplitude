<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<script>
function checkboxChanged(checkbox) {
    const sampleRateSlider = document.getElementById('aampl_plg_sample_rate');
    if(checkbox.checked) {
        sampleRateSlider.disabled = false;
    } else {
        sampleRateSlider.disabled = true;
    }
}
</script>
<input name="<?php echo esc_attr( $name ); ?>" type="checkbox" id="session_replay_enabled" value="1" <?php checked(1, $settings['session_replay_enabled']); ?> onchange="checkboxChanged(this)"/>