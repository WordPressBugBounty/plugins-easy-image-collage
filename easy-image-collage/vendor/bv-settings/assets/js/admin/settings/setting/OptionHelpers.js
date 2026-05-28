const objectToOptions = (options) => {
    let selectOptions = [];

    if (Array.isArray(options)) {
        return options;
    }

    if (!options || 'object' !== typeof options) {
        return selectOptions;
    }

    for (let option in options) {
        selectOptions.push({
            value: option,
            label: options[option],
        });
    }

    return selectOptions;
};

const normalizeOptions = (options) => objectToOptions(options).map((option) => {
    if (option && 'object' === typeof option) {
        return option;
    }

    return {
        value: option,
        label: option,
    };
});

const normalizeGroupedOptions = (options) => {
    if (Array.isArray(options)) {
        return options.map((option) => {
            if (option && 'object' === typeof option && option.hasOwnProperty('options')) {
                return {
                    ...option,
                    options: normalizeOptions(option.options),
                };
            }

            return option;
        });
    }

    if (!options || 'object' !== typeof options) {
        return [];
    }

    let selectOptions = [];

    for (let group in options) {
        const groupOptions = options[group];

        if (groupOptions && 'object' === typeof groupOptions && groupOptions.hasOwnProperty('options')) {
            selectOptions.push({
                label: groupOptions.hasOwnProperty('label') ? groupOptions.label : group,
                options: normalizeOptions(groupOptions.options),
            });
        } else {
            selectOptions.push({
                label: group,
                options: normalizeOptions(groupOptions),
            });
        }
    }

    return selectOptions;
};

const flattenOptions = (options) => {
    let flattened = [];

    for (let option of options) {
        if (option && 'object' === typeof option && option.hasOwnProperty('options')) {
            flattened = flattened.concat(flattenOptions(option.options));
        } else {
            flattened.push(option);
        }
    }

    return flattened;
};

export {
    flattenOptions,
    normalizeGroupedOptions,
    normalizeOptions,
};
