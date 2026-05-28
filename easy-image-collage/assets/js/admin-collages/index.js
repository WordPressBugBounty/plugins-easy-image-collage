import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import ReactTable from 'react-table';
import 'react-table/react-table.css';
import { Tooltip } from 'react-tippy';
import 'react-tippy/dist/tippy.css';

import './style.scss';

const config = window.eic_collages_admin || {};
const text = config.text || {};
const pageSizeOptions = [5, 10, 20, 25, 50, 100, 500];
const selectedColumnsStorageKey = 'eic-admin-manage-collages-columns';
const titleColumnMigrationStorageKey = 'eic-admin-manage-collages-columns-title-migrated';
const previewSmallWidth = 100;
const previewSmallMaxHeight = 100;

const getPreviewBorderColor = (collage) => {
    if (collage.previewBorderColor) {
        return collage.previewBorderColor;
    }

    const match = collage.previewHtml ? collage.previewHtml.match(/border:\s*\d+px\s+solid\s+([^;]+);/) : false;

    return match ? match[1].trim() : '#dcdcde';
};

const getPreviewBorderRadius = (collage) => Math.max(0, parseInt(collage.previewBorderRadius, 10) || 0);

const getPreviewFallbackDimensions = (collage) => ({
    width: Math.max(1, parseInt(collage.previewWidth, 10) || previewSmallWidth),
    height: Math.max(1, parseInt(collage.previewHeight, 10) || previewSmallWidth),
});

const getPreviewRenderDimensions = (previewWidth, previewHeight, targetWidth, targetMaxHeight = false) => {
    const widthScale = targetWidth / previewWidth;
    const heightScale = targetMaxHeight ? targetMaxHeight / previewHeight : widthScale;
    const scale = Math.min(widthScale, heightScale);

    return {
        renderedWidth: Math.min(targetWidth, Math.ceil(previewWidth * scale)),
        renderedHeight: targetMaxHeight ? Math.min(targetMaxHeight, Math.ceil(previewHeight * scale)) : Math.ceil(previewHeight * scale),
        scale,
    };
};

class EicCollagePreviewMarkup extends Component {
    constructor(props) {
        super(props);

        this.state = {
            measuredWidth: false,
            measuredHeight: false,
            snapX: 0,
            snapY: 0,
        };

        this.renderElement = false;
        this.scaleElement = false;
        this.measure = this.measure.bind(this);
    }

    componentDidMount() {
        this.measure();
    }

    componentDidUpdate() {
        this.measure();
    }

    getFallbackDimensions() {
        return getPreviewFallbackDimensions(this.props.collage);
    }

    measure() {
        if (!this.renderElement || !this.scaleElement) {
            return;
        }

        const frame = this.scaleElement.querySelector('.eic-frame');

        if (!frame) {
            return;
        }

        const measuredWidth = Math.max(1, Math.ceil(frame.offsetWidth));
        const measuredHeight = Math.max(1, Math.ceil(frame.offsetHeight));
        const renderRect = this.renderElement.getBoundingClientRect();
        const baseLeft = renderRect.left - this.state.snapX;
        const baseTop = renderRect.top - this.state.snapY;
        const snapX = Math.round(baseLeft) - baseLeft;
        const snapY = Math.round(baseTop) - baseTop;
        const nextState = {};

        if (
            measuredWidth !== this.state.measuredWidth
            || measuredHeight !== this.state.measuredHeight
        ) {
            nextState.measuredWidth = measuredWidth;
            nextState.measuredHeight = measuredHeight;
        }

        if (
            Math.abs(snapX - this.state.snapX) > 0.001
            || Math.abs(snapY - this.state.snapY) > 0.001
        ) {
            nextState.snapX = snapX;
            nextState.snapY = snapY;
        }

        if (Object.keys(nextState).length) {
            this.setState(nextState);
        }
    }

