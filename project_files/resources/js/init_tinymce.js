// Import TinyMCE
import tinymce from 'tinymce/tinymce';
// Default icons are required for TinyMCE 5.3 or above
import 'tinymce/icons/default';
// A theme is also required
import 'tinymce/themes/silver';
// Any plugins you want to use has to be imported
//import 'tinymce/plugins/paste';
//import 'tinymce/plugins/link';
window.initTinyMce = function (options) {
    let params = {
        selector: options.selector,
        width: 640,
        height: 400,
        language: 'ru',
        inline_styles : false,
        skin_url: '/vendors/tinymce/skins/ui/oxide',
        base_url: '/vendors/tinymce',
        plugins: 'link, lists, table, media, codesample, autolink, code, image, imagetools',
        content_css: '/vendors/tinymce/editor-styles.css',
        toolbar: 'link | numlist bullist outdent indent |  bold italic strikethrough |  alignleft aligncenter alignright alignjustify | forecolor backcolor | removeformat image',
        images_upload_url: '/admin/tiny_upload_image/'+options.fileprefix,
        automatic_uploads: true,
        branding: false,
        images_upload_handler: function (blobInfo, success, failure) {
            let xhr = new XMLHttpRequest();
            xhr.open('POST', '/admin/tiny_upload_image/'+options.fileprefix);
            xhr.setRequestHeader('X-CSRF-TOKEN', window._token); // manually set header

            xhr.onload = function() {
                if (xhr.status !== 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }

                let json = JSON.parse(xhr.responseText);

                if (!json || typeof json.location !== 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }

                success(json.location);
            };

            let formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            xhr.send(formData);
        }
    };
    params = Object.assign(params, options);
    tinymce.init(params);
};
