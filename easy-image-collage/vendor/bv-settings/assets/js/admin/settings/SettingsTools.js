import React from 'react';
import PropTypes from 'prop-types';
import { Element } from 'react-scroll';

import Helpers from '../general/Helpers';

const SettingsTools = (props) => {
    return (
        <div id={`bvs-settings-group-${props.group.id}`} className="bvs-settings-group">
            <h2 className="bvs-settings-group-name">
                {props.searchQuery ? Helpers.highlightText(props.group.name, props.searchQuery) : props.group.name}
            </h2>
            <div className="bvs-settings-group-container">
                <Element className="bvs-setting-container" name={Helpers.getSettingsToolsResetAnchor()}>
                    <div className="bvs-setting-label-container">
                        <span className="bvs-setting-label">
                            {props.searchQuery ? Helpers.highlightText('Reset to defaults', props.searchQuery) : 'Reset to defaults'}
                        </span>
                        <span className="bvs-setting-description">
                            {props.searchQuery ? Helpers.highlightText('Reset all settings to their default values.', props.searchQuery) : 'Reset all settings to their default values.'}
                        </span>
                    </div>
                    <div className="bvs-setting-input-container">
                        <button
                            className="button button-secondary button-compact"
                            onClick={props.onResetDefaults}
                        >Reset to Defaults</button>
                    </div>
                </Element>
            </div>
        </div>
    );
}

SettingsTools.propTypes = {
    group: PropTypes.object.isRequired,
    settings: PropTypes.object.isRequired,
    onResetDefaults: PropTypes.func.isRequired,
    searchQuery: PropTypes.string.isRequired,
}

export default SettingsTools;