    render() {
        const { collage, targetWidth, targetMaxHeight } = this.props;
        const fallbackDimensions = this.getFallbackDimensions();
        const previewWidth = this.state.measuredWidth || fallbackDimensions.width;
        const previewHeight = this.state.measuredHeight || fallbackDimensions.height;
        const { renderedWidth, renderedHeight, scale } = getPreviewRenderDimensions(previewWidth, previewHeight, targetWidth, targetMaxHeight);
        const borderColor = getPreviewBorderColor(collage);
        const borderRadius = getPreviewBorderRadius(collage);
        const { snapX, snapY } = this.state;

        return (
            <div
                className="eic-collages-preview-render"
                ref={(element) => {
                    this.renderElement = element;
                }}
                style={{
                    width: renderedWidth,
                    height: renderedHeight,
                    transform: `translate(${snapX}px, ${snapY}px)`,
                    '--eic-preview-border-color': borderColor,
                    '--eic-preview-border-radius': `${borderRadius}px`,
                }}
            >
                <div
                    className="eic-collages-preview-scale"
                    ref={(element) => {
                        this.scaleElement = element;
                    }}
                    style={{
                        width: previewWidth,
                        height: previewHeight,
                        transform: `scale(${scale})`,
                    }}
                    dangerouslySetInnerHTML={{ __html: collage.previewHtml }}
                />
            </div>
        );
    }
}

const EicCollagePreviewPlaceholder = ({ collage, targetWidth, targetMaxHeight }) => {
    const fallbackDimensions = getPreviewFallbackDimensions(collage);
    const { renderedWidth, renderedHeight } = getPreviewRenderDimensions(fallbackDimensions.width, fallbackDimensions.height, targetWidth, targetMaxHeight);

    return (
        <div
            className="eic-collages-preview-placeholder"
            style={{
                width: renderedWidth,
                height: renderedHeight,
            }}
        >
            <span className="eic-collages-preview-spinner" role="status" aria-label={text.loading || 'Loading...'} />
        </div>
    );
};

const EicTooltip = (props) => {
    if (!props.content && !props.html) {
        return props.children;
    }

    return (
        <Tooltip
            html={props.html || (
                <div
                    dangerouslySetInnerHTML={{ __html: props.content }}
                />
            )}
            popperOptions={{
                modifiers: {
                    addZIndex: {
                        enabled: true,
                        order: 810,
                        fn: data => ({
                            ...data,
                            styles: {
                                ...data.styles,
                                zIndex: 100000,
                            },
                        }),
                    },
                },
            }}
        >
            {props.children}
        </Tooltip>
    );
};

const icons = {
    edit: (
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" focusable="false" aria-hidden="true">
            <g fill="#111111">
                <polygon fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" points="13,0.5 15.5,3 7.5,11 4,12 5,8.5" />
                <line fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" x1="11" y1="2.5" x2="13.5" y2="5" />
                <path fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" d="M13.5,9.5v5 c0,0.552-0.448,1-1,1h-11c-0.552,0-1-0.448-1-1v-11c0-0.552,0.448-1,1-1h5" />
            </g>
        </svg>
    ),
    duplicate: (
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" focusable="false" aria-hidden="true">
            <g strokeWidth="1" fill="#111111" stroke="#111111">
                <rect x="0.5" y="0.5" width="11" height="11" fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" />
                <polyline points="13.5 4.5 15.5 4.5 15.5 15.5 4.5 15.5 4.5 13.5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
            </g>
        </svg>
    ),
    delete: (
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" focusable="false" aria-hidden="true">
            <g strokeWidth="1" fill="#111111" stroke="#111111">
                <path fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" d="M2.5,6.5v7 c0,1.105,0.895,2,2,2h8c1.105,0,2-0.895,2-2v-7" />
                <line fill="none" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" x1="1.5" y1="3.5" x2="15.5" y2="3.5" />
                <polyline fill="none" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" points="6.5,3.5 6.5,0.5 10.5,0.5 10.5,3.5" />
                <line fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" x1="8.5" y1="7.5" x2="8.5" y2="12.5" />
                <line fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" x1="11.5" y1="7.5" x2="11.5" y2="12.5" />
                <line fill="none" stroke="#111111" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10" x1="5.5" y1="7.5" x2="5.5" y2="12.5" />
            </g>
        </svg>
    ),
    restore: (
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" focusable="false" aria-hidden="true">
            <g strokeWidth="1" fill="none" stroke="#111111">
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.5,8.5c0-3.314,2.686-6,6-6c2.272,0,4.25,1.263,5.269,3.125" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5,1.5v4.5h-4.5" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5,8.5c0,3.314-2.686,6-6,6c-2.272,0-4.25-1.263-5.269-3.125" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.5,15v-4.5h4.5" />
            </g>
        </svg>
    ),
};

