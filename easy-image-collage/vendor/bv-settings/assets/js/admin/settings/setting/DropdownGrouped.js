import React from 'react';
import PropTypes from 'prop-types';
import Select from 'react-select';

import { flattenOptions, normalizeGroupedOptions } from './OptionHelpers';

const SettingDropdownGrouped = (props) => {
    const selectOptions = normalizeGroupedOptions(props.setting.options);
    const allOptions = flattenOptions(selectOptions);
    const isClearable = props.setting.hasOwnProperty('isClearable') ? props.setting.isClearable : false;
    const selectedOption = allOptions.filter((option) => option && String(option.value) === String(props.value))[0] || null;

    return (
        <Select
            className="bvs-setting-input"
            value={selectedOption}
            onChange={(option) => props.onValueChange(option ? option.value : '')}
            options={selectOptions}
            clearable={isClearable}
            isClearable={isClearable}
            styles={{
                menu: (provided) => ({
                    ...provided,
                    zIndex: '10',
                }),
            }}
        />
    );
}

SettingDropdownGrouped.propTypes = {
    setting: PropTypes.object.isRequired,
    value: PropTypes.any.isRequired,
    onValueChange: PropTypes.func.isRequired,
}

export default SettingDropdownGrouped;
