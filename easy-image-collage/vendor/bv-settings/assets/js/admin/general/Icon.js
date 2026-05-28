import React from 'react';
import SVG from 'react-inlinesvg';

import IconArrows from '../../../icons/arrows.svg';
import IconBasket from '../../../icons/basket.svg';
import IconBook from '../../../icons/book.svg';
import IconBrush from '../../../icons/brush.svg';
import IconButtonClick from '../../../icons/button-click.svg';
import IconChart from '../../../icons/chart.svg';
import IconClock from '../../../icons/clock.svg';
import IconCode from '../../../icons/code.svg';
import IconCog from '../../../icons/cog.svg';
import IconCrane from '../../../icons/crane.svg';
import IconDocApple from '../../../icons/doc-apple.svg';
import IconDoc from '../../../icons/doc.svg';
import IconDollar from '../../../icons/dollar.svg';
import IconEdit from '../../../icons/edit.svg';
import IconFiles from '../../../icons/files.svg';
import IconFloppyDisk from '../../../icons/floppy-disk.svg';
import IconHealth from '../../../icons/health.svg';
import IconHeart from '../../../icons/heart.svg';
import IconImport from '../../../icons/import.svg';
import IconKey from '../../../icons/key.svg';
import IconKnife from '../../../icons/knife.svg';
import IconLetter from '../../../icons/letter.svg';
import IconLink from '../../../icons/link.svg';
import IconList from '../../../icons/list.svg';
import IconLock from '../../../icons/lock.svg';
import IconMeasureApple from '../../../icons/measure-apple.svg';
import IconModal from '../../../icons/modal.svg';
import IconPainting from '../../../icons/painting.svg';
import IconPalette from '../../../icons/palette.svg';
import IconPlug from '../../../icons/plug.svg';
import IconPrinter from '../../../icons/printer.svg';
import IconQuestion from '../../../icons/question.svg';
import IconQuestionBox from '../../../icons/question-box.svg';
import IconReceipt from '../../../icons/receipt.svg';
import IconSearch from '../../../icons/search.svg';
import IconShare from '../../../icons/share.svg';
import IconShoppingCart from '../../../icons/shopping-cart.svg';
import IconSliders from '../../../icons/sliders.svg';
import IconSpeed from '../../../icons/speed.svg';
import IconSparks from '../../../icons/sparks.svg';
import IconStar from '../../../icons/star.svg';
import IconSupport from '../../../icons/support.svg';
import IconText from '../../../icons/text.svg';
import IconTimeline from '../../../icons/timeline.svg';
import IconTouch from '../../../icons/touch.svg';
import IconUndo from '../../../icons/undo.svg';
import IconUnlink from '../../../icons/unlink.svg';
import IconUp from '../../../icons/up.svg';
import IconWarning from '../../../icons/warning.svg';
import IconWhisk from '../../../icons/whisk.svg';

const icons = {
    arrows: IconArrows,
    basket: IconBasket,
    book: IconBook,
    brush: IconBrush,
    'button-click': IconButtonClick,
    chart: IconChart,
    clock: IconClock,
    code: IconCode,
    cog: IconCog,
    crane: IconCrane,
    'doc-apple': IconDocApple,
    doc: IconDoc,
    dollar: IconDollar,
    edit: IconEdit,
    files: IconFiles,
    'floppy-disk': IconFloppyDisk,
    health: IconHealth,
    heart: IconHeart,
    import: IconImport,
    key: IconKey,
    knife: IconKnife,
    letter: IconLetter,
    link: IconLink,
    list: IconList,
    lock: IconLock,
    'measure-apple': IconMeasureApple,
    modal: IconModal,
    painting: IconPainting,
    palette: IconPalette,
    plug: IconPlug,
    printer: IconPrinter,
    question: IconQuestion,
    'question-box': IconQuestionBox,
    receipt: IconReceipt,
    search: IconSearch,
    share: IconShare,
    'shopping-cart': IconShoppingCart,
    sliders: IconSliders,
    speed: IconSpeed,
    sparks: IconSparks,
    star: IconStar,
    support: IconSupport,
    text: IconText,
    timeline: IconTimeline,
    touch: IconTouch,
    undo: IconUndo,
    unlink: IconUnlink,
    up: IconUp,
    warning: IconWarning,
    whisk: IconWhisk,
};

const Icon = (props) => {
    let icon = icons.hasOwnProperty(props.type) ? icons[props.type] : false;

    if ( !icon ) {
        return <span className="bvs-settings-noicon">&nbsp;</span>;
    }

    return (
        <span className='bvs-settings-icon'>
            <SVG
                src={icon}
            />
        </span>
    );
}
export default Icon;