let defaultPageSize = 20;
let savedPageSize = localStorage.getItem('eic-admin-manage-page-size');

if (savedPageSize) {
    savedPageSize = parseInt(savedPageSize);

    if (pageSizeOptions.includes(savedPageSize)) {
        defaultPageSize = savedPageSize;
    }
}

const apiRequest = ( url, method = 'POST', data = {} ) => {
    return jQuery.ajax({
        url,
        method,
        data: 'DELETE' === method ? undefined : JSON.stringify(data),
        contentType: 'application/json',
        beforeSend: (xhr) => {
            xhr.setRequestHeader('X-WP-Nonce', config.nonce);
        },
    });
};

class CollagesTable extends Component {
    constructor(props) {
        super(props);

        this.state = {
            data: [],
            pages: null,
            loading: true,
            filtered: [],
            filteredCount: false,
            totalCount: false,
            copiedShortcode: false,
            selectedColumns: this.getDefaultSelectedColumns(),
            viewStatus: 'active',
            trashCount: parseInt(config.trash_count, 10) || 0,
        };

        this.dataRequestToken = 0;
        this.previewRequestToken = 0;
        this.previewCache = {};
        this.fetchData = this.fetchData.bind(this);
        this.refreshData = this.refreshData.bind(this);
        this.fetchPreviewsForRows = this.fetchPreviewsForRows.bind(this);
        this.createCollage = this.createCollage.bind(this);
        this.editCollage = this.editCollage.bind(this);
        this.duplicateCollage = this.duplicateCollage.bind(this);
        this.deleteCollage = this.deleteCollage.bind(this);
        this.restoreCollage = this.restoreCollage.bind(this);
        this.permanentlyDeleteCollage = this.permanentlyDeleteCollage.bind(this);
        this.setViewStatus = this.setViewStatus.bind(this);
        this.onColumnsChange = this.onColumnsChange.bind(this);
    }

    getDefaultSelectedColumns() {
        const availableColumns = ['preview', 'title', 'id', 'shortcode', 'imageCount', 'usage', 'created', 'modified'];
        const defaultColumns = ['preview', 'title', 'shortcode', 'usage', 'modified'];

        let selectedColumns = defaultColumns;
        let savedSelectedColumns = localStorage.getItem(selectedColumnsStorageKey);

        if (savedSelectedColumns) {
            try {
                savedSelectedColumns = JSON.parse(savedSelectedColumns);
            } catch (error) {
                savedSelectedColumns = false;
            }

            if (Array.isArray(savedSelectedColumns)) {
                selectedColumns = savedSelectedColumns.filter(columnId => availableColumns.includes(columnId));

                if (!selectedColumns.includes('title') && !localStorage.getItem(titleColumnMigrationStorageKey)) {
                    const previewIndex = selectedColumns.indexOf('preview');
                    selectedColumns.splice(previewIndex > -1 ? previewIndex + 1 : 0, 0, 'title');
                    localStorage.setItem(selectedColumnsStorageKey, JSON.stringify(selectedColumns));
                }

                localStorage.setItem(titleColumnMigrationStorageKey, '1');
            }
        }

        if (!selectedColumns.length) {
            selectedColumns = defaultColumns;
        }

        return selectedColumns;
    }

    refreshData() {
        if (this.table) {
            this.table.fireFetchData();
        }
    }

