var EasyImageCollage = EasyImageCollage || {};

/**
 * Variables
 */
EasyImageCollage.spinner = '<div class="eic-spinner"><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div><div class="rect5"></div></div>';
EasyImageCollage.file_frame = undefined;
EasyImageCollage.editing_image = undefined;
EasyImageCollage.manipulating_image = undefined;
EasyImageCollage.editing_grid = {};
EasyImageCollage.callback = false;
EasyImageCollage.lightbox_settings = {
    namespace: 'eic-lightbox',
    closeOnClick: false,
    closeOnEsc: false,
    afterOpen: function() {
        var lightbox = EasyImageCollage.getLightbox();
        var gridName = EasyImageCollage.editing_grid.name || '';

        lightbox.find('#grid-name')
            .val(gridName)
            .attr('placeholder', EasyImageCollage.getDefaultGridName(EasyImageCollage.editing_grid.id))
            .off('input change')
            .on('input change', function() {
                EasyImageCollage.editing_grid.name = jQuery(this).val();
            });

        // Alignment
        lightbox.find('#grid-align')
            .off('change.eicGridAlign')
            .on('change.eicGridAlign', function() {
                EasyImageCollage.editing_grid.properties.align = jQuery(this).val();
                EasyImageCollage.redrawAlignment();
            });


        // Border color - init and bind event
        var borderColorInput = jQuery('.eic-lightbox #border-color');

        if (!borderColorInput.hasClass('wp-color-picker')) {
            borderColorInput
                .wpColorPicker({
                    change: function () {
                        EasyImageCollage.editing_grid.properties.borderColor = jQuery(this).wpColorPicker('color');
                        EasyImageCollage.redrawBorders();
                    }
                })
            ;
        }

        // Grid width - init and bind event
        EasyImageCollage.initializeSliderControl('#grid-width', EasyImageCollage.editing_grid.properties.width, {
            range: [150,2000],
            step: 1,
            snap: true
        }, 'eicGridWidth', function (event, data) {
            jQuery('.eic-lightbox #grid-width-value').html(''+data.value);
            EasyImageCollage.editing_grid.properties.width = data.value;
            EasyImageCollage.redrawGrid();
        });

        var bindPromptValue = function(selector, promptText, getCurrent, normalize, setValue) {
            jQuery('.eic-lightbox ' + selector).off('click keydown').on('click keydown', function(e) {
                if ('keydown' === e.type && e.key !== 'Enter' && e.key !== ' ') {
                    return;
                }

                e.preventDefault();

                var value = window.prompt(promptText, getCurrent());

                if (value === null) {
                    return;
                }

                value = normalize(value);

                if (false === value) {
                    return;
                }

                setValue(value);
            });
        };

        // Grid width direct value input.
        bindPromptValue('.eic-grid-width-value', 'Enter grid width in pixels', function() {
            return parseInt(jQuery('.eic-lightbox #grid-width').val(), 10) || EasyImageCollage.editing_grid.properties.width;
        }, function(value) {
            value = parseInt(('' + value).replace(',', '.'), 10);

            if (isNaN(value)) {
                return false;
            }

            return Math.max(150, Math.min(2000, value));
        }, function(value) {
            jQuery('.eic-lightbox #grid-width').simpleSlider('setValue', value);
        });

        // Grid ratio - init and bind event
        EasyImageCollage.initializeSliderControl('#grid-ratio', EasyImageCollage.editing_grid.properties.ratio, {
            range: [0.25,4],
            step: 0.05,
            snap: true
        }, 'eicGridRatio', function (event, data) {
            var ratio = parseFloat(data.value.toFixed(2));
            jQuery('.eic-lightbox #grid-ratio-value').html(''+ratio);
            EasyImageCollage.editing_grid.properties.ratio = ratio;
            EasyImageCollage.redrawGrid();
        });

        bindPromptValue('.eic-grid-ratio-value', 'Enter grid ratio', function() {
            return parseFloat(jQuery('.eic-lightbox #grid-ratio').val()) || EasyImageCollage.editing_grid.properties.ratio;
        }, function(value) {
            value = parseFloat(('' + value).replace(',', '.'));

            if (isNaN(value)) {
                return false;
            }

            value = Math.max(0.25, Math.min(4, value));
            return parseFloat((Math.round(value / 0.05) * 0.05).toFixed(2));
        }, function(value) {
            jQuery('.eic-lightbox #grid-ratio').simpleSlider('setValue', value);
        });

        // Border width - init and bind event
        EasyImageCollage.initializeSliderControl('#border-width', EasyImageCollage.editing_grid.properties.borderWidth, {
            range: [0,20],
            step: 1,
            snap: true
        }, 'eicBorderWidth', function (event, data) {
            jQuery('.eic-lightbox #border-width-value').html(''+data.value*2);
            EasyImageCollage.editing_grid.properties.borderWidth = data.value;
            EasyImageCollage.redrawBorders();
        });

        bindPromptValue('.eic-border-width-value', 'Enter border width in pixels', function() {
            var current = parseInt(jQuery('.eic-lightbox #border-width').val(), 10);
            return (isNaN(current) ? EasyImageCollage.editing_grid.properties.borderWidth : current) * 2;
        }, function(value) {
            value = parseInt(('' + value).replace(',', '.'), 10);

            if (isNaN(value)) {
                return false;
            }

            value = Math.max(0, Math.min(40, value));
            return Math.round(value / 2);
        }, function(value) {
            jQuery('.eic-lightbox #border-width').simpleSlider('setValue', value);
        });

        // Border radius - init and bind event
        EasyImageCollage.initializeSliderControl('#border-radius', EasyImageCollage.editing_grid.properties.borderRadius, {
            range: [0,250],
            step: 1,
            snap: true
        }, 'eicBorderRadius', function (event, data) {
            jQuery('.eic-lightbox #border-radius-value').html(''+data.value);
            EasyImageCollage.editing_grid.properties.borderRadius = data.value;
            EasyImageCollage.redrawBorderRadius();
        });

        bindPromptValue('.eic-border-radius-value', 'Enter border radius in pixels', function() {
            var current = parseInt(jQuery('.eic-lightbox #border-radius').val(), 10);
            return isNaN(current) ? EasyImageCollage.editing_grid.properties.borderRadius : current;
        }, function(value) {
            value = parseInt(('' + value).replace(',', '.'), 10);

            if (isNaN(value)) {
                return false;
            }

            return Math.max(0, Math.min(250, value));
        }, function(value) {
            jQuery('.eic-lightbox #border-radius').simpleSlider('setValue', value);
        });

        // Border Adjustments
        jQuery('.eic-lightbox #border-change').off('change.eicBorderChange').on('change.eicBorderChange', function() {
            if(jQuery(this).is(':checked')) {
                jQuery('.eic-lightbox .eic-divider').show();
                if (typeof EasyImageCollage.redrawDividers !== 'function') {
                    jQuery('.eic-lightbox .eic-editing .eic-premium-only').show();
                }
            } else {
                jQuery('.eic-lightbox .eic-divider').hide();
                jQuery('.eic-lightbox .eic-editing .eic-premium-only').hide();
            }
        });

        // Show Image size
        jQuery('.eic-lightbox #image-size').off('change.eicImageSize').on('change.eicImageSize', function() {
            if(jQuery(this).is(':checked')) {
                jQuery('.eic-lightbox .eic-image-size').css('display','inline-block');
                if (typeof EasyImageCollage.recalculateSizes !== 'function') {
                    jQuery('.eic-lightbox .eic-editing .eic-premium-only').show();
                }
            } else {
                jQuery('.eic-lightbox .eic-image-size').css('display','none');
                jQuery('.eic-lightbox .eic-editing .eic-premium-only').hide();
            }
        });

        EasyImageCollage.syncEditingControls();
    }
};

