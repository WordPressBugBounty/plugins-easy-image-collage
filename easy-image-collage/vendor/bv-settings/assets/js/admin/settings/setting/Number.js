import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

const SettingNumber = (props) => {
    return (
        <Fragment>
            <input
                className="bvs-setting-input"
                type="number"
                min={ props.setting.hasOwnProperty('min') ? props.setting.min : undefined }
                max={ props.setting.hasOwnProperty('max') ? props.setting.max : undefined }
                step={ props.setting.hasOwnProperty('step') ? props.setting.step : undefined }
                value={props.value}
                onChange={(e) => props.onValueChange(e.target.value)}
            />
            {
                props.setting.hasOwnProperty('suffix')
                ?
                <span className="bvs-setting-number-suffix"> { props.setting.suffix }</span>
                :
                null
            }
        </Fragment>
    );
}

SettingNumber.propTypes = {
    setting: PropTypes.object.isRequired,
    value: PropTypes.any.isRequired,
    onValueChange: PropTypes.func.isRequired,
}

export default SettingNumber;
