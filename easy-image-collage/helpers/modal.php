<div class="eic-modal">
    <div class="eic-layouts">
        <div class="eic-modal-header">
            <span class="eic-modal-title"><i class="fa fa-angle-double-down"></i> <?php _e( 'Choose layout', 'easy-image-collage' ); ?></span>
        </div>
        <div class="eic-modal-body eic-layout-picker">
            <div class="eic-container">
                <div class="eic-frame eic-frame-0 eic-frame-custom" data-layout-name="custom">
                    <div class="eic-image eic-image-0">
                        <div class="eic-text">
                            <span><?php _e( 'Create Your Own Layout', 'easy-image-collage' ); ?></span>
                            <span><?php if ( ! EasyImageCollage::is_premium_active() ) { _e( 'Premium Only', 'easy-image-collage' ); } ?></span>
                        </div>
                    </div>
                </div>
                <?php echo EasyImageCollage::get()->helper( 'layouts' )->draw_layouts( true ); ?>
                <div class="eic-custom-layouts">
                    <?php
                    foreach( $grid_custom_layouts as $layout_name => $grid_custom_layout ) {
                        $grid_custom_layout['name'] = $layout_name;
                        echo EasyImageCollage::get()->helper( 'layouts' )->draw_layout( $grid_custom_layout, false, true );
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="eic-creating">
        <?php
        if( EasyImageCollage::is_addon_active( 'custom-layout' ) ) {
            require( EasyImageCollage::addon( 'custom-layout' )->addonDir . '/modal.php' );
        } else {
            ?>
            <div class="eic-modal-header">
                <a href="#" class="eic-modal-title eic-modal-action eic-modal-action-secondary" onclick="event.preventDefault(); EasyImageCollage.btnChooseLayout()"><i class="fa fa-angle-double-left"></i> <?php _e( 'Back', 'easy-image-collage' ); ?></a>
            </div>
            <div class="eic-modal-body eic-upgrade-preview">
                <div class="eic-premium-only"><?php _e( 'This feature is only available in', 'easy-image-collage' ); ?> <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">Easy Image Collage Premium</a></div>
                <div class="creating-image-preview">
                    <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">
                        <img src="<?php echo EasyImageCollage::get()->coreUrl; ?>/img/custom_layout_preview.png" />
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="eic-editing">
        <div class="eic-modal-header">
	        <a href="#" class="eic-modal-title eic-modal-action eic-modal-action-secondary" onclick="event.preventDefault(); EasyImageCollage.btnChooseLayout()"><i class="fa fa-angle-double-left"></i> <?php _e( 'Change layout', 'easy-image-collage' ); ?></a>
	        <a href="#" class="eic-modal-title eic-modal-title-right eic-modal-action eic-modal-action-primary" onclick="event.preventDefault(); EasyImageCollage.btnFinish()"><?php _e( 'Finish', 'easy-image-collage' ); ?> <i class="fa fa-angle-double-right"></i></a>
        </div>
        <div class="eic-modal-body eic-editing-workspace">
	        <div class="eic-properties">
                <?php do_action( 'eic_modal_notices' ); ?>
                <div class="eic-settings-group">
                    <span class="eic-property-header"><?php _e( 'Grid', 'easy-image-collage' ); ?></span>
                    <div class="eic-setting-row eic-setting-row-name">
                        <label for="grid-name"><?php _e( 'Name', 'easy-image-collage' ); ?></label>
                        <input type="text" id="grid-name" value="" placeholder="<?php esc_attr_e( 'Collage #id', 'easy-image-collage' ); ?>">
                    </div>
                    <div class="eic-setting-row eic-setting-row-slider eic-setting-row-grid-width">
                        <label class="eic-setting-label" for="grid-width"><?php _e( 'Width', 'easy-image-collage' ); ?></label>
                        <input type="text" id="grid-width" value="500">
                        <span class="slider-value-container eic-adjustable-value eic-grid-width-value" role="button" tabindex="0" data-eic-tooltip="<?php esc_attr_e( 'Click to adjust', 'easy-image-collage' ); ?>" aria-label="<?php esc_attr_e( 'Click to adjust grid width', 'easy-image-collage' ); ?>"><span id="grid-width-value">500</span> px</span>
                    </div>
                    <div class="eic-setting-row eic-setting-row-slider">
                        <label class="eic-setting-label" for="grid-ratio"><?php _e( 'Ratio', 'easy-image-collage' ); ?></label>
                        <input type="text" id="grid-ratio" value="1">
                        <span class="slider-value-container eic-adjustable-value eic-grid-ratio-value" role="button" tabindex="0" data-eic-tooltip="<?php esc_attr_e( 'Click to adjust', 'easy-image-collage' ); ?>" aria-label="<?php esc_attr_e( 'Click to adjust grid ratio', 'easy-image-collage' ); ?>"><span id="grid-ratio-value">1</span></span>
                    </div>
                    <div class="eic-setting-row">
                        <select id="grid-align">
                            <optgroup label="<?php _e( 'Text above and below', 'easy-image-collage' ); ?>">
                                <option value="left"><?php _e( 'Align', 'easy-image-collage' ); ?>: <?php _e( 'left', 'easy-image-collage' ); ?></option>
                                <option value="center"><?php _e( 'Align', 'easy-image-collage' ); ?>: <?php _e( 'center', 'easy-image-collage' ); ?></option>
                                <option value="right"><?php _e( 'Align', 'easy-image-collage' ); ?>: <?php _e( 'right', 'easy-image-collage' ); ?></option>
                            </optgroup>
                            <optgroup label="<?php _e( 'Text around', 'easy-image-collage' ); ?>">
                                <option value="float-left"><?php _e( 'Float', 'easy-image-collage' ); ?>: <?php _e( 'left', 'easy-image-collage' ); ?></option>
                                <option value="float-right"><?php _e( 'Float', 'easy-image-collage' ); ?>: <?php _e( 'right', 'easy-image-collage' ); ?></option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="eic-settings-group">
                    <span class="eic-property-header"><?php _e( 'Borders', 'easy-image-collage' ); ?></span>
                    <div class="eic-setting-row eic-setting-row-slider">
                        <label class="eic-setting-label" for="border-width"><?php _e( 'Width', 'easy-image-collage' ); ?></label>
                        <input type="text" id="border-width" value="4">
                        <span class="slider-value-container eic-adjustable-value eic-border-width-value" role="button" tabindex="0" data-eic-tooltip="<?php esc_attr_e( 'Click to adjust', 'easy-image-collage' ); ?>" aria-label="<?php esc_attr_e( 'Click to adjust border width', 'easy-image-collage' ); ?>"><span id="border-width-value">4</span> px</span>
                    </div>
                    <div class="eic-setting-row eic-setting-row-slider">
                        <label class="eic-setting-label" for="border-radius"><?php _e( 'Radius', 'easy-image-collage' ); ?></label>
                        <input type="text" id="border-radius" value="0">
                        <span class="slider-value-container eic-adjustable-value eic-border-radius-value" role="button" tabindex="0" data-eic-tooltip="<?php esc_attr_e( 'Click to adjust', 'easy-image-collage' ); ?>" aria-label="<?php esc_attr_e( 'Click to adjust border radius', 'easy-image-collage' ); ?>"><span id="border-radius-value">0</span> px</span>
                    </div>
                    <div class="eic-setting-row eic-setting-row-color">
                        <label class="eic-setting-label" for="border-color"><?php _e( 'Color', 'easy-image-collage' ); ?></label>
                        <input type="color" id="border-color" value="#444444">
                    </div>
                    <div class="eic-setting-row eic-setting-row-checkboxes">
                        <label><input type="checkbox" id="border-change"> <?php _e( 'Adjust Borders', 'easy-image-collage' ); ?></label>
                    </div>
                </div>

                <div class="eic-settings-group eic-settings-group-last">
                    <span class="eic-property-header"><?php _e( 'Other', 'easy-image-collage' ); ?></span>
                    <div class="eic-setting-row eic-setting-row-checkboxes">
                        <label><input type="checkbox" id="image-size"> <?php _e( 'Show Image Size', 'easy-image-collage' ); ?></label>
                    </div>
                </div>
	            <div class="eic-premium-only"><?php _e( 'This feature is only available in', 'easy-image-collage' ); ?> <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">Easy Image Collage Premium</a></div>
            </div>
            <div class="eic-editing-canvas">
                <div class="eic-container">
                </div>
            </div>
        </div>
    </div>

	<div class="eic-manipulating">
		<?php
		if( EasyImageCollage::is_addon_active( 'image-manipulation' ) ) {
			require( EasyImageCollage::addon( 'image-manipulation' )->addonDir . '/modal.php' );
		} else {
		?>
        <div class="eic-modal-header">
		    <a href="#" class="eic-modal-title eic-modal-action eic-modal-action-secondary" onclick="event.preventDefault(); EasyImageCollage.setActivePage('editing')"><i class="fa fa-angle-double-left"></i> <?php _e( 'Cancel', 'easy-image-collage' ); ?></a>
        </div>
        <div class="eic-modal-body eic-upgrade-preview">
		    <div class="eic-premium-only"><?php _e( 'This feature is only available in', 'easy-image-collage' ); ?> <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">Easy Image Collage Premium</a></div>
            <div class="manipulating-image-preview">
                <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">
                    <img src="<?php echo EasyImageCollage::get()->coreUrl; ?>/img/manipulate_image_preview.png" />
                </a>
            </div>
        </div>
		<?php } ?>
	</div>

    <div class="eic-links">
        <?php
        if( EasyImageCollage::is_addon_active( 'custom-links' ) ) {
            require( EasyImageCollage::addon( 'custom-links' )->addonDir . '/modal.php' );
        } else {
            ?>
            <div class="eic-modal-header">
                <a href="#" class="eic-modal-title eic-modal-action eic-modal-action-secondary" onclick="event.preventDefault(); EasyImageCollage.setActivePage('editing')"><i class="fa fa-angle-double-left"></i> <?php _e( 'Cancel', 'easy-image-collage' ); ?></a>
            </div>
            <div class="eic-modal-body eic-upgrade-preview">
                <div class="eic-premium-only"><?php _e( 'This feature is only available in', 'easy-image-collage' ); ?> <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">Easy Image Collage Premium</a></div>
                <?php // TODO ?>
                <div class="custom-links-preview">
                    <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">
                        <img src="<?php echo EasyImageCollage::get()->coreUrl; ?>/img/custom_links_preview.png" />
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="eic-captions">
        <?php
        if( EasyImageCollage::is_addon_active( 'captions' ) ) {
            require( EasyImageCollage::addon( 'captions' )->addonDir . '/modal.php' );
        } else {
            ?>
            <div class="eic-modal-header">
                <a href="#" class="eic-modal-title eic-modal-action eic-modal-action-secondary" onclick="event.preventDefault(); EasyImageCollage.setActivePage('editing')"><i class="fa fa-angle-double-left"></i> <?php _e( 'Cancel', 'easy-image-collage' ); ?></a>
            </div>
            <div class="eic-modal-body eic-upgrade-preview">
                <div class="eic-premium-only"><?php _e( 'This feature is only available in', 'easy-image-collage' ); ?> <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">Easy Image Collage Premium</a></div>
                <?php // TODO ?>
                <div class="captions-preview">
                    <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">
                        <img src="<?php echo EasyImageCollage::get()->coreUrl; ?>/img/captions_preview.png" />
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="eic-text-frames">
        <?php
        if( EasyImageCollage::is_addon_active( 'text-frames' ) ) {
            require( EasyImageCollage::addon( 'text-frames' )->addonDir . '/modal.php' );
        } else {
            ?>
            <div class="eic-modal-header">
                <a href="#" class="eic-modal-title eic-modal-action eic-modal-action-secondary" onclick="event.preventDefault(); EasyImageCollage.setActivePage('editing')"><i class="fa fa-angle-double-left"></i> <?php _e( 'Cancel', 'easy-image-collage' ); ?></a>
            </div>
            <div class="eic-modal-body eic-upgrade-preview">
                <div class="eic-premium-only"><?php _e( 'This feature is only available in', 'easy-image-collage' ); ?> <a href="http://bootstrapped.ventures/easy-image-collage/" target="_blank">Easy Image Collage Premium</a></div>
            </div>
        <?php } ?>
    </div>
</div>
