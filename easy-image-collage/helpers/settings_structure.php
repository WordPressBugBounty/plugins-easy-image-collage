<?php

$settings_structure = array(
	array(
		'id' => 'default_style',
		'name' => __( 'Default Style', 'easy-image-collage' ),
		'icon' => 'painting',
		'subGroups' => array(
			array(
				'name' => __( 'Display', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'default_style_display',
						'name' => __( 'Display Method', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'image' => __( 'Actual Images', 'easy-image-collage' ),
							'background' => __( 'Background Images (Legacy Mode)', 'easy-image-collage' ),
						),
						'default' => 'image',
					),
				),
			),
			array(
				'name' => __( 'Defaults', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'default_style_grid_align',
						'name' => __( 'Grid Alignment', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'left' => __( 'Align', 'easy-image-collage' ) . ': ' . __( 'left', 'easy-image-collage' ),
							'center' => __( 'Align', 'easy-image-collage' ) . ': ' . __( 'center', 'easy-image-collage' ),
							'right' => __( 'Align', 'easy-image-collage' ) . ': ' . __( 'right', 'easy-image-collage' ),
						),
						'default' => 'center',
					),
						array(
							'id' => 'default_style_grid_width',
							'name' => __( 'Grid Width', 'easy-image-collage' ),
							'description' => __( 'Minimum: 150. Maximum: 2000.', 'easy-image-collage' ),
							'type' => 'number',
							'min' => 150,
							'max' => 2000,
						'step' => 1,
						'default' => 500,
					),
						array(
							'id' => 'default_style_grid_ratio',
							'name' => __( 'Grid Ratio', 'easy-image-collage' ),
							'description' => __( 'Minimum: 0.25. Maximum: 4.', 'easy-image-collage' ),
							'type' => 'number',
							'min' => 0.25,
							'max' => 4,
						'step' => 0.05,
						'default' => 1,
					),
						array(
							'id' => 'default_style_border_width',
							'name' => __( 'Border Width', 'easy-image-collage' ),
							'description' => __( 'Minimum: 0. Maximum: 20.', 'easy-image-collage' ),
							'type' => 'number',
							'min' => 0,
							'max' => 20,
						'step' => 1,
						'default' => 4,
					),
					array(
						'id' => 'default_style_border_color',
						'name' => __( 'Border Color', 'easy-image-collage' ),
						'type' => 'color',
						'default' => '#444444',
					),
						array(
							'id' => 'default_style_border_radius',
							'name' => __( 'Border Radius', 'easy-image-collage' ),
							'description' => __( 'Minimum: 0. Maximum: 250.', 'easy-image-collage' ),
							'type' => 'number',
							'min' => 0,
							'max' => 250,
						'step' => 1,
						'default' => 0,
					),
				),
			),
		),
	),
	array(
		'id' => 'responsive',
		'name' => __( 'Responsive Layout', 'easy-image-collage' ),
		'icon' => 'arrows',
		'subGroups' => array(
			array(
				'name' => __( 'General', 'easy-image-collage' ),
				'settings' => array(
						array(
							'id' => 'responsive_breakpoint',
							'name' => __( 'Responsive Breakpoint', 'easy-image-collage' ),
							'description' => __( "The width of the collage at which will be switched to the mobile version. Make sure it's not larger than the initial width. Minimum: 10. Maximum: 1000.", 'easy-image-collage' ),
							'type' => 'number',
							'min' => 10,
							'max' => 1000,
						'step' => 1,
						'default' => 300,
					),
					array(
						'id' => 'responsive_layout',
						'name' => __( 'Show regular images on mobile', 'easy-image-collage' ),
						'description' => __( 'Prevent a collage from becoming to small by having regular images on mobile. Important: this will only work with "Actual Images" display mode.', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
					),
				),
			),
			array(
				'name' => __( 'Captions', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'responsive_hide_captions',
						'name' => __( 'Hide on mobile', 'easy-image-collage' ),
						'description' => __( 'Prevent captions from blocking the image by hiding them on mobile.', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
					),
				),
			),
		),
	),
	array(
		'id' => 'lightbox',
		'name' => __( 'Lightbox', 'easy-image-collage' ),
		'icon' => 'modal',
		'subGroups' => array(
			array(
				'name' => __( 'General', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'clickable_images',
						'name' => __( 'Clickable Images', 'easy-image-collage' ),
						'description' => __( 'Best used in combination with a lightbox plugin.', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
					),
					array(
						'id' => 'clickable_images_new_tab',
						'name' => __( 'Open in new tab', 'easy-image-collage' ),
						'description' => __( 'Open clickable images in new tab.', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
						'dependency' => array(
							'id' => 'clickable_images',
							'value' => true,
						),
					),
				),
			),
			array(
				'name' => __( 'Advanced', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'lightbox_class',
						'name' => __( 'Link class', 'easy-image-collage' ),
						'description' => __( 'Class to be added to the lightbox link.', 'easy-image-collage' ),
						'type' => 'text',
						'default' => '',
					),
					array(
						'id' => 'lightbox_rel',
						'name' => __( 'Link rel', 'easy-image-collage' ),
						'description' => __( 'Rel value of the lightbox link.', 'easy-image-collage' ),
						'type' => 'text',
						'default' => 'lightbox',
					),
				),
			),
		),
	),
	array(
		'id' => 'captions',
		'name' => __( 'Captions', 'easy-image-collage' ),
		'icon' => 'text',
		'required' => 'premium',
		'subGroups' => array(
			array(
				'name' => __( 'General', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'captions_autofill',
						'name' => __( 'Autofill Captions', 'easy-image-collage' ),
						'description' => __( 'Automatically fill caption when adding an image to the grid.', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'disabled' => __( 'Disabled', 'easy-image-collage' ),
							'title' => __( 'Image title', 'easy-image-collage' ),
							'caption' => __( 'Image caption', 'easy-image-collage' ),
							'alt' => __( 'Image alt', 'easy-image-collage' ),
						),
						'default' => 'disabled',
					),
				),
			),
			array(
				'name' => __( 'Appearance', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'captions_hover_only',
						'name' => __( 'Show on Hover Only', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => true,
					),
					array(
						'id' => 'captions_location',
						'name' => __( 'Location', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'top' => __( 'Top', 'easy-image-collage' ),
							'bottom' => __( 'Bottom', 'easy-image-collage' ),
						),
						'default' => 'bottom',
					),
					array(
						'id' => 'captions_text_alignment',
						'name' => __( 'Text Alignment', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'left' => __( 'Left', 'easy-image-collage' ),
							'center' => __( 'Center', 'easy-image-collage' ),
							'right' => __( 'Right', 'easy-image-collage' ),
						),
						'default' => 'left',
					),
						array(
							'id' => 'captions_font_size',
							'name' => __( 'Font Size', 'easy-image-collage' ),
							'description' => __( 'Minimum: 6. Maximum: 60.', 'easy-image-collage' ),
							'type' => 'number',
							'min' => 6,
							'max' => 60,
						'step' => 1,
						'default' => 12,
					),
					array(
						'id' => 'captions_text_color',
						'name' => __( 'Text Color', 'easy-image-collage' ),
						'type' => 'color',
						'default' => 'rgba(255,255,255,1)',
					),
					array(
						'id' => 'captions_background_color',
						'name' => __( 'Background Color', 'easy-image-collage' ),
						'type' => 'color',
						'default' => 'rgba(0,0,0,0.7)',
					),
				),
			),
		),
	),
	array(
		'id' => 'social',
		'name' => __( 'Social Media', 'easy-image-collage' ),
		'icon' => 'share',
		'subGroups' => array(
			array(
				'name' => __( 'Pinterest', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'pinterest_enable',
						'name' => __( 'Pinterest on Hover', 'easy-image-collage' ),
						'description' => __( 'Show a Pinterest button when hovering over images.', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
					),
					array(
						'id' => 'pinterest_location',
						'name' => __( 'Button Location', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'top_left' => __( 'Top Left', 'easy-image-collage' ),
							'top_right' => __( 'Top Right', 'easy-image-collage' ),
							'bottom_left' => __( 'Bottom Left', 'easy-image-collage' ),
							'bottom_right' => __( 'Bottom Right', 'easy-image-collage' ),
						),
						'default' => 'top_left',
					),
					array(
						'id' => 'pinterest_style',
						'name' => __( 'Button Style', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'default' => __( 'Default', 'easy-image-collage' ),
							'red' => __( 'Red', 'easy-image-collage' ),
							'white' => __( 'White', 'easy-image-collage' ),
							'round' => __( 'Round', 'easy-image-collage' ),
						),
						'default' => 'default',
					),
					array(
						'id' => 'pinterest_size',
						'name' => __( 'Button Size', 'easy-image-collage' ),
						'type' => 'dropdown',
						'options' => array(
							'default' => __( 'Default', 'easy-image-collage' ),
							'large' => __( 'Large', 'easy-image-collage' ),
						),
						'default' => 'default',
					),
					array(
						'id' => 'pinterest_description',
						'name' => __( 'Description', 'easy-image-collage' ),
						'description' => __( 'You can use the following placeholders:', 'easy-image-collage' ) . ' %title% %alt% %caption%',
						'type' => 'text',
						'default' => '%title%',
					),
				),
			),
		),
	),
	array(
		'id' => 'custom_links',
		'name' => __( 'Custom Links', 'easy-image-collage' ),
		'icon' => 'link',
		'required' => 'premium',
		'subGroups' => array(
			array(
				'name' => __( 'Defaults', 'easy-image-collage' ),
				'settings' => array(
					array(
						'id' => 'custom_link_new_tab',
						'name' => __( 'Open in New Tab', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
					),
					array(
						'id' => 'custom_link_nofollow',
						'name' => __( 'Use Nofollow', 'easy-image-collage' ),
						'type' => 'toggle',
						'default' => false,
					),
				),
			),
		),
	),
	array(
		'id' => 'custom_code',
		'name' => __( 'Custom Code', 'easy-image-collage' ),
		'icon' => 'code',
		'settings' => array(
			array(
				'id' => 'custom_code_public_css',
				'name' => __( 'Public CSS', 'easy-image-collage' ),
				'type' => 'code',
				'code' => 'css',
				'default' => '',
			),
		),
	),
);