/** Variables from PHP
 *
 */
EasyImageCollage.grids = {};
EasyImageCollage.default_grid = {};

EasyImageCollage.initializeSliderControl = function(selector, value, options, namespace, onChange) {
    var input = jQuery('.eic-lightbox ' + selector);

    if (!input.length) {
        return;
    }

    input
        .off('slider:changed.' + namespace)
        .on('slider:changed.' + namespace, onChange);

    if (input.data('slider-object')) {
        EasyImageCollage.syncSliderValue(input, value);
    } else {
        input
            .val(value)
            .simpleSlider(options);
    }
};

EasyImageCollage.syncSliderValue = function(input, value) {
    var slider = input.data('slider-object');

    if (!slider) {
        input.val(value);
        return;
    }

    value = slider.nearestValidValue(value);
    input.val(value);
    slider.value = value;
    slider.setSliderPositionFromValue(value);
};

EasyImageCollage.syncEditingControls = function() {
    if (!EasyImageCollage.editing_grid || !EasyImageCollage.editing_grid.properties) {
        return;
    }

    var properties = EasyImageCollage.editing_grid.properties;
    var lightbox = jQuery('.eic-lightbox');

    lightbox.find('#grid-align').val(properties.align);

    EasyImageCollage.syncSliderValue(lightbox.find('#grid-width'), properties.width);
    lightbox.find('#grid-width-value').html('' + properties.width);

    EasyImageCollage.syncSliderValue(lightbox.find('#grid-ratio'), properties.ratio);
    lightbox.find('#grid-ratio-value').html('' + properties.ratio);

    EasyImageCollage.syncSliderValue(lightbox.find('#border-width'), properties.borderWidth);
    lightbox.find('#border-width-value').html('' + properties.borderWidth * 2);

    EasyImageCollage.syncSliderValue(lightbox.find('#border-radius'), properties.borderRadius);
    lightbox.find('#border-radius-value').html('' + properties.borderRadius);

    var borderColorInput = lightbox.find('#border-color');
    if (borderColorInput.hasClass('wp-color-picker')) {
        borderColorInput.wpColorPicker('color', properties.borderColor);
    } else {
        borderColorInput.val(properties.borderColor);
    }
};

