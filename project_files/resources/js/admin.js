import SlimSelect from 'slim-select';window.SlimSelect = SlimSelect;
import CodeMirror from 'codemirror';
window.CodeMirror = CodeMirror;
require ('codemirror/mode/css/css.js');
require ('codemirror/mode/xml/xml.js');
require ('codemirror/mode/htmlmixed/htmlmixed.js');
import { Fancybox } from "@fancyapps/ui";

window.createElementFromString = function (str) {
    const element = new DOMParser().parseFromString(str, 'text/html');
    return element.documentElement.querySelector('body').firstChild;
};

document.addEventListener("keydown", function(e) {
    const editForm = document.querySelector('form');
    if ((window.navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)  && e.keyCode == 83) {
        e.preventDefault();
        if(editForm){
            editForm.submit();
        }
    }
}, false);

document.addEventListener('click', async ev => {
    const arrow = ev.target.closest('[data-change-order-action]');
    if (!arrow) {
        return;
    }
    const response = await fetch(arrow.dataset.changeOrderAction).then((html) => html.text());
    const listingInner = document.querySelector('[data-entities-list]');
    listingInner.innerHTML = response;
});

document.addEventListener('click', async ev=>{
    const actionTool = ev.target.closest('[data-run-action]');
    if(actionTool){
        ev.preventDefault();
        ev.stopPropagation();
        const answer = await fetch(actionTool.href).then(response => response.text());
        showNote(answer);
    }
});

function showNote(text, state){
    state = state||'ok';
    const noteHtml = `<div class="note ${state}">${text}</div>`;
    const parser = new DOMParser();
    const noteElement = parser.parseFromString(noteHtml, 'text/html').body.firstElementChild;
    document.body.appendChild(noteElement);
    setTimeout(e=>noteElement.remove(),5000);
}

class belongToManyTool {
    addButtonSelector = '[data-add-belong-to-many-relation]';
    modificationsListSelector = '[data-belong-to-many-list]';
    patternSelector = '[data-belong-to-many-pattern]';
    removeModificationSelector = '[data-remove-relation]';
    modificationRowSelector = '[data-belong-to-many-row]';

    constructor (containerElement){
        this.mainElement = containerElement;
        this.addButton = this.mainElement.querySelector(this.addButtonSelector);
        this.addButton.addEventListener('click', ev => this.add.bind(this)(ev));
        this.modificationsList = this.mainElement.querySelector(this.modificationsListSelector);
        this.pattern = this.mainElement.querySelector(this.patternSelector);
        this.mainElement.addEventListener('click', ev => this.remove.bind(this)(ev));
        this.dataFieldName = this.mainElement.dataset.fieldName;
    }
    add(ev){
        ev.preventDefault();
        ev.stopPropagation();
        const newModificationElement = createElementFromString(this.pattern.innerHTML);
        const newModificationElementSelect = newModificationElement.querySelector('select');
        newModificationElementSelect.name = `${this.dataFieldName}[]`;
        this.modificationsList.classList.add('active');
        this.modificationsList.insertAdjacentElement(`beforeend`, newModificationElement);
        new SlimSelect({
            select: newModificationElementSelect
        });
        newModificationElement.querySelector(this.removeModificationSelector).addEventListener('click',ev => this.remove.bind(this)(ev));
    }
    remove(ev){
        const existsModificationElement = ev.target.closest(this.removeModificationSelector);
        if(existsModificationElement){
            ev.preventDefault();
            ev.stopPropagation();
            const modificationRow = existsModificationElement.closest(this.modificationRowSelector);
            modificationRow.remove();
        }
    }
}window.belongToManyTool = belongToManyTool;

