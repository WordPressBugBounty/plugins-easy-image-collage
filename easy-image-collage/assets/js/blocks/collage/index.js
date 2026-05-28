const { __ } = wp.i18n;
const {
    Button,
    Disabled,
    Modal,
    Spinner,
    TextControl,
    ToolbarGroup,
    ToolbarButton,
} = wp.components;
const ServerSideRender = wp.serverSideRender;
const { registerBlockType } = wp.blocks;
const { Component, Fragment } = wp.element;
const {
    BlockControls,
    useBlockProps,
} = wp.blockEditor;
const apiFetch = wp.apiFetch;

import '../../../../css/public.scss';
import './editor.scss';

const pickerConfig = window.eic_blocks || {};
const pickerEndpoints = pickerConfig.endpoints || {};
const pickerPreviewWidth = 120;
const pickerPreviewMaxHeight = 90;

const getPreviewRenderDimensions = ( previewWidth, previewHeight, targetWidth, targetMaxHeight ) => {
    const widthScale = targetWidth / previewWidth;
    const heightScale = targetMaxHeight / previewHeight;
    const scale = Math.min( widthScale, heightScale );

    return {
        renderedWidth: Math.min( targetWidth, Math.ceil( previewWidth * scale ) ),
        renderedHeight: Math.min( targetMaxHeight, Math.ceil( previewHeight * scale ) ),
        scale,
    };
};

const getPreviewBorderRadius = ( collage ) => Math.max( 0, parseInt( collage.previewBorderRadius, 10 ) || 0 );

class CollagePreviewMarkup extends Component {
    constructor( props ) {
        super( props );

        this.state = {
            measuredWidth: false,
            measuredHeight: false,
            snapX: 0,
            snapY: 0,
        };

        this.renderElement = false;
        this.scaleElement = false;
        this.measure = this.measure.bind( this );
    }

    componentDidMount() {
        this.measure();
    }

    componentDidUpdate() {
        this.measure();
    }

    measure() {
        if ( ! this.renderElement || ! this.scaleElement ) {
            return;
        }

        const frame = this.scaleElement.querySelector( '.eic-frame' );

        if ( ! frame ) {
            return;
        }

        const measuredWidth = Math.max( 1, Math.ceil( frame.offsetWidth ) );
        const measuredHeight = Math.max( 1, Math.ceil( frame.offsetHeight ) );
        const renderRect = this.renderElement.getBoundingClientRect();
        const baseLeft = renderRect.left - this.state.snapX;
        const baseTop = renderRect.top - this.state.snapY;
        const snapX = Math.round( baseLeft ) - baseLeft;
        const snapY = Math.round( baseTop ) - baseTop;
        const nextState = {};

        if (
            measuredWidth !== this.state.measuredWidth
            || measuredHeight !== this.state.measuredHeight
        ) {
            nextState.measuredWidth = measuredWidth;
            nextState.measuredHeight = measuredHeight;
        }

        if (
            Math.abs( snapX - this.state.snapX ) > 0.001
            || Math.abs( snapY - this.state.snapY ) > 0.001
        ) {
            nextState.snapX = snapX;
            nextState.snapY = snapY;
        }

        if ( Object.keys( nextState ).length ) {
            this.setState( nextState );
        }
    }

    render() {
        const { collage } = this.props;
        const fallbackWidth = Math.max( 1, parseInt( collage.previewWidth, 10 ) || pickerPreviewWidth );
        const fallbackHeight = Math.max( 1, parseInt( collage.previewHeight, 10 ) || pickerPreviewWidth );
        const previewWidth = this.state.measuredWidth || fallbackWidth;
        const previewHeight = this.state.measuredHeight || fallbackHeight;
        const { renderedWidth, renderedHeight, scale } = getPreviewRenderDimensions( previewWidth, previewHeight, pickerPreviewWidth, pickerPreviewMaxHeight );
        const borderRadius = getPreviewBorderRadius( collage );
        const { snapX, snapY } = this.state;

        return (
            <div
                className="eic-block-collage-picker-preview-render"
                ref={ ( element ) => {
                    this.renderElement = element;
                } }
                style={ {
                    width: renderedWidth,
                    height: renderedHeight,
                    transform: `translate(${ snapX }px, ${ snapY }px)`,
                    '--eic-preview-border-radius': `${ borderRadius }px`,
                } }
            >
                <div
                    className="eic-block-collage-picker-preview-scale"
                    ref={ ( element ) => {
                        this.scaleElement = element;
                    } }
                    style={ {
                        width: previewWidth,
                        height: previewHeight,
                        transform: `scale(${ scale })`,
                    } }
                    dangerouslySetInnerHTML={ { __html: collage.previewHtml } }
                />
            </div>
        );
    }
}