EasyImageCollage.getDefaultGridProperties = function() {
    var defaultGrid = EasyImageCollage.default_grid || {};

    return jQuery.extend(true, {
        align: 'center',
        width: 500,
        ratio: 1,
        borderWidth: 4,
        borderColor: '#444444',
        borderRadius: 0
    }, defaultGrid.properties || {});
};

EasyImageCollage.normalizeGrid = function(grid, id) {
    var defaultGrid = EasyImageCollage.default_grid || {};
    var normalized = jQuery.extend(true, {}, defaultGrid, grid || {});
    var images = [];

    normalized.id = id !== undefined ? parseInt(id, 10) || 0 : parseInt(normalized.id, 10) || 0;
    normalized.name = normalized.name !== undefined && normalized.name !== null ? '' + normalized.name : '';
    normalized.layout = normalized.layout !== undefined ? normalized.layout : 'square';
    normalized.properties = jQuery.extend(true, EasyImageCollage.getDefaultGridProperties(), normalized.properties || {});

    if (jQuery.isArray(normalized.images)) {
        images = normalized.images;
    } else if (normalized.images && typeof normalized.images === 'object') {
        jQuery.each(normalized.images, function(imageId, image) {
            images[parseInt(imageId, 10)] = image;
        });
    }
    normalized.images = images;

    return normalized;
};

EasyImageCollage.getDefaultGridName = function(id) {
    id = parseInt(id, 10) || 0;

    if (typeof eic_admin !== 'undefined' && eic_admin.text_collage_name_default) {
        return eic_admin.text_collage_name_default.replace('#id', id ? '#' + id : '#id');
    }

    return id ? 'Collage #' + id : 'Collage #id';
};

EasyImageCollage.getLightbox = function() {
    return jQuery('.eic-lightbox').last();
};

EasyImageCollage.getEditableLayout = function(layout) {
    var layoutName = typeof layout === 'string' || layout instanceof String
        ? '' + layout
        : jQuery(layout).first().attr('data-layout-name');
    var template = document.getElementById('eic-editable-layout-templates');

    if (layoutName && template && template.content) {
        var templateFrames = template.content.querySelectorAll('.eic-frame');

        for (var i = 0; i < templateFrames.length; i++) {
            if (templateFrames[i].getAttribute('data-layout-name') === layoutName) {
                return jQuery(templateFrames[i]).clone();
            }
        }
    }

    // Custom layouts are registered in the picker with their editing chrome intact.
    var fallback = jQuery('.eic-modal').first().find('.eic-layouts .eic-frame').filter(function() {
        return jQuery(this).attr('data-layout-name') === layoutName;
    }).first();

    return fallback.clone();
};

EasyImageCollage.registerGrid = function(id, grid, customLayoutHtml) {
    id = parseInt(id, 10) || 0;
    EasyImageCollage.grids[id] = EasyImageCollage.normalizeGrid(grid, id);

    if (customLayoutHtml) {
        var container = jQuery('.eic-modal .eic-custom-layouts');
        container.find('.eic-frame-custom-' + id).remove();
        container.append(customLayoutHtml);
    }
};

EasyImageCollage.loadGrid = function(id, callback) {
    if (typeof eic_admin === 'undefined' || !eic_admin.ajaxurl || !eic_admin.nonce) {
        callback(false);
        return;
    }

    jQuery.post(eic_admin.ajaxurl, {
        action: 'image_collage_get',
        security: eic_admin.nonce,
        grid_id: id
    }, function(response) {
        if (response && response.success && response.data && response.data.grid) {
            EasyImageCollage.registerGrid(id, response.data.grid, response.data.customLayoutHtml);
            callback(true);
        } else {
            callback(false);
        }
    }, 'json').fail(function() {
        callback(false);
    });
};

/**
 * Front end events
 */
jQuery(document).ready(function($) {
    if(typeof eic_admin_grids !== 'undefined' && typeof eic_default_grid !== 'undefined') {
        EasyImageCollage.grids = eic_admin_grids;
        EasyImageCollage.default_grid = eic_default_grid;

        // Add new button
        $('#eic-button').featherlight($('.eic-modal'), EasyImageCollage.lightbox_settings);
        $('#eic-button').click(function() {
            EasyImageCollage.setActivePage('layouts');
            EasyImageCollage.newGrid();
        });

        // Choose layout
        $('.eic-layouts .eic-frame').click(function() {
            var layout = $(this);
            if(layout.hasClass('eic-frame-custom')) {
                EasyImageCollage.setActivePage('creating');
                if (typeof EasyImageCollage.customLayout == 'function') {
                    EasyImageCollage.customLayout();
                }
            } else {
                EasyImageCollage.btnPickLayout(layout.clone(), false);
            }
        });

    }
});

/**
 * Front end control buttons
 */
EasyImageCollage.btnCreateGrid = function(id, callback) {
    EasyImageCollage.callback = callback;
    EasyImageCollage.newGrid();

    jQuery.featherlight(jQuery('.eic-modal'), EasyImageCollage.lightbox_settings);
    EasyImageCollage.setActivePage('layouts');
};