    fetchData(state) {
        const dataRequestToken = ++this.dataRequestToken;
        this.previewRequestToken++;
        const viewStatus = this.state.viewStatus;

        this.setState({
            loading: true,
        }, () => {
            apiRequest(config.endpoints.collages, 'POST', {
                pageSize: state.pageSize,
                page: state.page,
                sorted: state.sorted,
                filtered: state.filtered,
                status: viewStatus,
            }).then((data) => {
                if (dataRequestToken !== this.dataRequestToken) {
                    return;
                }

                const rows = this.applyCachedPreviews(data.rows);

                this.setState({
                    data: rows,
                    pages: data.pages,
                    filteredCount: data.filtered,
                    totalCount: data.total,
                    trashCount: data.counts && undefined !== data.counts.trash ? parseInt(data.counts.trash, 10) || 0 : this.state.trashCount,
                    loading: false,
                }, () => this.fetchPreviewsForRows(rows));
            }).catch(() => {
                if (dataRequestToken !== this.dataRequestToken) {
                    return;
                }

                this.setState({
                    data: [],
                    pages: 0,
                    loading: false,
                });
            });
        });
    }

    createCollage() {
        EasyImageCollage.btnCreateGrid(0, () => this.refreshData());
    }

    editCollage(row) {
        this.prepareModalForRow(row);
        EasyImageCollage.btnEditGrid(row.id, () => {
            this.clearPreviewCache(row.id);
            this.refreshData();
        });
    }

    duplicateCollage(row) {
        apiRequest(config.endpoints.duplicate, 'POST', {
            id: row.id,
        }).then(() => this.refreshData());
    }

    deleteCollage(row) {
        const confirmText = row.usageCount
            ? (text.delete_confirm_used || '').replace('%d', row.usageCount)
            : text.delete_confirm;

        if (!window.confirm(confirmText)) {
            return;
        }

        const endpoint = config.endpoints.delete.replace('%id%', row.id);
        apiRequest(endpoint, 'DELETE').then(() => {
            this.clearPreviewCache(row.id);
            this.refreshData();
        });
    }

    restoreCollage(row) {
        const endpoint = config.endpoints.restore.replace('%id%', row.id);
        apiRequest(endpoint, 'POST').then(() => {
            this.clearPreviewCache(row.id);
            this.refreshData();
        });
    }

    permanentlyDeleteCollage(row) {
        const confirmText = row.usageCount
            ? (text.permanent_delete_confirm_used || '').replace('%d', row.usageCount)
            : text.permanent_delete_confirm;

        if (!window.confirm(confirmText)) {
            return;
        }

        const endpoint = config.endpoints.permanent_delete.replace('%id%', row.id);
        apiRequest(endpoint, 'DELETE').then(() => {
            this.clearPreviewCache(row.id);
            this.refreshData();
        });
    }

    setViewStatus(viewStatus) {
        if (viewStatus === this.state.viewStatus) {
            return;
        }

        this.dataRequestToken++;
        this.previewRequestToken++;

        this.setState({
            viewStatus,
            data: [],
            pages: null,
            loading: true,
        });
    }

    prepareModalForRow(row) {
        EasyImageCollage.registerGrid(row.id, {
            ...row.gridData,
            name: row.title,
        }, row.customLayoutHtml);
    }