const CollagePreview = ( { collage } ) => {
    if ( ! collage.imageCount ) {
        return (
            <div className="eic-block-collage-picker-preview eic-block-collage-picker-preview-empty">
                { __( 'No images', 'easy-image-collage' ) }
            </div>
        );
    }

    if ( ! collage.previewHtml && ! collage.previewLoaded ) {
        return (
            <div className="eic-block-collage-picker-preview eic-block-collage-picker-preview-loading">
                <Spinner />
            </div>
        );
    }

    if ( ! collage.previewHtml ) {
        return (
            <div className="eic-block-collage-picker-preview eic-block-collage-picker-preview-empty">
                { __( 'No preview', 'easy-image-collage' ) }
            </div>
        );
    }

    return (
        <div className="eic-block-collage-picker-preview">
            <CollagePreviewMarkup collage={ collage } />
        </div>
    );
};

class ExistingCollagePicker extends Component {
    constructor( props ) {
        super( props );

        this.state = {
            search: '',
            results: [],
            loading: true,
            error: false,
        };

        this.searchRequestToken = 0;
        this.previewRequestToken = 0;
        this.searchTimeout = false;
        this.fetchResults = this.fetchResults.bind( this );
        this.fetchPreviews = this.fetchPreviews.bind( this );
        this.onSearchChange = this.onSearchChange.bind( this );
    }

    componentDidMount() {
        this.fetchResults( '' );
    }

    componentWillUnmount() {
        window.clearTimeout( this.searchTimeout );
        this.searchRequestToken++;
        this.previewRequestToken++;
    }

    onSearchChange( search ) {
        this.setState( { search } );
        window.clearTimeout( this.searchTimeout );
        this.searchTimeout = window.setTimeout( () => {
            this.fetchResults( search );
        }, 250 );
    }

    fetchResults( search ) {
        if ( ! apiFetch || ! pickerEndpoints.collage_search ) {
            this.setState( {
                results: [],
                loading: false,
                error: true,
            } );
            return;
        }

        const searchRequestToken = ++this.searchRequestToken;
        this.previewRequestToken++;

        this.setState( {
            loading: true,
            error: false,
        } );

        apiFetch( {
            path: pickerEndpoints.collage_search,
            method: 'POST',
            data: { search },
        } ).then( ( data ) => {
            if ( searchRequestToken !== this.searchRequestToken ) {
                return;
            }

            const results = data && data.rows ? data.rows : [];

            this.setState( {
                results,
                loading: false,
            }, () => this.fetchPreviews( results ) );
        } ).catch( () => {
            if ( searchRequestToken !== this.searchRequestToken ) {
                return;
            }

            this.setState( {
                results: [],
                loading: false,
                error: true,
            } );
        } );
    }

    fetchPreviews( results ) {
        if ( ! apiFetch || ! pickerEndpoints.previews ) {
            return;
        }

        const ids = results
            .filter( collage => collage.imageCount )
            .map( collage => collage.id );

        if ( ! ids.length ) {
            return;
        }

        const previewRequestToken = ++this.previewRequestToken;

        apiFetch( {
            path: pickerEndpoints.previews,
            method: 'POST',
            data: { ids },
        } ).then( ( data ) => {
            if ( previewRequestToken !== this.previewRequestToken || ! data || ! data.previews ) {
                return;
            }

            this.setState( previousState => ( {
                results: previousState.results.map( ( collage ) => {
                    const preview = data.previews[ collage.id ];

                    if ( ! preview ) {
                        return collage;
                    }

                    return {
                        ...collage,
                        previewHtml: preview.previewHtml || '',
                        previewLoaded: true,
                    };
                } ),
            } ) );
        } ).catch( () => {
            if ( previewRequestToken !== this.previewRequestToken ) {
                return;
            }

            this.setState( previousState => ( {
                results: previousState.results.map( collage => ( {
                    ...collage,
                    previewLoaded: true,
                } ) ),
            } ) );
        } );
    }

