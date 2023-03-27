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