    copyShortcode(row) {
        const shortcode = row.shortcode;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shortcode);
        } else {
            const input = document.createElement('textarea');
            input.value = shortcode;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }

        this.setState({
            copiedShortcode: row.id,
        });

        window.setTimeout(() => {
            this.setState({
                copiedShortcode: false,
            });
        }, 1500);
    }

    onColumnsChange(id, checked) {
        let selectedColumns = [...this.state.selectedColumns];

        if (checked) {
            selectedColumns.push(id);
        } else {
            selectedColumns = selectedColumns.filter(columnId => columnId !== id);
        }

        this.setState({
            selectedColumns,
        }, () => {
            if ('preview' === id && checked) {
                this.fetchPreviewsForRows(this.state.data);
            }
        });

        localStorage.setItem(selectedColumnsStorageKey, JSON.stringify(selectedColumns));
        localStorage.setItem(titleColumnMigrationStorageKey, '1');

        if ('preview' === id && !checked) {
            this.previewRequestToken++;
        }
    }

    getFilteredColumnIds() {
        return this.state.filtered
            .filter(filter => '' !== filter.value && 'all' !== filter.value)
            .map(filter => filter.id);
    }

    getVisibleColumns(columns) {
        return columns.filter(column => 'actions' === column.id || this.state.selectedColumns.includes(column.id));
    }

    isPreviewColumnVisible() {
        return this.state.selectedColumns.includes('preview');
    }

    getPreviewCacheKey(id) {
        return parseInt(id, 10) || id;
    }

    clearPreviewCache(id) {
        delete this.previewCache[this.getPreviewCacheKey(id)];
        this.previewRequestToken++;
    }

    applyCachedPreview(row) {
        const cacheKey = this.getPreviewCacheKey(row.id);
        const preview = this.previewCache[cacheKey];

        return preview ? {
            ...row,
            ...preview,
        } : row;
    }

    applyCachedPreviews(rows) {
        return rows.map(row => this.applyCachedPreview(row));
    }

    fetchPreviewsForRows(rows) {
        if (!config.endpoints || !config.endpoints.previews || !this.isPreviewColumnVisible()) {
            return;
        }

        const ids = rows
            .filter(row => row.imageCount && !this.previewCache[this.getPreviewCacheKey(row.id)])
            .map(row => row.id);

        if (!ids.length) {
            return;
        }

        const previewRequestToken = ++this.previewRequestToken;

        apiRequest(config.endpoints.previews, 'POST', {
            ids,
        }).then((data) => {
            if (previewRequestToken !== this.previewRequestToken || !data || !data.previews) {
                return;
            }

            Object.keys(data.previews).forEach((id) => {
                const preview = data.previews[id];

                this.previewCache[this.getPreviewCacheKey(id)] = {
                    previewHtml: preview.previewHtml || '',
                    previewLoaded: true,
                };
            });

            this.setState(previousState => ({
                data: this.applyCachedPreviews(previousState.data),
            }));
        });
    }

    renderIcon(type, title, onClick, className = '') {
        return (
            <EicTooltip content={title}>
                <button
                    type="button"
                    className={`eic-admin-icon ${className}`}
                    title={title}
                    aria-label={title}
                    onClick={onClick}
                >
                    {icons[type]}
                </button>
            </EicTooltip>
        );
    }

    getColumns() {
        return [
            {
                Header: text.sort || 'Sort:',
                id: 'actions',
                headerClassName: 'eic-admin-table-help-text',
                sortable: false,
                width: 80,
                Filter: () => (
                    <div>
                        {text.filter || 'Filter:'}
                    </div>
                ),
                Cell: row => {
                    const collage = row.original;

                    return (
                        <div className="eic-admin-manage-actions">
                            {
                                collage.isTrash && collage.canRestore
                                    ? this.renderIcon('restore', text.restore, () => this.restoreCollage(collage))
                                    : null
                            }
                            {
                                collage.isTrash && collage.canDelete
                                    ? this.renderIcon('delete', text.delete_permanently || text.delete, () => this.permanentlyDeleteCollage(collage), 'eic-collages-delete')
                                    : null
                            }
                            {
                                !collage.isTrash && collage.canEdit
                                    ? this.renderIcon('edit', text.edit, () => this.editCollage(collage))
                                    : null
                            }
                            {
                                !collage.isTrash && collage.canDuplicate
                                    ? this.renderIcon('duplicate', text.duplicate, () => this.duplicateCollage(collage))
                                    : null
                            }
                            {
                                !collage.isTrash && collage.canDelete
                                    ? this.renderIcon('delete', text.delete, () => this.deleteCollage(collage), 'eic-collages-delete')
                                    : null
                            }
                        </div>
                    );
                },
            },
            {
                Header: text.preview || 'Preview',
                id: 'preview',
                accessor: 'previewHtml',
                sortable: false,
                filterable: false,
                width: 140,
                className: 'eic-collages-preview-cell',
                Cell: row => this.renderPreview(row.original),
            },
            {
                Header: 'ID',
                id: 'id',
                accessor: 'id',
                width: 80,
            },
            {
                Header: text.name || 'Name',
                id: 'title',
                accessor: 'title',
                minWidth: 180,
            },
            {
                Header: text.shortcode || 'Shortcode',
                id: 'shortcode',
                accessor: 'shortcode',
                sortable: false,
                minWidth: 280,
                Cell: row => {
                    const tooltip = this.state.copiedShortcode === row.original.id ? (text.copied_exclamation || text.copied) : (text.copy_to_clipboard || text.copy_shortcode);

                    return (
                        <div className="eic-collages-shortcode">
                            <EicTooltip content={tooltip}>
                                <button
                                    type="button"
                                    className="eic-collages-shortcode-copy"
                                    title={tooltip}
                                    onClick={() => this.copyShortcode(row.original)}
                                >
                                    <code>{row.value}</code>
                                </button>
                            </EicTooltip>
                        </div>
                    );
                },
            },
            {
                Header: text.images || 'Images',
                id: 'imageCount',
                accessor: 'imageCount',
                width: 90,
            },
            {
                Header: text.used_in || 'Used In',
                id: 'usage',
                accessor: 'usage',
                sortable: false,
                minWidth: 300,
                Cell: row => this.renderUsage(row.value),
            },
            {
                Header: text.created || 'Created',
                id: 'created',
                accessor: 'created',
                width: 160,
            },
            {
                Header: text.last_modified || 'Last Modified',
                id: 'modified',
                accessor: 'modified',
                width: 160,
            },
        ];
    }

    renderPreview(collage) {
        if (!collage.imageCount) {
            return (
                <div className="eic-collages-preview eic-collages-preview-empty">
                    {text.no_images}
                </div>
            );
        }

        if (!collage.previewHtml && !collage.previewLoaded) {
            return (
                <div className="eic-collages-preview eic-collages-preview-loading">
                    <EicCollagePreviewPlaceholder collage={collage} targetWidth={previewSmallWidth} targetMaxHeight={previewSmallMaxHeight} />
                </div>
            );
        }

        if (!collage.previewHtml) {
            return (
                <div className="eic-collages-preview eic-collages-preview-empty">
                    {text.no_images}
                </div>
            );
        }

        return (
            <div className="eic-collages-preview">
                {this.renderPreviewMarkup(collage, previewSmallWidth, previewSmallMaxHeight)}
            </div>
        );
    }

    renderPreviewMarkup(collage, targetWidth, targetMaxHeight = false) {
        return <EicCollagePreviewMarkup collage={collage} targetWidth={targetWidth} targetMaxHeight={targetMaxHeight} />;
    }

    renderUsage(usage) {
        if (!usage || !usage.length) {
            return <span className="eic-collages-usage-empty">{text.not_used}</span>;
        }

        return (
            <div className="eic-collages-usage-list">
                {usage.slice(0, 3).map((item) => {
                    const label = `${item.title} (${item.type})`;
                    const status = 'publish' === item.status ? null : <span className="eic-collages-usage-status">{item.status}</span>;

                    return item.url
                        ? (
                            <a className="eic-collages-usage-chip" href={item.url} key={item.id}>
                                {label}{status}
                            </a>
                        )
                        : (
                            <span className="eic-collages-usage-chip" key={item.id}>
                                {label}{status}
                            </span>
                        );
                })}
                {usage.length > 3 && <span className="eic-collages-usage-more">+{usage.length - 3} {text.more}</span>}
            </div>
        );
    }

    renderColumnSelector(columns) {
        const filteredColumns = this.getFilteredColumnIds();

        return (
            <div className="eic-admin-manage-select-columns-container">
                <div className="eic-admin-manage-select-columns">
                    {
                        columns.map((column, index) => {
                            if ('actions' === column.id) {
                                return null;
                            }

                            const selected = this.state.selectedColumns.includes(column.id);
                            const filtered = filteredColumns.includes(column.id);
                            let classNames = ['eic-admin-manage-select-columns-column'];

                            if (selected) {
                                classNames.push('eic-admin-manage-select-columns-column-selected');
                            }

                            if (filtered) {
                                classNames.push('eic-admin-manage-select-columns-column-filtered');
                            }

                            return (
                                <span
                                    className={classNames.join(' ')}
                                    onClick={(event) => {
                                        event.preventDefault();

                                        if (!filtered) {
                                            this.onColumnsChange(column.id, !selected);
                                        }
                                    }}
                                    key={index}
                                >{column.Header}</span>
                            );
                        })
                    }
                </div>
            </div>
        );
    }

    renderManageMenu() {
        const trashCount = parseInt(this.state.trashCount, 10) || 0;
        const trashLabel = trashCount > 0
            ? `${text.trash || 'Trash'} (${Number(trashCount).toLocaleString()})`
            : text.trash || 'Trash';

        const tabs = [
            { id: 'active', label: text.overview || 'Overview' },
            { id: 'trash', label: trashLabel },
        ];

        return (
            <div id="eic-admin-manage-header">
                <div className="eic-admin-manage-parent-menu">
                    <a
                        href="#"
                        className="eic-admin-manage-menu-item eic-admin-manage-menu-item-active"
                        onClick={event => event.preventDefault()}
                    >
                        {text.image_collages || 'Image Collages'}
                    </a>
                </div>
                <div className="eic-admin-manage-child-menu">
                    {tabs.map(tab => (
                        <a
                            href={`#${tab.id}`}
                            key={tab.id}
                            className={`eic-admin-manage-menu-item ${this.state.viewStatus === tab.id ? 'eic-admin-manage-menu-item-active' : ''}`}
                            onClick={(event) => {
                                event.preventDefault();
                                this.setViewStatus(tab.id);
                            }}
                        >
                            {tab.label}
                        </a>
                    ))}
                </div>
            </div>
        );
    }

    renderTrashNotice() {
        const emptyTrashDays = parseInt(config.trash_auto_delete_days, 10) || 0;

        if ('trash' !== this.state.viewStatus || emptyTrashDays < 1) {
            return null;
        }

        return (
            <div className="notice notice-warning eic-admin-trash-notice">
                <p>{text.trash_auto_delete_notice}</p>
            </div>
        );
    }

    renderTotals() {
        const { filteredCount, totalCount } = this.state;

        if (false === filteredCount || false === totalCount) {
            return <div className="eic-admin-table-totals">&nbsp;</div>;
        }

        if (filteredCount === totalCount) {
            return (
                <div className="eic-admin-table-totals">
                    {text.showing || 'Showing'} {Number(totalCount).toLocaleString()} {text.total || 'total'}
                </div>
            );
        }

        return (
            <div className="eic-admin-table-totals">
                {text.showing || 'Showing'} {Number(filteredCount).toLocaleString()} {text.filtered_of || 'filtered of'} {Number(totalCount).toLocaleString()} {text.total || 'total'}
            </div>
        );
    }

    render() {
        const { data, pages, loading } = this.state;
        const columns = this.getColumns();
        const visibleColumns = this.getVisibleColumns(columns);

        return (
            <React.Fragment>
                {this.renderManageMenu()}
                {this.renderTrashNotice()}
                <div id="eic-admin-manage-content">
                    <div className="eic-admin-manage-page">
                        <div className="eic-admin-manage-header">
                            {this.renderColumnSelector(columns)}
                            <div className="eic-admin-manage-header-buttons">
                                {
                                    config.can_create && 'active' === this.state.viewStatus
                                    && <button
                                        type="button"
                                        className="button button-primary button-compact"
                                        onClick={this.createCollage}
                                    >{text.add_new}</button>
                                }
                            </div>
                        </div>
                        <div className="eic-admin-manage-table-container">
                            {this.renderTotals()}
                            <div className="eic-admin-manage-table-inner">
                                <ReactTable
                                    key={this.state.viewStatus}
                                    ref={(table) => { this.table = table; }}
                                    manual
                                    columns={visibleColumns}
                                    data={data}
                                    pages={pages}
                                    filtered={this.state.filtered}
                                    onFilteredChange={filtered => this.setState({ filtered })}
                                    loading={loading}
                                    onFetchData={this.fetchData}
                                    defaultPageSize={defaultPageSize}
                                    pageSizeOptions={pageSizeOptions}
                                    onPageSizeChange={(pageSize) => {
                                        localStorage.setItem('eic-admin-manage-page-size', pageSize);
                                    }}
                                    defaultSorted={[{
                                        id: 'created',
                                        desc: true,
                                    }]}
                                    filterable
                                    resizable={false}
                                    className="eic-admin-manage-table eic-admin-table -highlight"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </React.Fragment>
        );
    }
}

const container = document.getElementById('eic-admin-manage');

if (container) {
    ReactDOM.render(<CollagesTable />, container);
}
