import React from 'react';
import PropTypes from 'prop-types';
import { Element } from 'react-scroll';

import Helpers from '../general/Helpers';
import RequiredLabel from './RequiredLabel';

import SettingCode from './setting/Code';
import SettingColor from './setting/Color';
import SettingDropdown from './setting/Dropdown';
import SettingDropdownAsync from './setting/DropdownAsync';
import SettingDropdownGrouped from './setting/DropdownGrouped';
import SettingDropdownMultiselect from './setting/DropdownMultiselect';
import SettingEmail from './setting/Email';
import SettingNumber from './setting/Number';
import SettingObjectTable from './setting/ObjectTable';
import SettingRichTextarea from './setting/RichTextarea';
import SettingText from './setting/Text';
import SettingTextarea from './setting/Textarea';
import SettingToggle from './setting/Toggle';

import InformationButton from './information/Button';

const settingTypes = {
    code: SettingCode,
    color: SettingColor,
    dropdown: SettingDropdown,
    dropdownAsync: SettingDropdownAsync,
    dropdownGrouped: SettingDropdownGrouped,
    dropdownMultiselect: SettingDropdownMultiselect,
    email: SettingEmail,
    number: SettingNumber,
    objectTable: SettingObjectTable,
    richTextarea: SettingRichTextarea,
    text: SettingText,
    textarea: SettingTextarea,
    toggle: SettingToggle,
}
const informationTypes = {
    button: InformationButton,
}

const Setting = (props) => {
    const SettingComponent = settingTypes.hasOwnProperty(props.setting.type) ? settingTypes[props.setting.type] : false;
    const InformationComponent = informationTypes.hasOwnProperty(props.setting.type) ? informationTypes[props.setting.type] : false;
    
    const displayValue = SettingComponent ? Helpers.beforeSettingDisplay(props.setting, props.settings) : false;
    const documentationText = props.setting.hasOwnProperty('documentation_text') ? props.setting.documentation_text : 'Learn More';
    const isFullWidth = props.setting.fullWidth || 'full' === props.setting.layout;

    return (
        <Element className={`bvs-setting-container${isFullWidth ? ' bvs-setting-container-full' : ''}`} name={props.setting.id} >
            <div className="bvs-setting-label-container">
                <span className="bvs-setting-label">
                    <RequiredLabel object={props.setting} />
                    {props.setting.name && (props.searchQuery ? Helpers.highlightText(props.setting.name, props.searchQuery) : props.setting.name)}
                </span>
                {props.setting.description && (
                    <span className="bvs-setting-description">
                        {props.searchQuery ? Helpers.highlightText(props.setting.description, props.searchQuery) : props.setting.description}
                    </span>
                )}
                {
                    props.setting.hasOwnProperty('documentation')
                    ?
                    <a href={props.setting.documentation} target="_blank" className="bvs-setting-documentation">{ documentationText }</a>
                    :
                    null
                }
            </div>
            <div className="bvs-setting-input-container">
                {
                    SettingComponent ?
                    <SettingComponent
                        setting={props.setting}
                        settingsChanged={props.settingsChanged}
                        onValueChange={(value) => {
                            const saveValue = Helpers.beforeSettingSave(value, props.setting, props.settings);
                            return props.onSettingChange(props.setting.id, saveValue)
                        }}
                        value={displayValue}
                    />
                    :
                    null
                }
                {
                    InformationComponent ?
                    <InformationComponent
                        setting={props.setting}
                        settingsChanged={props.settingsChanged}
                    />
                    :
                    null
                }
            </div>
        </Element>
    );
}

Setting.propTypes = {
    settings: PropTypes.object.isRequired,
    setting: PropTypes.object.isRequired,
    onSettingChange: PropTypes.func.isRequired,
    settingsChanged: PropTypes.bool.isRequired,
    searchQuery: PropTypes.string.isRequired,
}

export default Setting;