    renderResults() {
        if ( this.state.loading ) {
            return (
                <div className="eic-block-collage-picker-status">
                    <Spinner />
                    <span>
                        {
                            this.state.search
                                ? __( 'Searching collages...', 'easy-image-collage' )
                                : __( 'Loading latest collages...', 'easy-image-collage' )
                        }
                    </span>
                </div>
            );
        }

        if ( this.state.error ) {
            return (
                <div className="eic-block-collage-picker-status">
                    { __( 'Could not load image collages.', 'easy-image-collage' ) }
                </div>
            );
        }

        if ( ! this.state.results.length ) {
            return (
                <div className="eic-block-collage-picker-status">
                    { __( 'No image collages found.', 'easy-image-collage' ) }
                </div>
            );
        }

        return (
            <div className="eic-block-collage-picker-results">
                { this.state.results.map( collage => (
                    <button
                        type="button"
                        className="eic-block-collage-picker-result"
                        key={ collage.id }
                        onClick={ () => this.props.onSelect( collage.id ) }
                    >
                        <CollagePreview collage={ collage } />
                        <span className="eic-block-collage-picker-result-details">
                            <span className="eic-block-collage-picker-result-title">{ collage.title }</span>
                            <span className="eic-block-collage-picker-result-meta">
                                { __( 'ID', 'easy-image-collage' ) }: { collage.id } / { collage.imageCount } { __( 'images', 'easy-image-collage' ) }
                            </span>
                        </span>
                    </button>
                ) ) }
            </div>
        );
    }

    render() {
        return (
            <Modal
                title={ __( 'Add existing Image Collage', 'easy-image-collage' ) }
                onRequestClose={ this.props.onClose }
                className="eic-block-collage-picker-modal"
            >
                <div className="eic-block-collage-picker">
                    <TextControl
                        label={ __( 'Search image collages', 'easy-image-collage' ) }
                        value={ this.state.search }
                        onChange={ this.onSearchChange }
                        placeholder={ __( 'Search by name or ID', 'easy-image-collage' ) }
                    />
                    { this.renderResults() }
                </div>
            </Modal>
        );
    }
}

registerBlockType( 'easy-image-collage/collage', {
    apiVersion: 3,
    title: __( 'Easy Image Collage' ),
    description: __( 'Display multiple images in a collage.' ),
    icon: 'layout',
    keywords: [ 'eic' ],
    category: 'layout',
    supports: {
		html: false,
    },
    transforms: {
        from: [
            {
                type: 'shortcode',
                tag: 'easy-image-collage',
                attributes: {
                    id: {
                        type: 'number',
                        shortcode: ( { named: { id = '' } } ) => {
                            return parseInt( id.replace( 'id', '' ) );
                        },
                    },
                },
            },
        ]
    },
    edit: (props) => {
        const { attributes, setAttributes, className } = props;
        const blockProps = useBlockProps ? useBlockProps() : { className };
        const [ isPickerOpen, setPickerOpen ] = wp.element.useState( false );

        const modalCallback = ( id ) => {
            setAttributes({
                id,
                updated: Date.now(),
            });
        };

        return (
            <div { ...blockProps }>{
                attributes.id
                ?
                <Fragment>
                    <BlockControls>
                        <ToolbarGroup>
                            <ToolbarButton
                                icon="edit"
                                label={ __( 'Edit' ) }
                                onClick={ () => { EasyImageCollage.btnEditGrid( attributes.id, modalCallback ); } }
                            />
                        </ToolbarGroup>
                    </BlockControls>
                    <Disabled>    
                        <ServerSideRender
                            block="easy-image-collage/collage"
                            attributes={ attributes }
                        />
                    </Disabled>
                </Fragment>
                :
                <Fragment>
                    <div className="eic-block-collage-empty-actions">
                        <Button
                            isPrimary
                            isLarge
                            onClick={ () => {
                                EasyImageCollage.btnCreateGrid( attributes.id, modalCallback );
                            }}>
                            { __( 'Create new Image Collage' ) }
                        </Button>
                        <Button
                            isSecondary
                            isLarge
                            onClick={ () => setPickerOpen( true ) }>
                            { __( 'Add existing Image Collage', 'easy-image-collage' ) }
                        </Button>
                    </div>
                    { isPickerOpen && (
                        <ExistingCollagePicker
                            onClose={ () => setPickerOpen( false ) }
                            onSelect={ ( id ) => {
                                modalCallback( id );
                                setPickerOpen( false );
                            } }
                        />
                    ) }
                </Fragment>
            }</div>
        )
    },
    save: (props) => {
        const id = props.attributes.id;

        if ( !id ) {
            return null;
        } else {
            // Store shortcode for compatibility with Classic Editor.
            return `[easy-image-collage id=${props.attributes.id}]`;
        }
    },
    deprecated: [
        {
            attributes: {
                id: {
                    type: 'number',
                    default: 0
                }
            },
            save: (props) => { return null; }
        }
    ],
} );