EasyImageCollage.btnEditGrid = function(id, callback) {
    if (EasyImageCollage.grids[id] === undefined) {
        EasyImageCollage.loadGrid(id, function(loaded) {
            if (loaded) {
                EasyImageCollage.btnEditGrid(id, callback);
            } else if (window.console && window.console.error) {
                window.console.error('Easy Image Collage: could not load grid data for collage ' + id + '.');
            }
        });
        return;
    }

    // Set editing grid
    EasyImageCollage.editing_grid = EasyImageCollage.normalizeGrid(EasyImageCollage.grids[id], id);
    EasyImageCollage.grids[id] = EasyImageCollage.editing_grid;
    EasyImageCollage.callback = callback;
    var grid = EasyImageCollage.editing_grid;

    // Open lightbox
    jQuery.featherlight(jQuery('.eic-modal'), EasyImageCollage.lightbox_settings);

    // Load grid layout
    var layout_name = (typeof grid.layout === 'string' || grid.layout instanceof String) ? grid.layout : 'custom-' + id,
        layout = EasyImageCollage.getEditableLayout(layout_name),
        lightbox = EasyImageCollage.getLightbox();

    if (!layout.length) {
        if (window.console && window.console.error) {
            window.console.error('Easy Image Collage: could not find editable layout ' + layout_name + '.');
        }
        jQuery.featherlight.close();
        return;
    }

    lightbox.find('.eic-editing .eic-container').html(layout);

    // Load images in grid
    if(grid['images'] !== undefined) {
        for(var i = 0; i < grid['images'].length; i++) {
            var image = grid['images'][i];

            if(image) EasyImageCollage.setImageFrontend(image);
        }
    }

    // Go to edit grid page
    EasyImageCollage.setActivePage('editing');
};

EasyImageCollage.btnChooseLayout = function() {
    EasyImageCollage.setActivePage('layouts');
};

EasyImageCollage.btnPickLayout = function(layout_element, layout) {
    var editableLayout = layout ? jQuery(layout_element) : EasyImageCollage.getEditableLayout(layout_element);

    if (!editableLayout.length) {
        if (window.console && window.console.error) {
            window.console.error('Easy Image Collage: could not prepare the selected layout for editing.');
        }
        return;
    }

    EasyImageCollage.getLightbox().find('.eic-editing .eic-container').html(editableLayout);

    var grid = EasyImageCollage.editing_grid;

    grid['layout'] = layout ? layout : editableLayout.data('layout-name');
    grid['dividers'] = [];

    EasyImageCollage.setActivePage('editing');

    for(var i = 0; i < grid['images'].length; i++) {
        var image = grid['images'][i];

        if(image) {
            if (image.type === 'text' && typeof EasyImageCollage.setTextFrameFrontend == 'function') {
                EasyImageCollage.editing_grid['images'][i] = image;
                EasyImageCollage.setTextFrameFrontend(image);
            } else {
                var attachment = {
                    id: image.attachment_id,
                    url: image.attachment_url,
                    width: image.attachment_width,
                    height: image.attachment_height,
                    thumb: image.attachment_thumb,
                    custom_link: image.custom_link,
                    custom_link_new_tab: image.custom_link_new_tab,
                    custom_link_nofollow: image.custom_link_nofollow,
                    custom_caption: image.custom_caption
                };
                EasyImageCollage.setImage(i, attachment);
            }
        }
    }
};

EasyImageCollage.btnImage = function(id) {
    EasyImageCollage.editing_image = id;
    EasyImageCollage.openMediaModal();
};

EasyImageCollage.btnManipulate = function(id) {
    EasyImageCollage.manipulating_image = id;
    EasyImageCollage.setActivePage('manipulating');
    if (typeof EasyImageCollage.loadImageManipulate == 'function') {
        EasyImageCollage.loadImageManipulate(id);
    }
};

EasyImageCollage.btnLink = function(id) {
    EasyImageCollage.setActivePage('links');
    if (typeof EasyImageCollage.loadCustomLinks == 'function') {
        EasyImageCollage.loadCustomLinks(id);
    }
};

EasyImageCollage.btnCaption = function(id) {
    EasyImageCollage.setActivePage('captions');
    if (typeof EasyImageCollage.loadCaptions == 'function') {
        EasyImageCollage.loadCaptions(id);
    }
};

EasyImageCollage.btnTextFrame = function(id) {
    EasyImageCollage.setActivePage('text-frames');
    if (typeof EasyImageCollage.loadTextFrame == 'function') {
        EasyImageCollage.loadTextFrame(id);
    }
};

