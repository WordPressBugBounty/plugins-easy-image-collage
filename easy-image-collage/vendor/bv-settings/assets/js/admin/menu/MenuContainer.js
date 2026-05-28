import React, { useRef, useEffect } from 'react';
import PropTypes from 'prop-types'
import { Link } from 'react-scroll'

import Helpers from '../general/Helpers';
import Icon from '../general/Icon';
import Tooltip from '../general/Tooltip';

const MenuContainer = (props) => {
    const inputRef = useRef(null);

    useEffect(() => {
        if ( inputRef.current && inputRef.current.value !== props.searchQuery ) {
            inputRef.current.value = props.searchQuery;
        }
    }, [props.searchQuery]);

    let menuStructure = [];

    for ( let group of props.structure ) {
        if ( ! Helpers.dependencyMet(group, props.settings ) ) {
            continue;
        }

        if (group.hasOwnProperty('header')) {
            menuStructure.push({
                header: group.header,
            });
        } else {

            menuStructure.push({
                id: group.id,
                name: group.name,
                icon: group.hasOwnProperty( 'icon' ) ? group.icon : false,
            });
        }
    }

    const isSearchActive = props.searchQuery && props.searchQuery.length > 0;
    const changesSummary = props.changedSettingsCount
        ? `${props.changedSettingsCount} Change${1 === props.changedSettingsCount ? '' : 's'}`
        : 'No Changes';
    const searchSummary = props.searchResultsCount
        ? `${props.searchResultsCount} Result${1 === props.searchResultsCount ? '' : 's'}`
        : 'No Results';
    const changedSettingsBackLabel = isSearchActive ? 'Back to search results' : 'Back to categories';

    const clearSearch = () => {
        if ( inputRef.current ) {
            inputRef.current.value = '';
        }

        props.onSearchChange('');
    };

    const getChangedSettingContext = (changedSetting) => {
        if ( changedSetting.subgroupName ) {
            return `${changedSetting.groupName} > ${changedSetting.subgroupName}`;
        }

        return changedSetting.groupName;
    };

    const renderSearchSetting = (setting) => (
        <button
            type="button"
            className="bvs-settings-search-item"
            onClick={() => props.onJumpToSearchResult(setting)}
            key={setting.id}
        >
            {setting.name}
        </button>
    );

    return (
        <div id="bvs-settings-sidebar">
            <div id="bvs-settings-buttons">
                <div className="bvs-settings-changes-wrapper">
                    <div className="bvs-settings-changes-save-wrapper">
                        <button
                            className="button button-primary button-compact"
                            disabled={props.savingChanges || !props.settingsChanged}
                            onClick={props.onSaveChanges}
                        >{ props.savingChanges ? '...' : 'Save' }</button>
                    </div>
                    <Tooltip content={props.changedSettingsCount ? 'Click to review the changes that were made' : ''}>
                        <div className="bvs-settings-changes-summary-wrapper">
                            <button
                                type="button"
                                className={`button button-secondary button-compact bvs-settings-changes-summary${props.showChangedSettings ? ' active' : ''}`}
                                disabled={!props.changedSettingsCount}
                                onClick={props.onToggleChangedSettings}
                            >
                                {changesSummary}
                            </button>
                        </div>
                    </Tooltip>
                </div>
                <div className="bvs-settings-search-wrapper">
                    <input
                        ref={inputRef}
                        type="text"
                        id="bvs-settings-search"
                        placeholder="Search..."
                        defaultValue={props.searchQuery}
                        onChange={(e) => props.onSearchChange(e.target.value)}
                    />
                    {isSearchActive && (
                        <button
                            type="button"
                            className="bvs-settings-search-clear"
                            onClick={clearSearch}
                            title="Clear search"
                        >
                            ×
                        </button>
                    )}
                </div>
            </div>
            {
                props.showChangedSettings
                ?
                <div id="bvs-settings-menu" className="bvs-settings-menu-changed">
                    <div className="bvs-settings-changed-back">
                        <a
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                props.onToggleChangedSettings();
                            }}
                        >
                            {changedSettingsBackLabel}
                        </a>
                    </div>
                    <div className="bvs-settings-menu-header">Changed settings</div>
                    <div className="bvs-settings-changed-list">
                        {
                            props.changedSettings.map((changedSetting) => (
                                <div
                                    className="bvs-settings-changed-item-row"
                                    key={changedSetting.id}
                                >
                                    <button
                                        type="button"
                                        className="bvs-settings-changed-item"
                                        onClick={() => props.onJumpToChangedSetting(changedSetting)}
                                    >
                                        <span className="bvs-settings-changed-item-name">{changedSetting.name}</span>
                                        <span className="bvs-settings-changed-item-context">{getChangedSettingContext(changedSetting)}</span>
                                        {
                                            changedSetting.isCurrentlyVisible
                                            ?
                                            null
                                            :
                                            <span className="bvs-settings-changed-item-badge">Hidden</span>
                                        }
                                    </button>
                                    <button
                                        type="button"
                                        className="bvs-settings-changed-item-cancel"
                                        aria-label={`Cancel change for ${changedSetting.name}`}
                                        onClick={() => props.onRevertChangedSetting(changedSetting.id)}
                                    >
                                        <Tooltip content={changedSetting.changeDescription}>
                                            <span className="bvs-settings-changed-item-cancel-icon" aria-hidden="true">
                                                <Icon type="undo" />
                                            </span>
                                        </Tooltip>
                                    </button>
                                </div>
                            ))
                        }
                    </div>
                    <div className="bvs-settings-changed-actions bvs-settings-changed-actions-bottom">
                        <button
                            type="button"
                            className="button button-secondary button-compact"
                            onClick={props.onCancelChanges}
                        >
                            Cancel All Changes
                        </button>
                    </div>
                </div>
                :
                isSearchActive
                ?
                <div id="bvs-settings-menu" className="bvs-settings-menu-search">
                    <div className="bvs-settings-menu-header">Search results</div>
                    <div className="bvs-settings-search-summary">{searchSummary}</div>
                    {
                        props.searchResults.length ? (
                            <div className="bvs-settings-search-groups">
                                {
                                    props.searchResults.map((group) => (
                                        <div className="bvs-settings-search-group" key={group.id}>
                                            <button
                                                type="button"
                                                className="bvs-settings-search-group-button"
                                                onClick={() => props.onJumpToSearchResult({
                                                    targetType: 'section',
                                                    targetId: group.targetId,
                                                })}
                                            >
                                                { group.icon && <Icon type={group.icon} /> }
                                                <span>{group.name}</span>
                                            </button>
                                            {
                                                group.settings.length ? (
                                                    <div className="bvs-settings-search-items">
                                                        {group.settings.map(renderSearchSetting)}
                                                    </div>
                                                ) : null
                                            }
                                            {
                                                group.subgroups.length ? (
                                                    <div className="bvs-settings-search-subgroups">
                                                        {
                                                            group.subgroups.map((subgroup) => (
                                                                <div className="bvs-settings-search-subgroup" key={subgroup.id}>
                                                                    <button
                                                                        type="button"
                                                                        className="bvs-settings-search-subgroup-button"
                                                                        onClick={() => props.onJumpToSearchResult({
                                                                            targetType: 'section',
                                                                            targetId: subgroup.targetId,
                                                                        })}
                                                                    >
                                                                        {subgroup.name}
                                                                    </button>
                                                                    {
                                                                        subgroup.settings.length ? (
                                                                            <div className="bvs-settings-search-items">
                                                                                {subgroup.settings.map(renderSearchSetting)}
                                                                            </div>
                                                                        ) : null
                                                                    }
                                                                </div>
                                                            ))
                                                        }
                                                    </div>
                                                ) : null
                                            }
                                        </div>
                                    ))
                                }
                            </div>
                        ) : (
                            <div id="bvs-settings-menu-search-message">
                                No settings or sections match "{props.searchQuery}".
                            </div>
                        )
                    }
                    <div className="bvs-settings-search-footer">
                        <a href="#" onClick={(e) => { e.preventDefault(); clearSearch(); }}>
                            Clear search
                        </a>
                    </div>
                </div>
                :
                <div id="bvs-settings-menu">
                    {
                        menuStructure.map((group, i) => {
                            if (group.hasOwnProperty('header')) {
                                return <div className="bvs-settings-menu-header" key={i}>{group.header}</div>
                            } else {
                                return <Link
                                        to={`bvs-settings-group-${group.id}`}
                                        className="bvs-settings-menu-group"
                                        activeClass="active"
                                        spy={true}
                                        offset={-42}
                                        smooth={true}
                                        duration={400}
                                        key={i}
                                    >
                                    { group.icon && <Icon type={group.icon} /> } {group.name}
                                </Link>
                            }
                        })
                    }
                </div>
            }
        </div>
    );
}

MenuContainer.propTypes = {
    structure: PropTypes.array.isRequired,
    settings: PropTypes.object.isRequired,
    settingsChanged: PropTypes.bool.isRequired,
    savingChanges: PropTypes.bool.isRequired,
    onSaveChanges: PropTypes.func.isRequired,
    onCancelChanges: PropTypes.func.isRequired,
    searchQuery: PropTypes.string.isRequired,
    onSearchChange: PropTypes.func.isRequired,
    searchResults: PropTypes.array.isRequired,
    searchResultsCount: PropTypes.number.isRequired,
    onJumpToSearchResult: PropTypes.func.isRequired,
    changedSettings: PropTypes.array.isRequired,
    changedSettingsCount: PropTypes.number.isRequired,
    showChangedSettings: PropTypes.bool.isRequired,
    onToggleChangedSettings: PropTypes.func.isRequired,
    onJumpToChangedSetting: PropTypes.func.isRequired,
    onRevertChangedSetting: PropTypes.func.isRequired,
}

export default MenuContainer;
