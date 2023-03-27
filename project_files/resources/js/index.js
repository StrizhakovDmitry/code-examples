"use strict";
import Inputmask from 'inputmask';

const im = new Inputmask('+9 (999) 999-99-99');
document.addEventListener('focus', ev => {
    if (ev.target.type === 'tel') {
        im.mask(ev.target)
    }
}, true);

import SlimSelect from 'slim-select';

window.SlimSelect = SlimSelect;
import noUiSlider from 'nouislider';
import {isNumber} from "uikit/src/js/util";

window.noUiSlider = noUiSlider;

import {Fancybox} from "@fancyapps/ui";

const ajaxOptions = {
    headers: {
        requestType: 'xmlhttprequest'
    }
}

function isInt(n) {
    return Number(n) === n && n % 1 === 0;
}

function isFloat(n) {
    return Number(n) === n && n % 1 !== 0;
}

function toNumeric(n) {
    if (isFloat(n)) {
        return Number(n);
    } else {
        return parseFloat(n);
    }
}

window.createElementFromString = function (str) {
    const element = new DOMParser().parseFromString(str, 'text/html');
    return element.documentElement.querySelector('body').firstChild;
};

//возвращает элемент из "HTML портянки", в которой может содержаться много блоков, selector - идентификатор нужного блока.
//Используется для ajax ответа с сервера и разбора содержимого по разным частям страницы (чтобы не делать несколько отдельных запросов)
String.prototype.makeNode = function (selector) {
    const renderElement = createElementFromString(`<div>${this}</div>`);
    return renderElement.querySelector(selector);
}

class urlOperations {
    static replaceQueryParametersWithFormData(formData) {
        const addr = new URL(window.location);
        formData.forEach((value, key) => {
            addr.searchParams.set(key, value);
        });
        window.history.pushState({}, '', addr.href);
    }

    static replaceUrlPath(newUrl) {
        const addr = new URL(window.location);
        addr.pathname = newUrl;
        window.history.pushState({}, '', addr.href);
    }
}

FormData.prototype.addParamsToUrl = function () {
    const addr = new URL(window.location);
    this.forEach((value, key) => {
        addr.searchParams.set(key, value);
    });
    window.history.pushState({}, '', addr.href);
}

