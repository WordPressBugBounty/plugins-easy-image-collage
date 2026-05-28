import React from 'react';
import PropTypes from 'prop-types';

import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

const SettingRichTextarea = (props) => {
    return (
        <ReactQuill
            className="bvs-setting-input"
            theme="snow"
            value={props.value}
            onChange={(content, delta, source) => {
                if('<p></p>' === content || '<p><br></p>' === content || '<p><br/></p>' === content) {
                    content = '';
                }

                if ('string' === typeof content) {
                    content = content.replace(/\s*spellcheck="false"/gi, '');
                }

                props.onValueChange(content);
            }}
            modules={{
                toolbar: [
                    [{ 'header': [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{'list': 'ordered'}, {'list': 'bullet'}],
                    ['link']
                ]
            }}
        />
    );
}

SettingRichTextarea.propTypes = {
    setting: PropTypes.object.isRequired,
    value: PropTypes.any.isRequired,
    onValueChange: PropTypes.func.isRequired,
}

export default SettingRichTextarea;