EasyImageCollage.btnFinish = function() {
    var grid = EasyImageCollage.editing_grid;
    var gridNameInput = jQuery('.eic-lightbox #grid-name');

    if (gridNameInput.length) {
        grid.name = gridNameInput.val();
    }

    var data = {
        action: 'image_collage',
        security: eic_admin.nonce,
        grid: grid
    };

    var new_grid = grid.id == 0 ? true : false;

    jQuery.post(eic_admin.ajaxurl, data, function(grid_id) {
        var callback = EasyImageCollage.callback;

        if(callback) {
            // Gutenberg.
            callback(grid_id);
        } else {
            // Classic Editor.
            if(new_grid) {
                EasyImageCollage.addShortcodeToEditor(grid_id);
            } else {
                tinyMCE.activeEditor.setContent(tinyMCE.activeEditor.getContent());
            }
        }

        grid.id = grid_id;
        if (!grid.name) {
            grid.name = EasyImageCollage.getDefaultGridName(grid_id);
        }
        EasyImageCollage.grids[grid_id] = jQuery.extend(true, {}, grid);
        if (typeof EasyImageCollage.recordSavedCustomLayoutHistory == 'function') {
            EasyImageCollage.recordSavedCustomLayoutHistory(grid_id, grid);
        }
        jQuery.featherlight.close();
    }, 'json');
};

/**
 * Other functions
 */
EasyImageCollage.newGrid = function() {
    EasyImageCollage.editing_grid = EasyImageCollage.normalizeGrid(jQuery.extend(true, {}, EasyImageCollage.default_grid), 0);
};

EasyImageCollage.openMediaModal = function() {

    // If the media frame already exists, reopen it.
    if ( EasyImageCollage.file_frame ) {
        EasyImageCollage.file_frame.open();
        return;
    }

    // Create the media frame.
    EasyImageCollage.file_frame = wp.media.frames.file_frame = wp.media({
        title: 'Choose Image',
        button: {
            text: 'Choose Image'
        },
        multiple: false
    });

    // When an image is selected, run a callback.
    EasyImageCollage.file_frame.on( 'select', function() {
        // We set multiple to false so only get one image from the uploader
        attachment = EasyImageCollage.file_frame.state().get('selection').first().toJSON();

        if( EasyImageCollage.editing_image !== undefined ) {
            // Get thumbnail
            if(attachment.sizes.medium !== undefined && attachment.sizes.medium.url !== undefined) {
                attachment.thumb = attachment.sizes.medium.url;
            }

            // Get auto caption
            switch(eic_admin.captions_autofill) {
                case 'caption':
                    attachment.custom_caption = attachment.caption;
                    break;
                case 'title':
                    attachment.custom_caption = attachment.title;
                    break;
                case 'alt':
                    attachment.custom_caption = attachment.alt;
                    break;
                default:
                    attachment.custom_caption = '';
            }

            // Set selected image
            EasyImageCollage.setImage(EasyImageCollage.editing_image, attachment);
            EasyImageCollage.editing_image = undefined;
        }
    });

    // Finally, open the modal
    EasyImageCollage.file_frame.open();
};

EasyImageCollage.setImage = function(id, attachment) {
    var image_element = jQuery('.eic-lightbox .eic-editing .eic-image-' + id);

    if(image_element.length !== 0) {
        image = EasyImageCollage.getImageProperties(id, attachment);
        EasyImageCollage.editing_grid['images'][id] = image;
        EasyImageCollage.redrawImages();
    }

};

EasyImageCollage.getImageProperties = function(id, attachment) {
    var image_element = jQuery('.eic-lightbox .eic-editing .eic-image-' + id);

    if(image_element.length !== 0) {
        var total_border_width = 4 * parseInt(EasyImageCollage.editing_grid['properties']['borderWidth']);

        // Calculate size and position
        var frame_width = image_element.innerWidth() + total_border_width;
        var frame_height = image_element.innerHeight() + total_border_width;
        var frame_ratio = frame_width / frame_height;
        var image_ratio = attachment.width / attachment.height;

        var bg_width = frame_width;
        var bg_height = frame_width / image_ratio;
        var bg_pos_x = 0;
        var bg_pos_y = -(bg_height - frame_height) / 2; // Center vertically

        if(frame_ratio < image_ratio) {
            bg_width = frame_height * image_ratio;
            bg_height = frame_height;
            bg_pos_x = -(bg_width - frame_width) / 2; // Center horizontally
            bg_pos_y = 0;
        }

        if(attachment.thumb == undefined) {
            attachment.thumb = attachment.url;
        }

        return {
            type: 'image',
            id: id,
            attachment_id: attachment.id,
            attachment_url: attachment.url,
            attachment_width: attachment.width,
            attachment_height: attachment.height,
            attachment_thumb: attachment.thumb,
            custom_link: attachment.custom_link,
            custom_link_new_tab: attachment.custom_link_new_tab,
            custom_link_nofollow: attachment.custom_link_nofollow,
            custom_caption: attachment.custom_caption,
            size_x: bg_width,
            size_y: bg_height,
            pos_x: bg_pos_x,
            pos_y: bg_pos_y
        };
    }
    return undefined;
};

EasyImageCollage.getAdminText = function(key, fallback) {
    return typeof eic_admin !== 'undefined' && eic_admin[key] ? eic_admin[key] : fallback;
};

EasyImageCollage.setImageControlTooltip = function(control, tooltip) {
    control.attr({
        'data-eic-tooltip': tooltip,
        'aria-label': tooltip,
        'title': tooltip
    });
};