class additionalVariantHandler {
    addVariantButtonSelector = '[data-additional-variants-button]';
    removeVariantButtonSelector = '[data-remove-variant-button]';
    addVariantSelectSelector = '[data-additional-variants-tool]';
    updateVariantButtonSelector = '[data-update-variant-button]';
    constructor(element) {
        this.rootElement = element;
        this.productId = document.querySelector('[data-product-id]').dataset.productId;
        this.equipAdditionalVariantsTool();
        this.equipRemoveVariantTool();
        this.equipUpdateVariantTool();
    }
    equipAdditionalVariantsTool(){
        this.rootElement.addEventListener('click', async ev => {
            if(!ev.target.closest(this.addVariantButtonSelector)){return;}
            ev.preventDefault();
            ev.stopPropagation();
            const contextVariantElementId = this.rootElement.querySelector('[data-available-variants]').value;
            const addVariantAction = `/admin/product/${this.productId}/add-variant/${contextVariantElementId}`
            const options = {
                method:'post',
                headers:{
                    'X-CSRF-TOKEN': window._token
                }
            }
            const responseContent = await fetch(addVariantAction, options).then(render => render.text());
            const responseDOM =  createElementFromString(responseContent);
            this.replaceAdditionVariantTool(responseDOM);
            const newProductVariantElement = responseDOM.querySelector('[data-exists-products-variant]');
            if(newProductVariantElement){
                const existsVariantsPlace = document.querySelector('[data-exists-products-variants]');
                existsVariantsPlace.appendChild(newProductVariantElement);
            }
        });
    }
    replaceAdditionVariantTool(DOM){
        const newVariantsAdditionToolElement = DOM.querySelector(this.addVariantSelectSelector);
        const existVariantsAdditionToolElement = document.querySelector(this.addVariantSelectSelector);
        existVariantsAdditionToolElement.replaceWith(newVariantsAdditionToolElement);
    }
    equipRemoveVariantTool(){
        this.rootElement.addEventListener('click', async ev => {
            const removeVariantButton = ev.target.closest(this.removeVariantButtonSelector);
            if(!removeVariantButton){return;}
            let confirmState;
            confirmState = confirm('Вы уверены?');
            if(!confirmState){
                return false;
            }
            ev.preventDefault();
            ev.stopPropagation();
            const contextVariantElement = removeVariantButton.closest('[data-exists-products-variant]');
            const contextVariantElementId = contextVariantElement.dataset.id;
            const removeVariantAction = `/admin/product/${this.productId}/delete-variant/${contextVariantElementId}`
            const options = {
                method:'delete',
                headers:{
                    'X-CSRF-TOKEN': window._token
                }
            }
            const responseContent = await fetch(removeVariantAction, options).then(render => render.text());
            const responseDOM =  createElementFromString(responseContent);
            this.replaceAdditionVariantTool(responseDOM);
            contextVariantElement.remove();
        });
    }
    equipUpdateVariantTool(){
        this.rootElement.addEventListener('click', async ev => {
            const removeVariantButton = ev.target.closest(this.updateVariantButtonSelector);
            if(!removeVariantButton){return;}
            ev.preventDefault();
            ev.stopPropagation();
            const contextVariantElement = removeVariantButton.closest('[data-exists-products-variant]');
            const contextVariantElementId = contextVariantElement.dataset.id;
            const data = new FormData(contextVariantElement);
            const updateVariantAction = `/admin/product/update-attachment-variant/${contextVariantElementId}`;
            const options = {
                method:'post',
                headers:{
                    'X-CSRF-TOKEN': window._token
                },
                body: data
            }
            const responseContent = await fetch(updateVariantAction, options).then(render => render.text());
            const responseDOM =  createElementFromString(responseContent);
            const productVariantElement = responseDOM.querySelector('[data-exists-products-variant]');
            contextVariantElement.replaceWith(productVariantElement);
        });
    }
}window.additionalVariantHandler = additionalVariantHandler;

const htmlContentAreas = document.querySelectorAll('[data-code-mirror]');
if(htmlContentAreas.length > 0){
    let codeMirror;
    htmlContentAreas.forEach((contentArea)=>{
        let mode = "htmlmixed";
        if(contentArea.dataset.codemirrorMode){
            mode = contentArea.dataset.codemirrorMode;
        }
        codeMirror = CodeMirror.fromTextArea(contentArea,{
            lineNumbers: true,
            mode: {
                name: mode,
                startOpen: false
            },
            theme: 'dracula'
        });
        codeMirror.setSize('auto', 500);
    })
}

document.addEventListener('input', async ev => {
    const requestNote = ev.target.closest('[data-update-request-note]');
    if (requestNote) {
        ev.preventDefault();
        ev.stopPropagation();
        const body = JSON.stringify({note:requestNote.value});
        const requestId = requestNote.closest('tr').dataset.entityId;
        const updateAction = `/admin/leads/update/${requestId}`;
        const headers = {
            'Content-Type': 'application/json;charset=utf-8',
            'X-CSRF-TOKEN': window._token
        }
        const options = {method: 'post', headers:headers, body:body};
        fetch(updateAction, options);
    }
});
