<?php
/*
Keeping a separate file for the widgets for orgaization purposes
*/

class pum_Cloud_Widget extends WP_Widget {
	function pum_Cloud_Widget() {
		$widget_ops = array( 'classname' => 'pum-status-cloud', 'description' => 'Displays Uptime Robot Statuses on the sidebar' );
		$this->WP_Widget( 'pum_cloud', 'Uptime Robot Status Cloud', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		echo $before_widget;
		$title		= empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		//$count		= empty($instance['count']) ? 1 : $instance['count'];
		//$seemore	= empty($instance['seemore']) ? '...' : $instance['seemore'];
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
		$json = pum_get_data();
		foreach ($json->monitors->monitor as $monitor) {
			echo '<span class="pum stat'.$monitor->status.'">'.$monitor->friendlyname.'</span> ';
		}
		echo $after_widget;
		?>

        <?php }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
    	$instance = $old_instance;
    	$instance['title']		= strip_tags($new_instance['title']);
    	$instance['seemore']	= strip_tags($new_instance['seemore']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $instance = wp_parse_args( (array) $instance, array(
            'title'		=> 'Status Cloud'
            ));
        $title		= strip_tags($instance['title']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Widget Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
	<?php }

} // class

add_action( 'widgets_init', create_function( '', "register_widget('pum_Cloud_Widget');" ) );