EasyImageCollage.setFrameControls = function(image_element, id, state) {
    var imageControl = image_element.find('.eic-image-control-image');
    var textControl = image_element.find('.eic-image-control-text-frame');
    var removeControl = image_element.find('.eic-image-control-remove');
    var parsedId = parseInt(id, 10);

    if (imageControl.length) {
        imageControl.attr('onclick', 'event.preventDefault(); EasyImageCollage.btnImage(' + parsedId + ')');
        imageControl.find('i').removeClass().addClass('fa fa-picture-o');
        EasyImageCollage.setImageControlTooltip(
            imageControl,
            EasyImageCollage.getAdminText('image' === state ? 'text_change_image' : 'text_choose_image', 'image' === state ? 'Change Image' : 'Choose image')
        );
    }

    if (textControl.length) {
        textControl.attr('onclick', 'event.preventDefault(); EasyImageCollage.btnTextFrame(' + parsedId + ')');
        textControl.find('i').removeClass().addClass('fa fa-align-left');
        EasyImageCollage.setImageControlTooltip(
            textControl,
            EasyImageCollage.getAdminText('text' === state ? 'text_change_text' : 'text_add_text_frame', 'text' === state ? 'Change text' : 'Add text frame')
        );
    }

    if (removeControl.length) {
        if ('text' === state) {
            removeControl.attr('onclick', 'event.preventDefault(); EasyImageCollage.removeTextFrame(' + parsedId + ')');
            EasyImageCollage.setImageControlTooltip(
                removeControl,
                EasyImageCollage.getAdminText('text_remove_text_frame', 'Remove text frame')
            );
        } else {
            removeControl.attr('onclick', 'event.preventDefault(); EasyImageCollage.removeImage(' + parsedId + ')');
            EasyImageCollage.setImageControlTooltip(
                removeControl,
                EasyImageCollage.getAdminText('text_remove_image', 'Remove image')
            );
        }

        removeControl.find('i').removeClass().addClass('fa fa-ban');
    }
};

EasyImageCollage.removeImage = function(id) {
    var image_element = jQuery('.eic-lightbox .eic-editing .eic-image-' + id);

    if (!EasyImageCollage.editing_grid.images) {
        EasyImageCollage.editing_grid.images = [];
    }

    EasyImageCollage.editing_grid.images[id] = false;

    image_element
        .off('mousedown touchstart')
        .removeClass('has-image has-text-frame')
        .removeAttr('data-frame-type')
        .css('background-image', '')
        .css('background-size', '')
        .css('background-position', '')
        .css('background-color', '')
        .children('.eic-text-frame-content, .eic-image-caption')
        .remove();

    EasyImageCollage.setFrameControls(image_element, id, 'empty');
};

EasyImageCollage.setImageFrontend = function(image) {
    if (image.type === 'text' && typeof EasyImageCollage.setTextFrameFrontend == 'function') {
        EasyImageCollage.setTextFrameFrontend(image);
        return;
    }

    var image_element = jQuery('.eic-lightbox .eic-editing .eic-image-' + image.id);

    // Element styling
    image_element
        .removeClass('has-text-frame')
        .addClass('has-image')
        .attr('data-frame-type', 'image')
        .children('.eic-text-frame-content')
        .remove();

    image_element
        .css('background-image', 'url("'+image.attachment_url+'")')
        .css('background-size', '' + image.size_x + 'px ' + image.size_y + 'px')
        .css('background-position', '' + image.pos_x + 'px ' + image.pos_y + 'px')
        .css('background-color', '')
    ;

    EasyImageCollage.updateImageCaption(image_element, image);
    EasyImageCollage.setFrameControls(image_element, image.id, 'image');

    // Handle move
    EasyImageCollage.handleImageMove(image);
};

EasyImageCollage.updateImageCaption = function(image_element, image) {
    var captions_enabled = typeof eic_admin !== 'undefined' && eic_admin.captions_enabled;
    var caption = image.custom_caption ? image.custom_caption : '';
    var caption_element = image_element.children('.eic-image-caption');

    if(!captions_enabled || !caption) {
        caption_element.remove();
        return;
    }

    if(caption_element.length === 0) {
        caption_element = jQuery('<span/>', {
            'class': 'eic-image-caption'
        });

        var controls = image_element.children('.eic-image-controls');

        if(controls.length) {
            caption_element.insertBefore(controls);
        } else {
            caption_element.appendTo(image_element);
        }
    }

    caption_element
        .toggleClass('eic-image-caption-hover', typeof eic_admin !== 'undefined' && eic_admin.captions_hover_only)
        .text(caption);
};

