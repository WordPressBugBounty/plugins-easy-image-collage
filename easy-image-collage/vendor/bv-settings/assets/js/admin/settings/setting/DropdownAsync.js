import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AsyncSelect from 'react-select/async';

class SettingDropdownAsync extends Component {
    getConfig() {
        const asyncConfig = this.props.setting.hasOwnProperty('async') ? this.props.setting.async : {};

        return {
            ...asyncConfig,
            defaultOptions: this.props.setting.hasOwnProperty('defaultOptions') ? this.props.setting.defaultOptions : asyncConfig.defaultOptions,
            endpoint: this.props.setting.hasOwnProperty('endpoint') ? this.props.setting.endpoint : asyncConfig.endpoint,
            labelKey: this.props.setting.hasOwnProperty('labelKey') ? this.props.setting.labelKey : asyncConfig.labelKey,
            valueKey: this.props.setting.hasOwnProperty('valueKey') ? this.props.setting.valueKey : asyncConfig.valueKey,
            valueStorage: this.props.setting.hasOwnProperty('valueStorage') ? this.props.setting.valueStorage : asyncConfig.valueStorage,
        };
    }

    getValueKey() {
        const config = this.getConfig();
        return config.valueKey ? config.valueKey : 'value';
    }

    getLabelKey() {
        const config = this.getConfig();
        return config.labelKey ? config.labelKey : 'label';
    }

    getDefaultOptions() {
        const config = this.getConfig();
        return Array.isArray(config.defaultOptions) ? config.defaultOptions : [];
    }

    getValueStorage() {
        const config = this.getConfig();

        if (config.valueStorage) {
            return config.valueStorage;
        }

        return this.props.value && 'object' === typeof this.props.value ? 'object' : 'value';
    }

    getSelectedOption() {
        const valueKey = this.getValueKey();
        const value = this.props.value;

        if (value && 'object' === typeof value) {
            return value;
        }

        const defaultOptions = this.getDefaultOptions();
        const matches = defaultOptions.filter((option) => option && String(option[valueKey]) === String(value));

        if (matches.length) {
            return matches[0];
        }

        if ('' === value || null === value || undefined === value) {
            return null;
        }

        return {
            [valueKey]: value,
            [this.getLabelKey()]: value,
        };
    }

    getResponsePath(data, path) {
        if (!path) {
            return data;
        }

        let value = data;
        const parts = String(path).split('.');

        for (let part of parts) {
            if (!value || !value.hasOwnProperty(part)) {
                return [];
            }

            value = value[part];
        }

        return value;
    }

    getOptions(input) {
        const config = this.getConfig();

        if (!config.endpoint) {
            const normalizedInput = input ? String(input).toLowerCase() : '';
            const labelKey = this.getLabelKey();

            return Promise.resolve(this.getDefaultOptions().filter((option) => {
                if (!normalizedInput) {
                    return true;
                }

                return String(option[labelKey]).toLowerCase().includes(normalizedInput);
            }));
        }

        const method = config.method ? config.method.toUpperCase() : 'GET';
        const searchParam = config.searchParam ? config.searchParam : 'search';
        let endpoint = config.endpoint;
        let fetchOptions = {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(config.headers ? config.headers : {}),
            },
            credentials: config.credentials ? config.credentials : 'same-origin',
        };

        if (config.nonce) {
            const nonceHeader = config.nonceHeader ? config.nonceHeader : 'X-WP-Nonce';
            fetchOptions.headers[nonceHeader] = config.nonce;
        }

        if ('GET' === method) {
            const separator = endpoint.includes('?') ? '&' : '?';
            endpoint = `${endpoint}${separator}${encodeURIComponent(searchParam)}=${encodeURIComponent(input)}`;
        } else {
            fetchOptions.body = JSON.stringify({
                ...(config.body ? config.body : {}),
                [searchParam]: input,
            });
        }

        return fetch(endpoint, fetchOptions)
            .then((response) => response.json().then((json) => response.ok ? json : Promise.reject(json)))
            .then((data) => {
                const options = this.getResponsePath(data, config.responsePath);
                return Array.isArray(options) ? options : [];
            });
    }

    onChange(option) {
        if (!option) {
            this.props.onValueChange('object' === this.getValueStorage() ? null : '');
            return;
        }

        if ('object' === this.getValueStorage()) {
            this.props.onValueChange(option);
            return;
        }

        this.props.onValueChange(option[this.getValueKey()]);
    }

    render() {
        const config = this.getConfig();
        const isClearable = this.props.setting.hasOwnProperty('isClearable') ? this.props.setting.isClearable : !!config.isClearable;

        return (
            <AsyncSelect
                className="bvs-setting-input"
                placeholder={config.placeholder ? config.placeholder : 'Select or start typing to search'}
                value={this.getSelectedOption()}
                onChange={this.onChange.bind(this)}
                getOptionValue={(option) => String(option[this.getValueKey()])}
                getOptionLabel={(option) => {
                    const label = option[this.getLabelKey()];
                    return null === label || undefined === label ? '' : String(label);
                }}
                defaultOptions={this.getDefaultOptions()}
                loadOptions={this.getOptions.bind(this)}
                noOptionsMessage={() => config.noOptionsMessage ? config.noOptionsMessage : 'No options found'}
                clearable={isClearable}
                isClearable={isClearable}
            />
        );
    }
}

SettingDropdownAsync.propTypes = {
    setting: PropTypes.object.isRequired,
    value: PropTypes.any,
    onValueChange: PropTypes.func.isRequired,
}

export default SettingDropdownAsync;