document.addEventListener('click', e => {
    const videoElement = e.target.closest('[data-modal-video-url]');
    if (videoElement) {
        e.preventDefault();
        e.stopPropagation();
        const deviceType = document.body.dataset.deviceType;
        const frameWidthPercentage = deviceType === 'desktop' ? 80 : 100;
        const frameHeightPercentage = deviceType === 'desktop' ? 80 : 40;
        const youtubeLink = videoElement.dataset.modalVideoUrl;
        const frameHTML = `<iframe width="${frameWidthPercentage}%" height="${frameHeightPercentage}%" src="${youtubeLink}?autoplay=1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        const youtubeFrameModalBg = document.createElement('div');
        youtubeFrameModalBg.classList.add('youtube-frame-modal-bg');
        youtubeFrameModalBg.innerHTML = frameHTML;
        document.body.appendChild(youtubeFrameModalBg);
        youtubeFrameModalBg.addEventListener('click', e => {
            youtubeFrameModalBg.remove();
        });
    }
});

Object.defineProperty(HTMLElement.prototype, 'nthChild', {
    get: function () {
        let index = 1;
        for (let loopElement of this.parentElement.children) {
            if (loopElement === this) {
                return index
            } else {
                index++
            }
        }
    }
});

document.addEventListener('click', (e) => {
    const clickedPane = e.target.closest('[data-tabs-block] [data-panes] [data-pane]');
    if (clickedPane) {
        clickedPane.closest('[data-panes]').querySelectorAll('[data-pane]').forEach(element => {
            if (element === clickedPane) {
                element.classList.add('active');
            } else {
                element.classList.remove('active');
            }
        });
        const contentPane = clickedPane.closest('[data-tabs-block]').querySelector('[data-container]:nth-child(' + clickedPane.nthChild + ')');
        contentPane.closest('[data-containers]').querySelectorAll('[data-container]').forEach(element => {
            if (element === contentPane) {
                element.classList.add('active');
            } else {
                element.classList.remove('active');
            }
        });
    }
});

document.addEventListener('click', ev => {
    const changeAmountButton = ev.target.closest('[data-change-amount-button]');
    if (changeAmountButton) {
        ev.preventDefault();
        ev.stopPropagation();
        const productForm = changeAmountButton.closest('[data-product-thumb]');
        const productInput = productForm.querySelector('[data-product-amount-input]');
        let productAmountValue = productInput.value;
        const action = changeAmountButton.dataset.direction;
        if (action === 'add') {
            productAmountValue++;
        } else {
            productAmountValue--;
        }
        changeProductAmount(productInput, productAmountValue);
    }
})

function changeProductAmount(input, value) {
    try {
        value = value > 1 ? value : 1;
    } catch {
        value = 1;
    }
    input.value = value;
}

document.addEventListener('change', ev => {
    const amountInput = ev.target.closest('[data-product-amount-input]');
    if (amountInput) {
        changeProductAmount(amountInput, amountInput.value);
    }
});

document.addEventListener('click', ev => {
    const showMoreTool = ev.target.closest('[data-show-more-tool]');
    if (showMoreTool) {
        const container = showMoreTool.closest('[data-show-more-container]');
        container.classList.add('unroll');
    }
});

const filterHandler = {
    productFilterSelector: '[data-auto-compare]',
    changeMarkToolSelector: '[data-change-mark-tool]',
    changeModelToolSelector: '[data-change-model-tool]',
    changeModelModificationToolSelector: '[data-change-model-modification-tool]',
    filterSelector: '[data-auto-compare]',
    modelModificationSelectElement: null,
    tailsPlaceSelector: '[data-search-products-tool] [data-products-listing]',
    tailsPlace: null,
    filterForm: null,
    action: null,
    init: function () {
        this.filterForm = document.querySelector(this.filterSelector);
        this.modelModificationSelect = document.querySelector(this.changeModelModificationToolSelector);
        this.action = this.filterForm.action;
        new SlimSelect({
            select: this.changeMarkToolSelector
        });
        new SlimSelect({
            select: '[data-change-model-tool]'
        });
        try {
            new SlimSelect({
                select: '[data-change-product-type]'
            });
        } catch (e) {

        }
        if (this.modelModificationSelect) {
            new SlimSelect({
                select: this.modelModificationSelect
            });
        }
        document.querySelector(this.changeMarkToolSelector).addEventListener('change', this.changeAutoMarkHandler);
        document.querySelector(this.changeModelToolSelector).addEventListener('change', this.changeAutoModelHandler);
        this.filterForm.addEventListener('submit', (ev) => {
            this.submitHandler.bind(this)(ev)
        });
    },

    changeAutoMarkHandler: async function () {
        let productTypeId;
        const currentForm = this.closest('form');
        const productTypeField = currentForm.querySelector('[name=type_id]');
        if(productTypeField){
            productTypeId = productTypeField.value;
        }else{
            productTypeId = '';
        }
        const auto_mark_id = this.value;

        const parameters = {};

        parameters.headers = { 'for-repair-page' : 1 };

        let options = await fetch(`/auto-model/get-models-from-mark/${auto_mark_id}/${productTypeId}`, parameters).then(response => {
            return response.json()
        });

        console.log(options);

        options  = [{ id: 0, label: "—" }, ...options];
        console.log(options);
        const modelSelect = this.closest(filterHandler.productFilterSelector).querySelector(filterHandler.changeModelToolSelector);
        filterHandler.fillSelect(modelSelect, options);
        await filterHandler.changeAutoModelHandler.call(document.querySelector(filterHandler.changeModelToolSelector));
    },

    changeAutoModelHandler: async function () {
        const modelModificationSelect = this.closest(filterHandler.productFilterSelector).querySelector(filterHandler.changeModelModificationToolSelector);
        if(!modelModificationSelect)return;
        let productTypeId;
        const auto_model_id = this.value;
        const productTypeField = this.closest('form').querySelector('[name=type_id]');
        if(productTypeField){
            productTypeId = productTypeField.value;
        }else{
            productTypeId = '';
        }
        const options = await fetch(`/auto-model-modifications/get-modifications-from-model/${auto_model_id}/${productTypeId}`).then(response => {
            let _response;
            if (response.status === 200) {
                _response = response.json();
            } else {
                _response = [];
            }
            return _response;
        });

        const emptyOption = {id: 0, label: "—"}
        options.unshift(emptyOption);
        if (modelModificationSelect) {
            filterHandler.fillSelect(modelModificationSelect, options);
        }
    },

    fillSelect: function (select, options) {
        select.innerHTML = '';
        options.forEach(option => {
            const optionElement = document.createElement('option')
            optionElement.value = option.id;
            optionElement.text = option.label;
            select.appendChild(optionElement);
        });
        select.click();
    },

    submitHandler: async function (e) {
        e.preventDefault();
        const urlAction = this.filterForm.dataset.urlAction;
        const data = new FormData(this.filterForm);
        const headers = {};
        const characteristicsFilterPlace = document.querySelector('[data-characteristic-filter]');
        if (characteristicsFilterPlace) {
            headers['With-Characteristics-Filter'] = true;
        }
        const manufacturersFilterPlace = document.querySelector('[data-manufacturer-filter]');
        if (manufacturersFilterPlace) {
            headers['With-Manufacturer-Filter'] = true;
        }
        const options = {body: data, method: 'post', headers};
        const action = new URL(`${this.action}`);
        const response = await fetch(action.href, options);
        const responseContent = await response.text();
        const responseHeaders = await response.headers;
        if (responseHeaders.has('New-Url-Path')) {
            urlOperations.replaceUrlPath(responseHeaders.get('New-Url-Path'));
        } else {
            //  urlOperations.replaceQueryParametersWithFormData(data);
        }
        const responseDOM = createElementFromString(`<div>${responseContent}</div>`);
        const listing = responseDOM.querySelector('[data-products-listing]');
        this.tailsPlace = document.querySelector(this.tailsPlaceSelector);
        if (characteristicsFilterPlace) {
            const characteristicsFilterElement = responseDOM.querySelector('[data-characteristic-filter]');
            characteristicsFilterPlace.replaceWith(characteristicsFilterElement);
            new characteristicsFilter(characteristicsFilterElement);
        }
        if (manufacturersFilterPlace) {
            const manufacturersFilterElement = responseDOM.querySelector('[data-manufacturer-filter]');
            manufacturersFilterPlace.replaceWith(manufacturersFilterElement);
            new manufacturerFilter(manufacturersFilterElement);
        }
        this.tailsPlace.replaceWith(listing);
    }
};
window.filterHandler = filterHandler;

class characteristicsFilter {
    constructor(filterElement) {
        if (typeof filterElement.characteristicsFilter !== 'undefined') {
            console.error('filter has been initialized');
            return;
        } else {
            filterElement.characteristicsFilter = this;
        }
        filterElement.querySelectorAll('[data-range-filter]').forEach((rageFilterWrapper) => this.equipRageFilterInput(rageFilterWrapper))
        filterElement.querySelectorAll('[data-select-filter-input]').forEach((selectFilterInput) => this.equipSelectFilterInput(selectFilterInput));
    }

    equipRageFilterInput(rageFilterWrapper) {
        const input = rageFilterWrapper.querySelector('[data-range-filter-input]');
        const minValue = toNumeric(rageFilterWrapper.dataset.minValue);
        const maxValue = toNumeric(rageFilterWrapper.dataset.maxValue);
        const minValueElement = rageFilterWrapper.querySelector('[data-min-value]');
        const maxValueElement = rageFilterWrapper.querySelector('[data-max-value]');
        const step = (maxValue - minValue) / 100;
        noUiSlider.create(input, {
            start: [minValue, maxValue],
            connect: true,
            range: {min: minValue, max: maxValue},
            step: step
        });
        input.noUiSlider.on('update', function (values) {
            const [min, max] = values;
            minValueElement.textContent = toNumeric(min);
            maxValueElement.textContent = toNumeric(max);
        });
        input.noUiSlider.on('change', applyRailComponentsFilterHandler);
    }

    equipSelectFilterInput(selectFilterInput) {
        new SlimSelect({
            select: selectFilterInput
        });
        selectFilterInput.addEventListener('change', applyRailComponentsFilterHandler);
    }
}

window.characteristicsFilter = characteristicsFilter;

class manufacturerFilter {
    constructor(filterElement) {
        if (typeof filterElement.characteristicsFilter !== 'undefined') {
            console.error('filter has been initialized');
            return;
        } else {
            filterElement.characteristicsFilter = this;
        }
        filterElement.querySelectorAll('input').forEach(checkboxFilterInput => this.equipCheckboxFilterInput(checkboxFilterInput));
    }

    equipCheckboxFilterInput(checkboxFilterInput) {
        checkboxFilterInput.addEventListener('change', applyRailComponentsFilterHandler);
    }
}

window.manufacturerFilter = manufacturerFilter;

async function applyRailComponentsFilterHandler() {
    const filterForms = {
        manufacturerFilter: document.querySelector('[data-manufacturer-filter]'),
        characteristicsFilter: document.querySelector('[data-characteristic-filter]'),
        autoCompareForm: document.querySelector('[data-auto-compare]')
    };
    const formsData = new FormData;
    for (let filterName in filterForms) {
        if (filterForms[filterName]) {
            let loopFormData = new FormData(filterForms[filterName]);
            loopFormData.forEach((value, key) => {
                formsData.append(key, value);
            })
        }
    }
    const uiSlides = document.querySelectorAll('[data-range-filter-input] ');
    uiSlides.forEach(element => {
        const filterFieldName = element.dataset.name;
        let [min, max] = element.noUiSlider.get();
        formsData.append(`filter[characteristics][number][${filterFieldName}][min]`, min);
        formsData.append(`filter[characteristics][number][${filterFieldName}][max]`, max);
    });
    const url = new URL(`${window.location.origin}${window.location.pathname}`);
    formsData.forEach((value, key) => {
        url.searchParams.append(key, value);
    })
    const response = await fetch(url.href, ajaxOptions);
    const listing = document.querySelector('[data-products-listing]');
    const listingRender = await response.text();
    listing.outerHTML = listingRender;
}

class changeVariantTool {
    productImagesContainerSelector = '[data-product-images-container]';
    variantSelector = '[data-variant]';
    productPrice = '[data-product-price]';
    productCommonArticle = '[data-product-common-article]';
    productCommonTrademark = '[data-product-common-trademark]';
    productCommonManufacturer = '[data-product-common-manufacturer]';
    productStoragesSelector = '[data-storages]';
    productPrices = '[data-price-for-customer-groups]';
    constructor(element) {
        this.rootElement = element;
        this.equipChangeVariantInputs();
    }

    equipChangeVariantInputs() {
        this.rootElement.addEventListener('change', async ev => {
            const variantElementInput = ev.target.closest('[data-variant-input]');
            if (!variantElementInput) {
                return
            }
            ev.preventDefault();
            ev.stopPropagation();

            const currentVariantElement = variantElementInput.closest(this.variantSelector);
            const currentVariantId = currentVariantElement.querySelector('[data-variant-input]').value;
            const responseContent = await fetch(`/product-variant-blocks/${currentVariantId}`).then(response => response.text());
            const responseElements = window.createElementFromString(responseContent);
            const existsPicturesBlock = document.querySelector(this.productImagesContainerSelector);
            const newPicturesBlock = responseElements.querySelector(this.productImagesContainerSelector);
            existsPicturesBlock.replaceWith(newPicturesBlock);
            const existsStoragesBlock = document.querySelector(this.productStoragesSelector);
            const newStoragesBlock = responseElements.querySelector(this.productStoragesSelector);
            existsStoragesBlock.replaceWith(newStoragesBlock);

            const existsPriceBlock = document.querySelector(this.productPrice);
            const newPriceBlock = responseElements.querySelector(this.productPrice);
            if(existsPriceBlock && newPriceBlock){
                existsPriceBlock.replaceWith(newPriceBlock);
            }

            const existsProductPricesElement = document.querySelector(this.productPrices);
            if(existsProductPricesElement){
                const newProductPricesElement = responseElements.querySelector(this.productPrices);
                existsProductPricesElement.replaceWith(newProductPricesElement);
            }

            const variantArticle = currentVariantElement.dataset.article;
            const productCommonArticle = document.querySelector(this.productCommonArticle);
            productCommonArticle.textContent = variantArticle;

            const variantTrademark = currentVariantElement.dataset.trademark;
            const productCommonTrademark = document.querySelector(this.productCommonTrademark);
            productCommonTrademark.textContent = variantTrademark;


            const variantManufacturer = currentVariantElement.dataset.manufacturer;
            const productCommonManufacturer = document.querySelector(this.productCommonManufacturer);
            productCommonManufacturer.textContent = variantManufacturer;


        });
    }
}

window.changeVariantTool = changeVariantTool;

document.addEventListener('click', async e => {
    const productThumb = e.target.closest('[data-add-to-cart]');
    if (productThumb) {
        e.preventDefault();
        e.stopPropagation();
        const data = new FormData();
        const headerCartSelector = '[data-header-product-cart-element]';
        const noteSelector = '[data-add-product-note]';
        const productElement = productThumb.closest('[data-product]');
        const productId = productElement.dataset.id;
        const selectedProductVariantElement = productElement.querySelector('[data-variant-input]:checked');
        if (selectedProductVariantElement) {
            data.append('product_variant_id', selectedProductVariantElement.value);
        }
        const amountInput = productElement.querySelector('[data-product-amount-input]');
        if (amountInput) {
            data.append('amount', amountInput.value);
        }
        const needInstallationElement = productElement.querySelector('[name="need_installation"]');
        if(needInstallationElement){
            data.append('need_installation', needInstallationElement.checked);
        }
        const options = {
            method: 'post',
            body: data,
            headers: {
                'X-CSRF-Token': token
            }
        };
        const action = `/add-product-to-cart/${productId}`;
        const responseContent = await fetch(action, options).then(response => response.text());
        const newCartElement = responseContent.makeNode(headerCartSelector);
        const existCartElement = document.querySelector(headerCartSelector);
        const noteElement = responseContent.makeNode(noteSelector);
        document.body.appendChild(noteElement);
        setTimeout(ev => {
            noteElement.classList.add('damping');
        }, 1000);
        setTimeout(ev => {
            noteElement.remove();
        }, 4000);
        existCartElement.replaceWith(newCartElement);
    }
});

class inputWithArrowsHandler {

    constructor(element) {
        this.rootElement = element;
        this.input = this.rootElement.querySelector('[data-value-element]');
        this.rootElement.querySelectorAll('[data-change-value-direction]')
            .forEach(button => {
                button.addEventListener('click',
                    (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if(e.pointerType){
                            //странный эффект, когда браузер воспринимает нажатие Enter на input как клик
                            //мышкой по кнопке, причём pointerType при таком событии - пустая строка. Данный if необходим для фильтрации этого события.
                            this.clickArrowsHandler.call(this, button)
                        }
                    }
                )
            });
        this.input.addEventListener('change', () => {
            this.changeInputValueHandler.call(this)
        });
    }

    clickArrowsHandler(button) {
        const direction = button.dataset.changeValueDirection;
        const min = this.input.min;
        if (direction === 'plus') {
            this.input.value++;
        } else {
            if (this.input.value <= min) {
                this.input.value = min;
            } else {
                this.input.value--;
            }
        }
        this.sendEvent(this.input.value);
    }

    changeInputValueHandler() {
        const normalizeValue = parseInt(this.input.value);
        if (isNaN(parseInt(this.input.value))) {
            this.input.value = this.input.defaultValue
        } else {
            const min = this.input.min;
            if (this.input.value <= min) {
                this.input.value = min;
            } else {
                this.input.value = normalizeValue;
            }
        }
        this.sendEvent(this.input.value);
    }

    sendEvent(value) {
        const event = new CustomEvent('changeNumberInputValue', {detail: {target: this, value: parseInt(value)}, bubbles: true});
        this.rootElement.dispatchEvent(event);
    }

}

document.querySelectorAll('[data-input-with-arrows]').forEach(element => {
    new inputWithArrowsHandler(element)
});

document.addEventListener('click', async e => {
    const removePurchaseButton = e.target.closest('[data-remove-purchase]');
    if (removePurchaseButton) {
        e.preventDefault();
        e.stopPropagation();
        const purchaseElement = e.target.closest('[data-purchase-id]');
        const purchaseId = purchaseElement.dataset.purchaseId;
        const headers = {'X-CSRF-Token': token};
        const options = {method: 'delete', headers: headers};
        const purchaseRowElements = document.querySelectorAll(`[data-purchase-id="${purchaseId}"]`);
        purchaseRowElements.forEach(element => element.remove());
        fetch(`/remove-purchase-from-cart/${purchaseId}`, options);
    }
});

async function liveSearchHandler(e) {
    const liveSearchInput = e.target.closest('[data-live-search-input]');
    if (liveSearchInput) {
        e.stopPropagation();
        e.preventDefault();
        const words = liveSearchInput.value;
        const existsResultsRender = document.querySelector('[data-live-results]');
        if (words.length === 0) {
            if (existsResultsRender) {
                existsResultsRender.remove();
                return
            }
        }
        const response = await fetch(`/live-search?words=${words}`).then(response => response);
        const responseHeaders = response.headers;
        const searchSuccessState = responseHeaders.get('state');
        if (existsResultsRender) {
            existsResultsRender.remove();
        }
        if (!searchSuccessState) {
            return false;
        }
        const responseHTML = await response.text();
        const searchResultsElement = createElementFromString(responseHTML);
        liveSearchInput.insertAdjacentElement(`afterend`, searchResultsElement);
    }
}

document.addEventListener('input', async e => {
    await liveSearchHandler(e);
});
document.addEventListener('click', async e => {
    await liveSearchHandler(e);
});

document.addEventListener('click', e => {
    if (!e.target.closest('[data-live-search-form]')) {
        const existsResultsRender = document.querySelector('[data-live-results]');
        if (existsResultsRender) {
            existsResultsRender.remove();
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    if (typeof ymaps === 'undefined') {
        return;
    }
    ymaps.ready(function () {
        const deliveryMap = new ymaps.Map("delivery-map", {
            center: [pickupPointsMapData.center.lat, pickupPointsMapData.center.long],
            zoom: 10
        });
        window.deliveryMap = deliveryMap;
        window.pikupPoints = [];
        pickupPointsMapData.points.forEach((point) => {
            const pickupPoint = new ymaps.GeoObject({
                geometry: {
                    type: "Point",
                    coordinates: [point.coordinates.lat, point.coordinates.long]
                },
                properties: {
                    iconCaption: point.name,
                    pickupPointId: point.id
                },

            });
            deliveryMap.geoObjects.add(pickupPoint);
            pickupPoint.events.add('click', function (e) {
                let eMap = e.get('target');
                const pickupInput = document.querySelector(`[data-map-zoom][value='${eMap.properties._data.pickupPointId}']`)
                pickupInput.click();
            });
            window.pikupPoints[point.id] = pickupPoint;
        });
    });
});

document.addEventListener('change', e => {
    const changePickupPoint = e.target.closest('[data-map-zoom]');
    if (changePickupPoint) {
        const pointId = changePickupPoint.value;
        const point = window.pikupPoints[pointId];
        const pointCoordinates = point.geometry._coordinates;
        window.deliveryMap.setCenter(pointCoordinates, 15, {duration: 1500});
    }
})

document.addEventListener('click', ev => {
    const excessTool = ev.target.closest('[data-excess-tool]');
    if (excessTool) {
        ev.preventDefault();
        const rootBlock = excessTool.closest('[data-excess-state]');
        rootBlock.dataset.excessState = '0';
    }
})

document.addEventListener('submit', async ev => {
    const form = ev.target.closest('[data-form]');
    if (form) {
        ev.preventDefault();
        ev.stopPropagation();
        const data = new FormData(form);
        const options = {method: 'post', body: data};
        const result = {};
        const response = await fetch('/store-form', options).then(response => {
            const headers = response.headers;
            result.validateState = headers.get('Validate-State');
            return response.text()
        }).then(response => response);

        if (result.validateState !== null && result.validateState === 'ok') {
            const formHeight = form.offsetHeight;
            form.outerHTML = `<div style="height: ${formHeight}px;" class="formResponse">Заявка успешно отправлена, мы свяжемся с Вами в ближайшее время</div>`;
        } else {
            form.outerHTML = response;
        }
    }
});


function _modal(content) {
    const alertContent = `
<div class="modal__bg">
    <div class="modal">
        <div class="modal__text">${content}</div>
        <img src="/images/close.svg" class="modal__close" alt="close-icon"/>
    </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', alertContent)
}

window._modal = _modal;

const modalsTemplates = {
    'buy_by_one_click_product_page':
        `<form class="modal-form form one-column-form" data-static-form>
        <div class="form__title">%form_title%</div>
        <input type="hidden" name="form_type" value='buy_by_one_click_product_page'>
        <input type="hidden" name="form_description" value='%form_description%'>
        <input type="hidden" name="form_title" value='%form_title%'>
        <input type="hidden" name="sale_type" value='%sale_type%'>
        <input type="hidden" name="sale_id" value='%sale_id%'>
        <div class="body__cell">
            <div class="form__field">
                <span class="input-wrapper">
                    <input name="name" class="input" placeholder="Ваше имя" value="">
                </span>
            </div>
        </div>
        <div class="body__cell">
            <div class="form__field">
                <span class="input-wrapper">
                    <input name="tel" class="input" type="tel" placeholder="Телефон" value="">
                </span>
            </div>
        </div>
        <div class="body__cell">
            <div class="form__field">
                <span class="input-wrapper">
                    <button class="button type-1">отправить</button>
                </span>
            </div>
        </div>
        <div data-validate-errors class="body__cell"></div>
    </form>`
};

class FormHandler {
    constructor(element) {
        this.element = element;
    }

    showModal(action) {
        this[action]();
    }

    async sendForm() {
        const form = this.element;
        const formData = new FormData(form);
        const action = '/save-form';
        const headers = {'X-CSRF-TOKEN': window.token};
        const options = {method: 'POST', headers: headers, body: formData};
        const response = await fetch(action, options).then(response => response);
        const validateState = response.headers.get('Validate-State');
        if (validateState === 'reject') {
            const validateErrors = await response.text();
            form.querySelector('[data-validate-errors]').innerHTML = validateErrors;
        } else if (validateState === 'success') {
            const formHeight = form.offsetHeight;
            form.outerHTML = `<div style="height: ${formHeight}px;" class="formResponse">Заявка успешно отправлена, мы свяжемся с Вами в ближайшее время</div>`;
        }
    }

    buy_by_one_click_product_page() {
        let saleType, saleId, productName, formTitle, variantName;
        productName = window.productName;
        const availableVariantsInputs = document.querySelectorAll('[data-variant-input]');
        if (availableVariantsInputs.length > 0) {
            saleType = 'variant';
            availableVariantsInputs.forEach(availableVariantInput => {
                if (availableVariantInput.checked) {
                    saleId = availableVariantInput.value;
                    variantName = availableVariantInput.closest('[data-variant]').querySelector('[data-variant-name]').textContent.toLowerCase();
                }
            });
            formTitle = `${productName} ${variantName}`;
        } else {
            saleType = 'product';
            const productElement = document.querySelector('[data-product]');
            saleId = productElement.dataset.id;
            formTitle = productName;
        }
        const formDescription = `Купить в один клик ${formTitle}`.toLowerCase();
        let formContent = modalsTemplates['buy_by_one_click_product_page'];
        formContent = formContent.replaceAll('%form_title%', formTitle);
        formContent = formContent.replaceAll('%form_description%', formDescription);
        formContent = formContent.replaceAll('%sale_type%', saleType);
        formContent = formContent.replaceAll('%sale_id%', saleId);
        _modal(formContent);
    }
}

document.addEventListener('submit', async ev => {
    const form = ev.target.closest('form[data-static-form]');
    if (form) {
        ev.preventDefault();
        ev.stopPropagation();
        const fh = new FormHandler(form);
        await fh.sendForm();
    }
});

document.addEventListener('click', ev => {
    const callingElement = ev.target.closest('[data-show-form]');
    if (callingElement) {
        ev.preventDefault();
        ev.stopPropagation();
        const fh = new FormHandler();
        fh.showModal(callingElement.dataset.showForm);
    }
});

document.addEventListener('click', async ev => {
    const callbackButton = ev.target.closest('[data-modal-form-slug]');
    if (callbackButton) {
        ev.preventDefault();
        ev.stopPropagation();
        const formSlug = callbackButton.dataset.modalFormSlug;
        const formDescription = callbackButton.dataset.modalFormDescription;
        const formTitle = callbackButton.dataset.modalFormTitle;
        await invokeSendRequestForm(callbackButton, formSlug, formDescription, formTitle);
    }
});

async function invokeSendRequestForm(callbackButton, formSlug, formDescription, formTitle) {
    let content = await fetch(`/get-form/${formSlug}`).then(response => {
        return response.text()
    });
    content = content.replaceAll('%form_title%', formTitle);
    content = content.replaceAll('%form_description%', formDescription);
    if (formSlug === 'order_repair') {
        const autoModificationId = callbackButton.dataset.autoModificationId;
        content = content.replaceAll('%auto_modification_id%', autoModificationId);
    }
    _modal(content);
}

document.addEventListener('mousedown', ev => { //remove modal
    const modal__bg = ev.target.classList.contains('modal__bg');
    const modal__close = ev.target.classList.contains('modal__close');
    const modal__button = ev.target.classList.contains('modal__button');
    if (modal__button || modal__bg || modal__close) {
        const element = document.querySelector('.modal__bg');
        element.remove();
    }
});

document.addEventListener('click', ev => {
    const menuButton = ev.target.closest('[data-mobile-menu-button]');
    const mobileMenu = ev.target.closest('[data-mobile-menu]');
    if (menuButton) {
        if (!document.body.classList.contains('menu-opened')) {
            document.body.classList.add('menu-opened');
        } else {
            document.body.classList.remove('menu-opened');
        }
    }
    if (!mobileMenu && !menuButton) {
        document.body.classList.remove('menu-opened');
    }
});


document.addEventListener('changeNumberInputValue', e => {
    const cartPageBody = document.querySelector('[data-page-type="cart"]');
    if(cartPageBody){
        const purchaseAmount = e.detail.value;
        const purchaseId = e.target.closest('[data-purchase-id]').dataset.purchaseId;
        const purchaseTotalPriceElement = document.querySelector(`[data-purchase-id="${purchaseId}"] [data-purchase-total-price]`)
        const pricePerUnit = document.querySelector(`[data-purchase-id="${purchaseId}"] [data-price-per-unit]`).dataset.pricePerUnit;
        const totalPurchasePrice = pricePerUnit * purchaseAmount;
        purchaseTotalPriceElement.dataset.purchaseTotalPrice = String(totalPurchasePrice);
        const priceFormatTool = new Intl.NumberFormat();
        const formattedTotalPrice = priceFormatTool.format(totalPurchasePrice);
        purchaseTotalPriceElement.textContent = formattedTotalPrice;
        renewPrices();
    }
});

function renewPrices(){
    const priceFormatTool = new Intl.NumberFormat();
    const totalCartPrices = document.querySelectorAll('[data-total-cart-price]');
    const totalCartAmount = document.querySelectorAll('[data-total-cart-amount]');
    const purchaseTotalPriceElements = document.querySelectorAll('[data-purchase-total-price]');
    const purchaseTotalAmountElements = document.querySelectorAll('[data-value-element]');
    let totalPrice = 0;
    purchaseTotalPriceElements.forEach(element => {
        totalPrice+= Number(element.dataset.purchaseTotalPrice);
    }, 0);
    totalCartPrices.forEach(totalCartPriceElement => {
        totalCartPriceElement.textContent = priceFormatTool.format(totalPrice);
    });
    let totalAmount = 0;
    purchaseTotalAmountElements.forEach(purchaseTotalAmountElement => {
        totalAmount += Number(purchaseTotalAmountElement.value);
    });
    totalCartAmount.forEach(element => {
        element.textContent = totalAmount;
    });
}

document.addEventListener('click', async function showAdditionalInfo(ev){
    const additionInfoElement = ev.target.closest('[data-show-addition-info]');
    if(additionInfoElement){
        ev.stopPropagation();
        const modificationId = additionInfoElement.closest('[data-modification-id]').dataset.modificationId;
        const response  =  await fetch(`/get-additional-repair-info/${modificationId}`).then(r => r.text());
        _modal(`${response}`);
    }
})

document.addEventListener('click', e => {
    const buyOneClickProductPageButton = e.target.closest('[data-show-form=buy_by_one_click_product_page]');
    if(buyOneClickProductPageButton){
        ym(ym.a[0][0],'reachGoal','kupit-v-1-klik');
    }
})

document.addEventListener('submit', e => {
    const sendOrderForm = e.target.closest('[data-page-type="cart"] form');
    if(sendOrderForm) {
        ym(ym.a[0][0], 'reachGoal', 'oformil-zakaz-pokupka');
    }

    const orderRepairForm = e.target.closest('[data-form-name=order-repair-form]');
    if(orderRepairForm) {
        ym(ym.a[0][0], 'reachGoal', 'zayavka-remont');
    }

})






