EasyImageCollage.handleImageMove = function(image) {
    var image_element = jQuery('.eic-lightbox .eic-editing .eic-image-' + image.id);

    image_element.off('mousedown touchstart');

    image_element.on('mousedown touchstart', function(e) {
        if (e.target !== image_element[0]) return;
        e.preventDefault();

        if (e.originalEvent.touches) {
            EasyImageCollage.modifyEventForTouch(e);
        } else if (e.which !== 1) {
            return;
        }

        var x0 = e.clientX,
            y0 = e.clientY,
            size = image_element.css('background-size').match(/(-?\d+).*?\s(-?\d+)/),
            size_x = size[1],
            size_y = size[2],
            min_x = image_element.innerWidth() - size_x,
            min_y = image_element.innerHeight() - size_y,
            backgroundPos = image_element.css('background-position').split(" "),
            pos_x = parseInt(backgroundPos[0]),
            pos_y = parseInt(backgroundPos[1]);

        jQuery(window).on('mousemove touchmove', function(e) {
            e.preventDefault();

            if (e.originalEvent.touches) {
                EasyImageCollage.modifyEventForTouch(e);
            }

            var x = e.clientX,
                y = e.clientY;

            // New position
            pos_x = pos_x+x-x0;
            pos_y = pos_y+y-y0;

            // Check bounds
            pos_x = pos_x < min_x ? min_x : ( pos_x > 0 ? 0 : pos_x );
            pos_y = pos_y < min_y ? min_y : ( pos_y > 0 ? 0 : pos_y );

            // New starting point for drag
            x0 = x;
            y0 = y;

            image_element
                .css('background-position', '' + pos_x + 'px ' + pos_y + 'px')
        });

        jQuery(window).on('mouseup touchend', function() {
            // Update new image position

            var backgroundPos = image_element.css('background-position').split(" "),
                pos_x = parseInt(backgroundPos[0]),
                pos_y = parseInt(backgroundPos[1]);

            image.pos_x = pos_x;
            image.pos_y = pos_y;

            // Remove event handlers
            jQuery(window).off('mousemove touchmove');
            jQuery(window).off('mouseup touchend');
        });
    });
};

/**
 * Helper functions
 */
EasyImageCollage.redrawBorders = function() {
    var borderWidth = EasyImageCollage.editing_grid.properties.borderWidth;
    var borderColor = EasyImageCollage.editing_grid.properties.borderColor;

    jQuery('.eic-lightbox .eic-editing .eic-frame')
        .css('border', borderWidth + 'px solid ' + borderColor)
        .find('.eic-image')
        .css('border', borderWidth + 'px solid ' + borderColor);

    EasyImageCollage.redrawImages();
    EasyImageCollage.redrawBorderRadius();
    if (typeof EasyImageCollage.recalculateSizes == 'function') {
        EasyImageCollage.recalculateSizes();
    }
};

EasyImageCollage.redrawBorderRadius = function() {
    var borderRadius = parseInt(EasyImageCollage.editing_grid.properties.borderRadius, 10) || 0;
    var borderWidth = parseInt(EasyImageCollage.editing_grid.properties.borderWidth, 10) || 0;
    var imageRadius = Math.max(0, borderRadius - borderWidth);
    var frame = jQuery('.eic-lightbox .eic-editing .eic-frame');

    frame
        .css('border-radius', borderRadius + 'px')
        .css('overflow', 'hidden');

    EasyImageCollage.markBorderRadiusCorners(frame);

    frame.find('.eic-image')
        .css('border-top-left-radius', '')
        .css('border-top-right-radius', '')
        .css('border-bottom-left-radius', '')
        .css('border-bottom-right-radius', '');

    frame.find('.eic-corner-top-left').css('border-top-left-radius', imageRadius + 'px');
    frame.find('.eic-corner-top-right').css('border-top-right-radius', imageRadius + 'px');
    frame.find('.eic-corner-bottom-left').css('border-bottom-left-radius', imageRadius + 'px');
    frame.find('.eic-corner-bottom-right').css('border-bottom-right-radius', imageRadius + 'px');
};

EasyImageCollage.markBorderRadiusCorners = function(frame) {
    var cornerClasses = 'eic-corner-top-left eic-corner-top-right eic-corner-bottom-left eic-corner-bottom-right';

    frame.find('.eic-image').removeClass(cornerClasses);

    var mark = function(element, corners) {
        var image = element.children('.eic-image').first();

        if (image.length) {
            image
                .toggleClass('eic-corner-top-left', !!corners.topLeft)
                .toggleClass('eic-corner-top-right', !!corners.topRight)
                .toggleClass('eic-corner-bottom-left', !!corners.bottomLeft)
                .toggleClass('eic-corner-bottom-right', !!corners.bottomRight);
            return;
        }

        var rows = element.children('.eic-rows').first();
        if (rows.length) {
            mark(rows.children('.eic-child-1').first(), {
                topLeft: corners.topLeft,
                topRight: corners.topRight,
                bottomLeft: false,
                bottomRight: false
            });
            mark(rows.children('.eic-child-2').first(), {
                topLeft: false,
                topRight: false,
                bottomLeft: corners.bottomLeft,
                bottomRight: corners.bottomRight
            });
            return;
        }

        var cols = element.children('.eic-cols').first();
        if (cols.length) {
            mark(cols.children('.eic-child-1').first(), {
                topLeft: corners.topLeft,
                topRight: false,
                bottomLeft: corners.bottomLeft,
                bottomRight: false
            });
            mark(cols.children('.eic-child-2').first(), {
                topLeft: false,
                topRight: corners.topRight,
                bottomLeft: false,
                bottomRight: corners.bottomRight
            });
        }
    };

    mark(frame, {
        topLeft: true,
        topRight: true,
        bottomLeft: true,
        bottomRight: true
    });
};

