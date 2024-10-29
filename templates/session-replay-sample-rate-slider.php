<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="aampl-custom-class">
<input type="range" min="0" max="100" step="1" value="<?php echo esc_attr($this->get_session_replay_sample_rate()); ?>" id="aampl_plg_sample_rate" oninput="updateAampliPlgSessionSampleRate(this.value)" <?php echo esc_attr($interaction_string) ?>/>

<input type="hidden" name="<?php echo esc_attr( $name); ?>_hidden" value="<?php echo esc_attr($this->get_session_replay_sample_rate()); ?>" id="aampli-plg-sample-rate-hidden"/>


<span id="aampl-plg-sample-rate-display" class="aampli-plg-des">
<?php echo esc_attr($this->get_session_replay_sample_rate()); ?>
</span>
<span class="aampli-plg-des">
    %
</span>
<script>
    function updateAampliPlgSessionSampleRate(value) {
        document.getElementById('aampl-plg-sample-rate-display').textContent = value;
        document.getElementById('aampli-plg-sample-rate-hidden').value = value;
    }
</script>
</div>
