import React from 'react';
import PropTypes from 'prop-types';

const SettingObjectTable = (props) => {
    const rows = props.value && 'object' === typeof props.value ? props.value : {};
    const columns = props.setting.hasOwnProperty('columns') && Array.isArray(props.setting.columns) ? props.setting.columns : [];
    const rowLabelKey = props.setting.rowLabelKey ? props.setting.rowLabelKey : 'label';
    const rowHeader = props.setting.rowHeader ? props.setting.rowHeader : '';

    const onChange = (rowKey, columnKey, value) => {
        let newRows = {};

        for (let existingRowKey in rows) {
            newRows[existingRowKey] = {
                ...rows[existingRowKey],
            };
        }

        newRows[rowKey] = {
            ...(newRows[rowKey] ? newRows[rowKey] : {}),
            [columnKey]: value,
        };

        props.onValueChange(newRows);
    };

    const renderInput = (rowKey, column) => {
        const row = rows[rowKey] ? rows[rowKey] : {};
        const value = row.hasOwnProperty(column.key) ? row[column.key] : '';
        const inputClassName = `bvs-setting-object-table-input${column.className ? ` ${column.className}` : ''}`;

        if ('textarea' === column.type) {
            return (
                <textarea
                    className={inputClassName}
                    value={value}
                    rows={column.rows ? column.rows : 3}
                    onChange={(e) => onChange(rowKey, column.key, e.target.value)}
                />
            );
        }

        return (
            <input
                className={inputClassName}
                type={column.type ? column.type : 'text'}
                value={value}
                onChange={(e) => onChange(rowKey, column.key, e.target.value)}
            />
        );
    };

    return (
        <table className="bvs-setting-object-table">
            <thead>
                <tr>
                    <th>{rowHeader}</th>
                    {
                        columns.map((column) => (
                            <th key={column.key}>{column.label ? column.label : column.key}</th>
                        ))
                    }
                </tr>
            </thead>
            <tbody>
                {
                    Object.keys(rows).map((rowKey) => {
                        const row = rows[rowKey] ? rows[rowKey] : {};
                        const rowLabel = row.hasOwnProperty(rowLabelKey) ? row[rowLabelKey] : rowKey;

                        return (
                            <tr key={rowKey}>
                                <th scope="row">{rowLabel}</th>
                                {
                                    columns.map((column) => (
                                        <td key={column.key}>
                                            {renderInput(rowKey, column)}
                                        </td>
                                    ))
                                }
                            </tr>
                        );
                    })
                }
            </tbody>
        </table>
    );
}

SettingObjectTable.propTypes = {
    setting: PropTypes.object.isRequired,
    value: PropTypes.any,
    onValueChange: PropTypes.func.isRequired,
}

export default SettingObjectTable;