EasyImageCollage.redrawGrid = function() {
    var width = EasyImageCollage.editing_grid.properties.width,
        ratio = EasyImageCollage.editing_grid.properties.ratio;

    var height = parseInt(width/ratio);

    jQuery('.eic-lightbox .eic-editing .eic-frame')
        .css('width', width + 'px')
        .css('height', height + 'px');

    EasyImageCollage.redrawImages();
};

EasyImageCollage.redrawAlignment = function() {
    var align = EasyImageCollage.editing_grid.properties.align;
    var container = jQuery('.eic-lightbox .eic-editing-canvas .eic-container');

    if (-1 === jQuery.inArray(align, ['left', 'center', 'right', 'float-left', 'float-right'])) {
        align = 'center';
    }

    container
        .removeClass('eic-align-left eic-align-center eic-align-right eic-float-left eic-float-right')
        .addClass('float-left' === align ? 'eic-align-left eic-float-left' : '')
        .addClass('float-right' === align ? 'eic-align-right eic-float-right' : '')
        .addClass('left' === align ? 'eic-align-left' : '')
        .addClass('center' === align ? 'eic-align-center' : '')
        .addClass('right' === align ? 'eic-align-right' : '');
};

EasyImageCollage.redrawImages = function() {
    var grid = EasyImageCollage.editing_grid;

    if(grid['images'] !== undefined) {
        for(var i = 0; i < grid['images'].length; i++) {
            var image = grid['images'][i];

            if(image) {
                if (image.type === 'text') {
                    if (typeof EasyImageCollage.setTextFrameFrontend == 'function') {
                        EasyImageCollage.setTextFrameFrontend(image);
                    }
                    continue;
                }

                var attachment = {
                    id: image.attachment_id,
                    url: image.attachment_url,
                    width: image.attachment_width,
                    height: image.attachment_height,
                    thumb: image.attachment_thumb,
                    custom_link: image.custom_link,
                    custom_link_new_tab: image.custom_link_new_tab,
                    custom_link_nofollow: image.custom_link_nofollow,
                    custom_caption: image.custom_caption
                };
                var newImage = EasyImageCollage.getImageProperties(i, attachment);

                if(newImage !== undefined) {
                    var change_x_size = newImage.size_x / image.size_x,
                        change_y_size = newImage.size_y / image.size_y,
                        border_width = 2 * parseInt( grid['properties']['borderWidth'] ),
                        change_x_pos = ( newImage.size_x - 2 * border_width ) / image.size_x,
                        change_y_pos = ( newImage.size_y - 2 * border_width ) / image.size_y;

                    image.size_x = Math.ceil(image.size_x * change_x_size) - 2 * border_width;
                    image.size_y = Math.ceil(image.size_y * change_y_size) - 2 * border_width;
                    image.pos_x = Math.ceil(image.pos_x * change_x_pos);
                    image.pos_y = Math.ceil(image.pos_y * change_y_pos);
                }
                grid['images'][i] = image;
                EasyImageCollage.setImageFrontend(image);
            }
        }
    }

    if (typeof EasyImageCollage.recalculateSizes == 'function') {
        EasyImageCollage.recalculateSizes();
    }
};

EasyImageCollage.setActivePage = function(name) {
    var pages = ['layouts', 'creating', 'editing', 'manipulating', 'links', 'captions', 'text-frames'],
        lightbox = EasyImageCollage.getLightbox();

    if (!lightbox.length) {
        return;
    }

    pages.forEach(function(page) {
        if(page == name) {
            lightbox.find('.eic-' + page).show();
        } else {
            lightbox.find('.eic-' + page).hide();
        }
    });

    // Page specific
    if(name == 'editing') {
        EasyImageCollage.syncEditingControls();

        if (typeof EasyImageCollage.redrawDividers == 'function') {
            EasyImageCollage.redrawDividers();
        }

        EasyImageCollage.redrawBorders();
        EasyImageCollage.redrawBorderRadius();
        EasyImageCollage.redrawGrid();
        EasyImageCollage.redrawAlignment();
    }
};

EasyImageCollage.modifyEventForTouch = function(e) {
    e.clientX = e.originalEvent.touches[0].clientX;
    e.clientY = e.originalEvent.touches[0].clientY;
};

EasyImageCollage.addShortcodeToEditor = function(id) {
    var text = ' [easy-image-collage id='+id+'] ';

    if( typeof tinyMCE == 'undefined' || !tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden()) {
        var current = jQuery('textarea#content').val();
        jQuery('textarea#content').val(current + text);
    } else {
        tinyMCE.execCommand('mceInsertContent', false, text);
    }
};
